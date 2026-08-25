<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployment_files', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('application_release_id')->index();

            $table->enum('operation', [
                'add',
                'replace',
                'delete',
            ]);

            $table->string('relative_path', 500);
            $table->string('sha256', 64)->nullable();
            $table->string('backup_sha256', 64)->nullable();

            $table->boolean('installed')->default(false);
            $table->timestamp('installed_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['application_release_id', 'relative_path'],
                'deployment_file_release_path_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_files');
    }
};
