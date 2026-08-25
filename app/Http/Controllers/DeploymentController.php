<?php

namespace App\Http\Controllers;

use App\Http\Requests\Deployment\UploadDeploymentPackageRequest;
use App\Http\Requests\Deployment\InstallDeploymentReleaseRequest;
use App\Http\Requests\Deployment\RollbackDeploymentReleaseRequest;
use App\Services\Deployment\DeploymentOrchestrator;
use App\Services\Deployment\DeploymentPackageProcessor;
use App\Services\Deployment\DeploymentPackagePersistenceInterface;
use App\Services\Deployment\DeploymentPackageRegistrationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DeploymentController extends Controller
{
    public function __construct(
        private DeploymentPackagePersistenceInterface $packagePersistence,
        private DeploymentPackageProcessor $packageProcessor,
        private DeploymentPackageRegistrationService $packageRegistration,
        private DeploymentOrchestrator $deploymentOrchestrator
    ) {
    }

    public function index(): View
    {
        $releases = DB::table('application_releases')
            ->select([
                'id',
                'release_id',
                'name',
                'scope',
                'module',
                'feature',
                'version',
                'status',
                'created_at',
                'installed_at',
            ])
            ->orderByDesc('id')
            ->paginate(8);

        return view('deployment.index', compact('releases'));
    }

    public function upload(
        UploadDeploymentPackageRequest $request
    ): Response {
        $uploadedPackage = $request->file('package');
        $persisted = null;
        $stagingPath = rtrim(
            (string) config('deployment.paths.staging'),
            DIRECTORY_SEPARATOR . '/\\'
        ) . DIRECTORY_SEPARATOR . Str::uuid()->toString();

        try {
            $persisted = $this->packagePersistence->persist(
                $uploadedPackage
            );

            $processed = $this->packageProcessor->process(
                $persisted['path'],
                $stagingPath
            );

            $manifest = $processed->manifest();

            if (! $processed->isValid() || $manifest === null) {
                $this->deletePersistedPackage($persisted);

                return response()->json([
                    'message' => 'Deployment package is invalid.',
                    'errors' => $processed->errors(),
                ], 422);
            }

            $registration = $this->packageRegistration->register([
                'release_uuid' => Str::uuid()->toString(),
                'release_id' => $manifest->releaseId(),
                'name' => $manifest->releaseId(),
                'scope' => $manifest->scope(),
                'version' => $manifest->version(),
                'module' => $manifest->module(),
                'feature' => $manifest->feature(),
                'package_filename' => $persisted['filename'],
                'sha256' => $persisted['sha256'],
                'created_by' => $request->user()?->getAuthIdentifier(),
            ]);

            if (! $registration->isSuccess()) {
                $this->deletePersistedPackage($persisted);

                return response()->json([
                    'message' => $registration->message()
                        ?? 'Deployment package registration failed.',
                ], 409);
            }
        } catch (Throwable $exception) {
            if (is_array($persisted)) {
                $this->deletePersistedPackage($persisted);
            }

            report($exception);

            return response()->json([
                'message' => 'Deployment package processing failed.',
            ], 422);
        } finally {
            File::deleteDirectory($stagingPath);
            @rmdir(dirname($stagingPath));
        }

        return response()->noContent();
    }

    private function deletePersistedPackage(array $persisted): void
    {
        $path = $persisted['path'] ?? null;

        if (is_string($path) && $path !== '') {
            File::delete($path);
        }
    }

    public function install(
        InstallDeploymentReleaseRequest $request
    ): Response {
        $authenticatedId = $request->user()?->getAuthIdentifier();
        $performedBy = is_numeric($authenticatedId)
            && (int) $authenticatedId > 0
                ? (int) $authenticatedId
                : null;

        $result = $this->deploymentOrchestrator->install(
            (int) $request->validated('application_release_id'),
            $performedBy
        );

        if (! $result->isSuccessful()) {
            return response()->json([
                'message' => 'Deployment installation failed.',
                'release_status' => $result->releaseStatus(),
            ], 409);
        }

        return response()->json([
            'message' => 'Deployment installation completed.',
            'release_status' => $result->releaseStatus(),
        ]);
    }

    public function rollback(
        RollbackDeploymentReleaseRequest $request
    ): Response {
        return response()->json([
            'message' => 'Deployment rollback is not implemented safely.',
            'release_status' => 'installed',
        ], 501);
    }

    public function history(): View
    {
        $history = DB::table('deployment_history as history')
            ->join(
                'application_releases as releases',
                'releases.id',
                '=',
                'history.application_release_id'
            )
            ->select([
                'history.id',
                'history.deployment_uuid',
                'history.action',
                'history.status',
                'history.started_at',
                'history.completed_at',
                'releases.release_id',
                'releases.version',
            ])
            ->orderByDesc('history.id')
            ->paginate(8);

        return view('deployment.history', compact('history'));
    }
}
