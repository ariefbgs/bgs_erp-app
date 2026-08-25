<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('label', 150);
            $table->string('module', 100)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('permissions')->insert([
            [
                'name' => 'deployment_view',
                'label' => 'View Deployment Manager',
                'module' => 'deployment',
                'description' => 'View deployment status and history.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'deployment_upload',
                'label' => 'Upload Deployment Package',
                'module' => 'deployment',
                'description' => 'Upload and validate release packages.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'deployment_install',
                'label' => 'Install Deployment',
                'module' => 'deployment',
                'description' => 'Install validated release packages.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'deployment_rollback',
                'label' => 'Rollback Deployment',
                'module' => 'deployment',
                'description' => 'Rollback an installed release.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'deployment_manage',
                'label' => 'Manage Deployment',
                'module' => 'deployment',
                'description' => 'Manage deployment configuration and registry.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
