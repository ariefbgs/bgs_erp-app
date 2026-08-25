<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployment_migrations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('application_release_id')->index();

            $table->string('migration_name', 255);
            $table->string('relative_path', 500);

            $table->string('sha256', 64);

            $table->enum('status', [
                'pending',
                'executed',
                'failed',
                'rolled_back',
            ])->default('pending')->index();

            $table->timestamp('executed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['application_release_id', 'migration_name'],
                'deployment_migration_release_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_migrations');
    }
};
