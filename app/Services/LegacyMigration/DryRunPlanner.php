<?php

namespace App\Services\LegacyMigration;

use InvalidArgumentException;

/** Pure snapshot planner: no database, filesystem writes or target ID allocation. */
final class DryRunPlanner
{
    private const STATES = ['AUTO-MAPPED', 'TRANSFORMED', 'LEGACY-EXCEPTION', 'REQUIRES-REVIEW', 'REJECTED/INVALID'];

    public function plan(array $manifest, array $source, array $target): array
    {
        if (!is_array($source['tables'] ?? null) || !is_array($target['tables'] ?? null)) {
            throw new InvalidArgumentException('Snapshots require a tables object.');
        }
        $unexported = $source['unexported_tables'] ?? [];
        if (!is_array($unexported) || !array_is_list($unexported)
            || array_filter($unexported, fn ($table) => !is_string($table) || $table === '')) {
            throw new InvalidArgumentException('Invalid unexported table inventory.');
        }
        if (array_intersect(array_keys($source['tables']), $unexported)) {
            throw new InvalidArgumentException('Snapshot contradicts its exported table inventory.');
        }
        if (($manifest['version'] ?? null) !== 1 || !is_array($manifest['entities'] ?? null)
            || !array_is_list($manifest['entities']) || $manifest['entities'] === []) {
            throw new InvalidArgumentException('Manifest version 1 and ordered entities are required.');
        }
        $seen = $tables = $targetTables = $specs = [];
        foreach ($manifest['entities'] as $spec) {
            if (!is_array($spec)) {
                throw new InvalidArgumentException('Invalid entity specification.');
            }
            foreach (['name', 'source_table', 'target_table', 'source_id', 'target_id'] as $field) {
                if (!is_string($spec[$field] ?? null) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $spec[$field])) {
                    throw new InvalidArgumentException('Entity identifiers must be explicit simple names.');
                }
            }
            if (isset($seen[$spec['name']]) || isset($tables[$spec['source_table']]) || isset($targetTables[$spec['target_table']])) {
                throw new InvalidArgumentException('Duplicate entity or source table.');
            }
            $fields = $spec['fields'] ?? null;
            $key = $spec['key'] ?? null;
            $dependencies = $spec['dependencies'] ?? [];
            $allowed = $spec['allowed'] ?? [];
            if (!is_array($fields) || $fields === [] || !is_array($key) || !array_is_list($key) || $key === []
                || !is_array($dependencies) || !is_array($allowed)) {
                throw new InvalidArgumentException('Explicit field mapping and business key are required.');
            }
            foreach ($fields as $to => $from) {
                if (!is_string($to) || !is_string($from) || $to === $spec['target_id']) {
                    throw new InvalidArgumentException('Invalid field mapping; target IDs cannot be copied.');
                }
            }
            foreach ($dependencies as $to => $dependency) {
                if (!is_string($to) || !is_array($dependency) || isset($fields[$to]) || $to === $spec['target_id']
                    || !isset($seen[$dependency['entity'] ?? '']) || !is_string($dependency['source_field'] ?? null)
                    || (isset($dependency['nullable']) && !is_bool($dependency['nullable']))) {
                    throw new InvalidArgumentException('Dependencies must reference earlier entities and have separate target fields.');
                }
            }
            foreach ($key as $field) {
                if (!is_string($field) || (!isset($fields[$field]) && !isset($dependencies[$field]))) {
                    throw new InvalidArgumentException('Every key field must be explicitly mapped.');
                }
            }
            foreach ($allowed as $field => $values) {
                if (!isset($fields[$field]) || !is_array($values) || !array_is_list($values) || $values === []) {
                    throw new InvalidArgumentException('Allowed values must constrain a mapped field.');
                }
            }
            foreach ([[$source, $spec['source_table']], [$target, $spec['target_table']]] as [$snapshot, $table]) {
                if (!is_array($snapshot['tables'][$table] ?? null) || !array_is_list($snapshot['tables'][$table])) {
                    throw new InvalidArgumentException('Every scoped table must be present as a row list, including empty tables.');
                }
            }
            $spec['dependencies'] = $dependencies;
            $spec['allowed'] = $allowed;
            $seen[$spec['name']] = true;
            $tables[$spec['source_table']] = true;
            $targetTables[$spec['target_table']] = true;
            $specs[] = $spec;
        }

