<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('user_manuals', function (Blueprint $table) {
        // Hapus unique constraint lama karena satu module_name sekarang punya banyak sub-modul
        $table->dropUnique(['module_name']); 
        
        // Tambahkan kolom hierarchy
        $table->foreignId('parent_id')->nullable()->constrained('user_manuals')->onDelete('cascade')->after('id');
        $table->enum('type', ['modul', 'sub_modul', 'feature'])->default('modul')->after('module_name');
    });
}

public function down()
{
    Schema::table('user_manuals', function (Blueprint $table) {
        $table->dropForeign(['parent_id']);
        $table->dropColumn(['parent_id', 'type']);
        $table->string('module_name')->unique()->change();
    });
}
};
