<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();

            // Compatibility layer for existing users.role string.
            $table->string('role', 50);
            $table->unsignedBigInteger('permission_id');

            $table->timestamps();

            $table->unique(
                ['role', 'permission_id'],
                'role_permission_unique'
            );

            $table->index('role');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
