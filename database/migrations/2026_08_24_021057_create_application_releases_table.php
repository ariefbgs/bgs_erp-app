<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_releases', function (Blueprint $table) {
            $table->id();

            $table->uuid('release_uuid')->unique();

            $table->string('release_id', 150)->unique();
            $table->string('name', 200);

            $table->enum('scope', [
                'application',
                'module',
                'feature',
                'hotfix',
            ]);

            $table->string('module', 100)->nullable()->index();
            $table->string('feature', 150)->nullable()->index();

            $table->string('version', 50);
            $table->string('previous_version', 50)->nullable();

            $table->enum('status', [
                'uploaded',
                'validated',
                'installed',
                'failed',
                'rolled_back',
                'superseded',
            ])->default('uploaded')->index();

            $table->string('package_filename', 255)->nullable();
            $table->string('package_sha256', 64)->nullable();

            $table->text('release_notes')->nullable();

            $table->timestamp('validated_at')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('installed_by')->nullable();
            $table->unsignedBigInteger('rolled_back_by')->nullable();

            $table->timestamps();

            $table->index(
                ['scope', 'module', 'feature', 'version'],
                'release_scope_version_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_releases');
    }
};
