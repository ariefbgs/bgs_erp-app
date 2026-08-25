<?php

namespace App\Providers;

use App\Services\Deployment\DeploymentPackagePersistenceInterface;
use App\Services\Deployment\LocalDeploymentPackagePersistence;

use App\Services\Deployment\DatabaseDeploymentExecutionContextProvider;
use App\Services\Deployment\DatabaseDeploymentHistoryRepository;
use App\Services\Deployment\DatabaseDeploymentMigrationStateRepository;
use App\Services\Deployment\DatabaseDeploymentReleaseStateRepository;
use App\Services\Deployment\DeploymentExecutionContextProviderInterface;
use App\Services\Deployment\DeploymentHistoryRepositoryInterface;
use App\Services\Deployment\DeploymentMigrationManager;
use App\Services\Deployment\DeploymentMigrationStateRepositoryInterface;
use App\Services\Deployment\DeploymentReleaseStateRepositoryInterface;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            DeploymentPackagePersistenceInterface::class,
            LocalDeploymentPackagePersistence::class
        );
        $this->app->bind(
            DeploymentExecutionContextProviderInterface::class,
            DatabaseDeploymentExecutionContextProvider::class
        );

        $this->app->bind(
            DeploymentReleaseStateRepositoryInterface::class,
            DatabaseDeploymentReleaseStateRepository::class
        );

        $this->app->bind(
            DeploymentHistoryRepositoryInterface::class,
            DatabaseDeploymentHistoryRepository::class
        );

        $this->app->bind(
            DeploymentMigrationStateRepositoryInterface::class,
            DatabaseDeploymentMigrationStateRepository::class
        );

        /*
        |--------------------------------------------------------------------------
        | Deployment migration execution boundary
        |--------------------------------------------------------------------------
        |
        | DeploymentMigrationManager supplies one validated migration file at a
        | time. The container explicitly defines how that file is executed.
        |
        | We intentionally do NOT call a global "artisan migrate" command here.
        | Only the exact migration file selected by the deployment manager may
        | cross this execution boundary.
        |--------------------------------------------------------------------------
        */

        $this->app->bind(
            DeploymentMigrationManager::class,
            function ($app): DeploymentMigrationManager {
                $repository = $app->make(
                    DeploymentMigrationStateRepositoryInterface::class
                );

                $executor = static function (
                    string $migrationName,
                    string $migrationPath
                ): void {
                    if (!is_file($migrationPath)) {
                        throw new RuntimeException(
                            'Deployment migration file does not exist: '
                            .$migrationPath
                        );
                    }

                    $migration = require $migrationPath;

                    if (!is_object($migration)) {
                        throw new RuntimeException(
                            'Deployment migration file must return a migration object: '
                            .$migrationPath
                        );
                    }

                    if (!method_exists($migration, 'up')) {
                        throw new RuntimeException(
                            'Deployment migration object does not define up(): '
                            .$migrationPath
                        );
                    }

                    $migration->up();
                };

                return new DeploymentMigrationManager(
                    $repository,
                    $executor
                );
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
