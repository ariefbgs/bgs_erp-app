<?php

namespace App\Services\Deployment;

use JsonException;

final class DeploymentPackageProcessor
{
    public function __construct(
        private ?DeploymentPackageExtractor $extractor = null,
        private ?DeploymentPackageValidator $validator = null,
        private ?DeploymentPackageInspector $inspector = null
    ) {
        $this->extractor ??=
            new DeploymentPackageExtractor();

        $this->validator ??=
            new DeploymentPackageValidator();

        $this->inspector ??=
            new DeploymentPackageInspector();
    }

    public function process(
        string $zipPath,
        string $stagingPath
    ): DeploymentPackageProcessResult {

        /*
        |--------------------------------------------------------------------------
        | 1. SECURE ZIP EXTRACTION
        |--------------------------------------------------------------------------
        */

        $extraction =
            $this->extractor->extract(
                $zipPath,
                $stagingPath
            );

        if (!$extraction->isValid()) {
            return DeploymentPackageProcessResult::invalid(
                $extraction->errors(),
                $stagingPath
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 2. READ MANIFEST
        |--------------------------------------------------------------------------
        */

        $manifestPath =
            rtrim(
                $stagingPath,
                DIRECTORY_SEPARATOR
            )
            .DIRECTORY_SEPARATOR
            .'manifest.json';

        if (!is_file($manifestPath)) {
            return DeploymentPackageProcessResult::invalid(
                ['manifest.json is missing after extraction.'],
                $stagingPath
            );
        }

        $json =
            file_get_contents(
                $manifestPath
            );

        if (!is_string($json)) {
            return DeploymentPackageProcessResult::invalid(
                ['Unable to read manifest.json.'],
                $stagingPath
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 3. JSON PARSE — FAIL CLOSED
        |--------------------------------------------------------------------------
        */

        try {
            $data = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            return DeploymentPackageProcessResult::invalid(
                [
                    'manifest.json contains invalid JSON: '
                    .$e->getMessage(),
                ],
                $stagingPath
            );
        }

        if (!is_array($data)) {
            return DeploymentPackageProcessResult::invalid(
                ['manifest.json must contain a JSON object.'],
                $stagingPath
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 4. MANIFEST SECURITY VALIDATION
        |--------------------------------------------------------------------------
        */

        $validation =
            $this->validator->validate(
                $data
            );

        $manifest =
            $validation->manifest();

        if (!$validation->isValid()) {
            return DeploymentPackageProcessResult::invalid(
                $validation->errors(),
                $stagingPath,
                $manifest
            );
        }

        if ($manifest === null) {
            return DeploymentPackageProcessResult::invalid(
                ['Validated manifest is unexpectedly missing.'],
                $stagingPath
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 5. PHYSICAL PAYLOAD + CHECKSUM INSPECTION
        |--------------------------------------------------------------------------
        */

        $inspection =
            $this->inspector->inspect(
                $stagingPath,
                $manifest
            );

        if (!$inspection->isValid()) {
            return DeploymentPackageProcessResult::invalid(
                $inspection->errors(),
                $stagingPath,
                $manifest
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 6. FINAL VALID PACKAGE
        |--------------------------------------------------------------------------
        */

        return DeploymentPackageProcessResult::valid(
            $manifest,
            $stagingPath
        );
    }
}