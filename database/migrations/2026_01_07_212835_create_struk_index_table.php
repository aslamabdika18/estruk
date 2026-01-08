<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('struk_index', function (Blueprint $table) {
    $table->id();
    $table->string('tahun', 4);
    $table->string('key', 20);
    $table->string('kassa', 5);
    $table->string('nomor', 10);
    $table->unsignedBigInteger('mtime');
    $table->string('path');

    $table->unique(['tahun', 'key']);
    $table->index(['tahun', 'nomor']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('struk_index');
    }
};
