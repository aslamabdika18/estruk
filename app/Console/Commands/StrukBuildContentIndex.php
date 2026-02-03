<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StrukBuildContentIndex extends Command
{
    protected $signature = 'struk:build-content-index';
    protected $description = 'Index isi file struk TXT ke content_index';

    public function handle(): int
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $startTime = microtime(true);
        $total = DB::table('struk_index')->whereNull('content_index')->count();

        Log::channel('struk_index')->info('START content_index', [
            'total_pending' => $total,
            'started_at' => now()->toDateTimeString(),
        ]);

        $this->info("Total pending: {$total}");

        if ($total === 0) {
            $this->info('Tidak ada data untuk diproses');
            return self::SUCCESS;
        }

        $processed = 0;
        $batchNo = 0;

        DB::table('struk_index')
            ->select('id', 'path')
            ->whereNull('content_index')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$processed, &$batchNo, $total, $startTime) {

                $batchNo++;
                $batchCount = 0;

                foreach ($rows as $row) {
                    if (!is_file($row->path)) continue;

                    $text = file_get_contents($row->path);

                    $text = strtoupper($text);
                    $text = preg_replace('/[^A-Z0-9 ]/', ' ', $text);
                    $text = preg_replace('/\s+/', ' ', $text);

                    DB::table('struk_index')
                        ->where('id', $row->id)
                        ->update(['content_index' => $text]);

                    $batchCount++;
                    $processed++;
                }

                $elapsed = round(microtime(true) - $startTime, 2);

                Log::channel('struk_index')->info('BATCH DONE', [
                    'batch' => $batchNo,
                    'processed_total' => $processed,
                    'processed_batch' => $batchCount,
                    'remaining' => max(0, $total - $processed),
                    'elapsed_sec' => $elapsed,
                ]);

                $this->info(
                    "Batch {$batchNo} | total: {$processed}/{$total} | elapsed: {$elapsed}s"
                );
            });

        Log::channel('struk_index')->info('FINISH content_index', [
            'processed' => $processed,
            'elapsed_sec' => round(microtime(true) - $startTime, 2),
            'finished_at' => now()->toDateTimeString(),
        ]);

        $this->info('Content index SELESAI');

        return self::SUCCESS;
    }
}
