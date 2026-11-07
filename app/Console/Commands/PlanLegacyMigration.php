<?php

namespace App\Console\Commands;

use App\Services\LegacyMigration\DryRunPlanner;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;

class PlanLegacyMigration extends Command
{
    protected $signature = 'legacy:plan {manifest} {source} {target}';
    protected $description = 'Plan legacy ID mapping and reconciliation from JSON snapshots without database writes';

    public function handle(DryRunPlanner $planner): int
    {
        try {
            $inputs = $hashes = [];
            foreach (['manifest', 'source', 'target'] as $name) {
                $path = (string) $this->argument($name);
                if (!is_file($path) || !is_readable($path)) {
                    throw new InvalidArgumentException('Every input must be a readable JSON file.');
                }
                $bytes = file_get_contents($path, false, null, 0, 16777217);
                if ($bytes === false || strlen($bytes) > 16777216) {
                    throw new InvalidArgumentException('Input exceeds the 16 MiB per-file limit or cannot be read.');
                }
                $inputs[$name] = json_decode($bytes, true, 64, JSON_THROW_ON_ERROR);
                if (!is_array($inputs[$name])) {
                    throw new InvalidArgumentException('Inputs must be JSON objects.');
                }
                $hashes[$name] = hash('sha256', $bytes);
            }
            $report = $planner->plan($inputs['manifest'], $inputs['source'], $inputs['target']);
            $report['input_sha256'] = $hashes;
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            return $report['exceptions'] === [] && $report['unscoped_source_tables'] === [] ? 0 : 2;
        } catch (InvalidArgumentException | JsonException $exception) {
            $this->error('Plan rejected: ' . $exception->getMessage());
            return 1;
        }
    }
}
