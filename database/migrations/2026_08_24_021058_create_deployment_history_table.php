<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployment_history', function (Blueprint $table) {
            $table->id();

            $table->uuid('deployment_uuid')->unique();
            $table->unsignedBigInteger('application_release_id')->index();

            $table->enum('action', [
                'validate',
                'install',
                'rollback',
            ])->index();

            $table->enum('status', [
                'started',
                'success',
                'failed',
                'rolled_back',
            ])->index();

            $table->unsignedBigInteger('performed_by')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('recovery_path', 500)->nullable();
            $table->string('database_backup_path', 500)->nullable();

            $table->longText('summary')->nullable();
            $table->longText('error_message')->nullable();

            $table->timestamps();

            $table->index(
                ['application_release_id', 'action', 'status'],
                'deployment_history_release_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_history');
    }
};
