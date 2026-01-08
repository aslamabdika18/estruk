<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('struk_index', function (Blueprint $table) {

            // ⚡ index untuk filter tanggal + sorting
            $table->index(['tahun', 'mtime'], 'idx_tahun_mtime');

            // ⚡ index untuk kassa
            $table->index(['tahun', 'kassa'], 'idx_tahun_kassa');

            // ⚡ index untuk pencarian key
            $table->index(['tahun', 'key'], 'idx_tahun_key');

            // 🚀 kolom prefix (SUPER CEPAT)
            if (!Schema::hasColumn('struk_index', 'key_prefix')) {
                $table->string('key_prefix', 6)->nullable();
            }
        });

        // index kolom prefix (harus di luar Schema::table)
        Schema::table('struk_index', function (Blueprint $table) {
            $table->index('key_prefix', 'idx_key_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('struk_index', function (Blueprint $table) {
            $table->dropIndex('idx_tahun_mtime');
            $table->dropIndex('idx_tahun_kassa');
            $table->dropIndex('idx_tahun_key');
            $table->dropIndex('idx_key_prefix');

            if (Schema::hasColumn('struk_index', 'key_prefix')) {
                $table->dropColumn('key_prefix');
            }
        });
    }
};
