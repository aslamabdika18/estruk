<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StrukIndexService;
use Throwable;

class StrukIndexCommand extends Command
{
    /**
     * Nama & signature command
     *
     * Contoh:
     * php artisan struk:index
     * php artisan struk:index 2024
     * php artisan struk:index 2025
     */
    protected $signature = 'struk:index {year? : Tahun struk (default tahun berjalan)}';

    /**
     * Deskripsi command
     */
    protected $description = 'Build / update index struk ke SQLite (arsip & tahun berjalan)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = (string) ($this->argument('year') ?? date('Y'));

        $this->info("▶ Mulai indexing struk tahun {$year}");

        try {
            $service = new StrukIndexService($year);

            // Tahun berjalan → incremental (max 1 jam)
            if ((int)$year === (int)date('Y')) {
                $this->line('• Mode: incremental (tahun berjalan)');
                $service->run();
            }
            // Arsip → full build (sekali saja)
            else {
                $this->line('• Mode: arsip (full build)');
                $service->forceBuildArchive();
            }

            $this->info("✔ Index struk {$year} selesai");
            return Command::SUCCESS;

        } catch (Throwable $e) {
            $this->error("✖ Gagal indexing struk {$year}");
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
