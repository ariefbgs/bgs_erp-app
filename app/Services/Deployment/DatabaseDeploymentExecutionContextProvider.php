<?php

namespace App\Services\Deployment;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DatabaseDeploymentExecutionContextProvider
    implements DeploymentExecutionContextProviderInterface
{
    public function forRelease(int $releaseId): DeploymentExecutionContext
    {
        if ($releaseId <= 0) {
            throw new RuntimeException(
                'Deployment release ID must be a positive integer.'
            );
        }

        $release = DB::table('application_releases')
            ->where('id', $releaseId)
            ->first();

        if ($release === null) {
            throw new RuntimeException(
                "Application release [{$releaseId}] was not found."
            );
        }

        $applicationRoot =
            (string) config(
                'deployment.application_root',
                base_path()
            );

        $packageRoot =
            (string) config(
                'deployment.paths.packages',
                storage_path('app/deployment/packages')
            );

        $stagingRoot =
            (string) config(
                'deployment.paths.staging',
                storage_path('app/deployment/staging')
            );

        $recoveryRoot =
            (string) config(
                'deployment.paths.recovery',
                storage_path('app/deployment/recovery')
            );

        $packageFile =
            $this->resolvePackageFileName($release);

        return new DeploymentExecutionContext(
            $this->joinPath(
                $packageRoot,
                $packageFile
            ),
            $this->joinPath(
                $stagingRoot,
                (string) $releaseId
            ),
            $applicationRoot,
            $this->joinPath(
                $recoveryRoot,
                (string) $releaseId
            ),
            is_string($release->package_sha256 ?? null)
                ? strtolower(trim($release->package_sha256))
                : '',
            [
                'release_id' => (string) ($release->release_id ?? ''),
                'version' => (string) ($release->version ?? ''),
                'scope' => (string) ($release->scope ?? ''),
                'module' => $this->nullableString($release->module ?? null),
                'feature' => $this->nullableString($release->feature ?? null),
            ]
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    private function resolvePackageFileName(object $release): string
    {
        foreach ([
            'package_file',
            'package_filename',
            'package_path',
        ] as $column) {
            if (
                property_exists($release, $column) &&
                is_string($release->{$column}) &&
                trim($release->{$column}) !== ''
            ) {
                return basename(
                    str_replace(
                        '\\',
                        '/',
                        trim($release->{$column})
                    )
                );
            }
        }

        return 'release-' .
            (string) $release->id .
            '.zip';
    }

    private function joinPath(
        string $root,
        string $child
    ): string {
        return rtrim(
            $root,
            DIRECTORY_SEPARATOR . '/\\'
        ) .
            DIRECTORY_SEPARATOR .
            ltrim(
                $child,
                DIRECTORY_SEPARATOR . '/\\'
            );
    }
}
