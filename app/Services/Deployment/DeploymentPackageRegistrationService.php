<?php

namespace App\Services\Deployment;

use App\Models\ApplicationRelease;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class DeploymentPackageRegistrationService
{
    private const ALLOWED_SCOPES = [
        'application',
        'module',
        'feature',
        'hotfix',
    ];

    public function register(array $package): DeploymentPackageRegistrationResult
    {
        $validationFailure = $this->validatePackage($package);

        if ($validationFailure !== null) {
            return DeploymentPackageRegistrationResult::failure(
                $validationFailure
            );
        }

        try {
            return DB::transaction(function () use ($package) {
                $releaseUuid = trim($package['release_uuid']);
                $releaseId = trim($package['release_id']);

                $duplicateExists = ApplicationRelease::query()
                    ->where('release_uuid', $releaseUuid)
                    ->orWhere('release_id', $releaseId)
                    ->exists();

                if ($duplicateExists) {
                    return DeploymentPackageRegistrationResult::failure(
                        'Release identity already exists.'
                    );
                }

                $release = new ApplicationRelease();

                $release->release_uuid = $releaseUuid;
                $release->release_id = $releaseId;
                $release->name = trim($package['name']);
                $release->scope = trim($package['scope']);
                $release->version = trim($package['version']);

                $release->module =
                    $this->nullableString($package['module'] ?? null);

                $release->feature =
                    $this->nullableString($package['feature'] ?? null);

                $release->previous_version =
                    $this->nullableString(
                        $package['previous_version'] ?? null
                    );

                $release->release_notes =
                    $this->nullableString(
                        $package['release_notes'] ?? null
                    );

                $release->package_filename =
                    trim($package['package_filename']);

                $release->package_sha256 =
                    strtolower(trim($package['sha256']));

                $release->status = 'uploaded';

                if (isset($package['created_by'])) {
                    $release->created_by = $package['created_by'];
                }

                $release->save();

                return DeploymentPackageRegistrationResult::success(
                    (int) $release->getKey()
                );
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateKeyException($exception)) {
                return DeploymentPackageRegistrationResult::failure(
                    'Release identity already exists.'
                );
            }

            return DeploymentPackageRegistrationResult::failure(
                'Package registration persistence failed.'
            );
        } catch (Throwable $exception) {
            return DeploymentPackageRegistrationResult::failure(
                'Package registration persistence failed.'
            );
        }
    }

    private function validatePackage(array $package): ?string
    {
        foreach ([
            'release_uuid',
            'release_id',
            'name',
            'scope',
            'version',
            'package_filename',
            'sha256',
        ] as $required) {
            if (
                ! isset($package[$required]) ||
                ! is_string($package[$required]) ||
                trim($package[$required]) === ''
            ) {
                return "Package {$required} is required.";
            }
        }

        if (! Str::isUuid(trim($package['release_uuid']))) {
            return 'Package release_uuid must be a valid UUID.';
        }

        if (
            ! in_array(
                trim($package['scope']),
                self::ALLOWED_SCOPES,
                true
            )
        ) {
            return 'Package scope is invalid.';
        }

        $sha256 = strtolower(trim($package['sha256']));

        if (! preg_match('/^[a-f0-9]{64}$/', $sha256)) {
            return 'Package sha256 must be a valid SHA-256 hash.';
        }

        if (strlen(trim($package['release_id'])) > 150) {
            return 'Package release_id exceeds maximum length.';
        }

        if (strlen(trim($package['name'])) > 200) {
            return 'Package name exceeds maximum length.';
        }

        if (strlen(trim($package['version'])) > 50) {
            return 'Package version exceeds maximum length.';
        }

        if (strlen(trim($package['package_filename'])) > 255) {
            return 'Package filename exceeds maximum length.';
        }

        if (
            isset($package['module']) &&
            is_string($package['module']) &&
            strlen(trim($package['module'])) > 100
        ) {
            return 'Package module exceeds maximum length.';
        }

        if (
            isset($package['feature']) &&
            is_string($package['feature']) &&
            strlen(trim($package['feature'])) > 150
        ) {
            return 'Package feature exceeds maximum length.';
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function isDuplicateKeyException(
        QueryException $exception
    ): bool {
        $errorInfo = $exception->errorInfo;

        return isset($errorInfo[1]) &&
            (int) $errorInfo[1] === 1062;
    }
}
