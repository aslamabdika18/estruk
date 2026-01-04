<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StrukController;
use App\Services\StrukIndexService;

Route::prefix('struk')->group(function () {

    // SEARCH
    Route::post('/by-nomor',   [StrukController::class, 'byNomor']);
    Route::post('/by-tanggal', [StrukController::class, 'byTanggal']);
    Route::post('/by-keyword', [StrukController::class, 'byKeyword']);

    // PREVIEW STREAM (BARU)
    Route::post('/content-stream', [StrukController::class, 'contentStream']);

    // TAHUN
    Route::get('/tahun', fn () =>
        response()->json(StrukIndexService::availableYears())
    );
});
