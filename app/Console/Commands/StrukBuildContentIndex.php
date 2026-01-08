<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StrukBuildContentIndex extends Command
{
    protected $signature = 'struk:build-content-index';
    protected $description = 'Index isi file struk TXT ke database';

    public function handle(): int
    {
        DB::table('struk_index')
            ->whereNull('content_index')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {

                foreach ($rows as $row) {
                    if (!is_file($row->path)) continue;

                    $text = file_get_contents($row->path);

                    // 🔥 normalisasi
                    $text = strtoupper($text);
                    $text = preg_replace('/[^A-Z0-9 ]/', ' ', $text);
                    $text = preg_replace('/\s+/', ' ', $text);

                    DB::table('struk_index')
                        ->where('id', $row->id)
                        ->update([
                            'content_index' => $text
                        ]);
                }
            });

        $this->info('Content index selesai');
        return self::SUCCESS;
    }
}
