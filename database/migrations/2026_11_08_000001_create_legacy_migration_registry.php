<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('legacy_migration_ids', function (Blueprint $table) {
            $table->id();
            $table->string('source_database', 100);
            $table->string('source_table', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('target_table', 100);
            $table->unsignedBigInteger('target_id');
            $table->char('source_row_hash', 64);
            $table->timestamps();
            $table->unique(['source_database', 'source_table', 'source_id'], 'legacy_source_identity_unique');
            $table->index(['target_table', 'target_id'], 'legacy_target_lookup');
        });
        Schema::create('legacy_migration_exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('source_database', 100);
            $table->string('source_table', 100);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reason_code', 100);
            $table->string('disposition', 40)->default('REQUIRES-REVIEW');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['source_table', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_migration_exceptions');
        Schema::dropIfExists('legacy_migration_ids');
    }
};