        $registry = $lookup = $exceptions = $totals = [];
        foreach ($specs as $spec) {
            $rows = $source['tables'][$spec['source_table']];
            $idCounts = $sourceKeys = $targetKeys = $targetIds = [];
            foreach ($rows as $row) {
                if (is_array($row) && $this->validId($row[$spec['source_id']] ?? null)) {
                    $id = (string) $row[$spec['source_id']];
                    $idCounts[$id] = ($idCounts[$id] ?? 0) + 1;
                }
            }
            foreach ($target['tables'][$spec['target_table']] as $row) {
                if (!is_array($row) || !$this->validId($row[$spec['target_id']] ?? null)) {
                    throw new InvalidArgumentException('Target snapshot has invalid identity.');
                }
                $id = (string) $row[$spec['target_id']];
                if (isset($targetIds[$id])) {
                    throw new InvalidArgumentException('Target snapshot has duplicate identity.');
                }
                $targetIds[$id] = true;
                $key = $this->key($row, $spec['key']);
                if ($key === null) {
                    throw new InvalidArgumentException('Target snapshot has an incomplete business key.');
                }
                $targetKeys[$key][] = $row;
            }
            $candidates = [];
            foreach ($rows as $position => $row) {
                $item = ['entity' => $spec['name'], 'row' => $position + 1, 'source_id' => null,
                    'target_id' => null, 'state' => 'REJECTED/INVALID', 'reason' => 'INVALID_ID', 'references' => []];
                $values = [];
                if (!is_array($row) || !$this->validId($row[$spec['source_id']] ?? null)) {
                    $candidates[] = [$item, null, []];
                    continue;
                }
                $item['source_id'] = $row[$spec['source_id']];
                $item['state'] = 'AUTO-MAPPED';
                $item['reason'] = 'NEW_TARGET_ID_NOT_ALLOCATED';
                if ($idCounts[(string) $item['source_id']] !== 1) {
                    $item['state'] = 'REJECTED/INVALID';
                    $item['reason'] = 'DUPLICATE_SOURCE_ID';
                }
                foreach ($spec['fields'] as $to => $from) {
                    if (!array_key_exists($from, $row) || (!is_scalar($row[$from]) && $row[$from] !== null)) {
                        $item['state'] = 'REJECTED/INVALID';
                        $item['reason'] = 'MISSING_OR_INVALID_FIELD';
                    } else {
                        $values[$to] = $row[$from];
                    }
                }
                if ($item['state'] === 'AUTO-MAPPED') {
                    foreach ($spec['allowed'] as $field => $allowed) {
                        if (!in_array($values[$field], $allowed, true)) {
                            $item['state'] = 'LEGACY-EXCEPTION';
                            $item['reason'] = 'UNAPPROVED_VALUE';
                            break;
                        }
                    }
                }
                if ($item['state'] === 'AUTO-MAPPED') {
                    foreach ($spec['dependencies'] as $to => $dependency) {
                        if (!array_key_exists($dependency['source_field'], $row)) {
                            $item['state'] = 'REJECTED/INVALID';
                            $item['reason'] = 'MISSING_DEPENDENCY_FIELD';
                            break;
                        }
                        $id = $row[$dependency['source_field']] ?? null;
                        if ($id === null && ($dependency['nullable'] ?? false)) {
                            $values[$to] = null;
                            continue;
                        }
                        $parent = $this->validId($id) ? ($lookup[$dependency['entity']][(string) $id] ?? null) : null;
                        if (!$parent || !in_array($parent['state'], ['AUTO-MAPPED', 'TRANSFORMED'], true)) {
                            $item['state'] = 'REQUIRES-REVIEW';
                            $item['reason'] = 'UNRESOLVED_PARENT';
                            break;
                        }
                        $item['references'][$to] = ['entity' => $dependency['entity'], 'source_id' => $id, 'target_id' => $parent['target_id']];
                        $values[$to] = $parent['target_id'];
                        if ($parent['target_id'] === null || (string) $id !== (string) $parent['target_id']) {
                            $item['state'] = 'TRANSFORMED';
                        }
                    }
                }
                $key = $this->key($values, $spec['key']);
                // A key containing a new parent's unallocated ID cannot be guessed.
                if ($key === null && in_array($item['state'], ['AUTO-MAPPED', 'TRANSFORMED'], true)) {
                    $item['state'] = 'REQUIRES-REVIEW';
                    $item['reason'] = 'INCOMPLETE_BUSINESS_KEY';
                }
                if ($key !== null) {
                    $sourceKeys[$key] = ($sourceKeys[$key] ?? 0) + 1;
                }
                $candidates[] = [$item, $key, $values];
            }
            $counts = array_fill_keys(self::STATES, 0);
            foreach ($candidates as [$item, $key, $values]) {
                if (in_array($item['state'], ['AUTO-MAPPED', 'TRANSFORMED'], true)) {
                    $matches = $targetKeys[$key] ?? [];
                    if (($sourceKeys[$key] ?? 0) > 1 || count($matches) > 1) {
                        $item['state'] = 'REQUIRES-REVIEW';
                        $item['reason'] = 'AMBIGUOUS_BUSINESS_KEY';
                    } elseif (count($matches) === 1) {
                        $match = $matches[0];
                        foreach ($values as $field => $value) {
                            if (!array_key_exists($field, $match) || $match[$field] !== $value
                                || ($value === null && isset($item['references'][$field]))) {
                                $item['state'] = 'REQUIRES-REVIEW';
                                $item['reason'] = 'TARGET_FIELD_CONFLICT';
                                break;
                            }
                        }
                        if (in_array($item['state'], ['AUTO-MAPPED', 'TRANSFORMED'], true)) {
                            $item['target_id'] = $match[$spec['target_id']];
                            $item['reason'] = 'EXACT_BUSINESS_KEY_AND_FIELDS';
                        }
                    }
                }
                $counts[$item['state']]++;
                $registry[] = $item;
                if ($item['source_id'] !== null) {
                    $lookup[$spec['name']][(string) $item['source_id']] = $item;
                }
                if (!in_array($item['state'], ['AUTO-MAPPED', 'TRANSFORMED'], true)) {
                    $exceptions[] = $item;
                }
            }
            $totals[$spec['name']] = ['source_rows' => count($rows), 'classified_rows' => array_sum($counts), 'states' => $counts];
        }
        return ['mode' => 'DRY_RUN', 'database_writes' => 0, 'load_implemented' => false,
            'scope' => array_keys($seen), 'unscoped_source_tables' => array_values(array_unique(array_merge(
                array_diff(array_keys($source['tables']), array_keys($tables)), $unexported))),
            'registry' => $registry, 'exceptions' => $exceptions, 'reconciliation' => $totals];
    }

    private function validId(mixed $id): bool
    {
        return (is_int($id) && $id > 0) || (is_string($id) && preg_match('/^[1-9][0-9]*$/D', $id) === 1);
    }

    private function key(array $row, array $fields): ?string
    {
        $values = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $row) || !is_scalar($row[$field]) || $row[$field] === '') {
                return null;
            }
            $values[] = $row[$field];
        }
        return json_encode($values, JSON_THROW_ON_ERROR);
    }
}
