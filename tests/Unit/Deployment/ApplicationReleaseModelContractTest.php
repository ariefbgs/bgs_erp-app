<?php

namespace Tests\Unit\Deployment;

use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

final class ApplicationReleaseModelContractTest extends TestCase
{
    public function test_application_release_model_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Models\ApplicationRelease::class),
            'Canonical App\Models\ApplicationRelease model must exist.'
        );
    }

    public function test_application_release_is_eloquent_model(): void
    {
        $this->assertTrue(
            is_subclass_of(
                \App\Models\ApplicationRelease::class,
                Model::class
            ),
            'ApplicationRelease must be an Eloquent model.'
        );
    }

    public function test_application_release_uses_canonical_table(): void
    {
        $model =
            new \App\Models\ApplicationRelease();

        $this->assertSame(
            'application_releases',
            $model->getTable()
        );
    }

    public function test_application_release_uses_standard_identity_and_timestamps(): void
    {
        $model =
            new \App\Models\ApplicationRelease();

        $this->assertSame(
            'id',
            $model->getKeyName()
        );

        $this->assertTrue(
            $model->getIncrementing()
        );

        $this->assertTrue(
            $model->usesTimestamps()
        );
    }
}