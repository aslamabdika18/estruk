<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StrukController;

Route::prefix('struk')->group(function () {

    // Cari berdasarkan nomor struk
    Route::post('/by-nomor', [StrukController::class, 'byNomor']);

    // Cari berdasarkan tanggal & kassa
    Route::post('/by-tanggal', [StrukController::class, 'byTanggal']);

    // Cari berdasarkan keyword (tanggal & kassa opsional)
    Route::post('/by-keyword', [StrukController::class, 'byKeyword']);

});
