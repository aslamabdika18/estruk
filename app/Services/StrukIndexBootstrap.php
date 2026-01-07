<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StrukIndexBootstrap
{
    /**
     * Dipanggil saat aplikasi boot
     * Aman, tidak blocking request
     */
    public static function boot(): void
    {
        $base = rtrim((string) config('struk.base_path'), '/\\');
        if ($base === '' || !is_dir($base)) {
            return;
        }

        $currentYear = (int) date('Y');

        foreach (scandir($base) as $dir) {

            // =========================
            // Folder arsip: estruk2024, estruk2025, dst
            // =========================
            if (preg_match('/^estruk(\d{4})$/', $dir, $m)) {
                $year = (string) $m[1];

                if ((int)$year < $currentYear) {
                    self::buildArchiveIndexOnce($year);
                }
            }

            // =========================
            // Folder tahun berjalan: estruk
            // =========================
            if ($dir === 'estruk') {
                self::handleCurrentYear((string)$currentYear);
            }
        }
    }

    /**
     * Build index arsip SEKALI
     * 🔧 FIX: cek SQLite, bukan hanya file JSON
     */
    protected static function buildArchiveIndexOnce(string $year): void
    {
        $indexFile = storage_path("app/struk/{$year}.index.json");

        // 🔧 FIX UTAMA:
        // Jika file index ADA tapi SQLite KOSONG → BUILD ULANG
        if (is_file($indexFile)) {
            $exists = DB::connection('struk_sqlite')
                ->table('struk_index')
                ->where('tahun', $year)
                ->exists();

            if ($exists) {
                return; // benar-benar sudah diindex
            }
        }

        $service = new StrukIndexService($year);
        $service->forceBuildArchive();
    }

    /**
     * Tahun berjalan:
     * - build jika belum ada
     * - update max 1 jam
     */
    protected static function handleCurrentYear(string $year): void
    {
        $indexFile = storage_path("app/struk/{$year}.index.json");
        $metaFile  = storage_path("app/struk/{$year}.meta.json");

        $service = new StrukIndexService($year);

        // Belum ada index → build
        if (!is_file($indexFile)) {
            $service->triggerIncrementalIndex();
            return;
        }

        // Sudah ada → cek 1 jam
        if (is_file($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            $lastRun = (int) ($meta['last_run'] ?? 0);

            if (time() - $lastRun >= 3600) {
                $service->triggerIncrementalIndex();
            }
        }
    }
}
