<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StrukCleanupCommand extends Command
{
    protected $signature = 'struk:cleanup-db';
    protected $description = 'Hapus index struk lama di database (tanpa hapus folder fisik)';

    public function handle(): int
    {
        $currentYear = (int) date('Y');
        $keepYear = $currentYear - 1;

        Log::channel('struk_index')->info('DB CLEANUP START', [
            'keep_year' => $keepYear,
        ]);

        $deleted = DB::table('struk_index')
            ->where('tahun', '<', $keepYear)
            ->delete();

        Log::channel('struk_index')->info('DB CLEANUP FINISHED', [
            'rows_deleted' => $deleted,
        ]);

        $this->info("Cleanup database selesai. Data tersisa: tahun {$keepYear}");
        return self::SUCCESS;
    }
}
