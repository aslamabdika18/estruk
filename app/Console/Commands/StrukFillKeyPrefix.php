<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StrukFillKeyPrefix extends Command
{
    protected $signature = 'struk:fill-key-prefix';
    protected $description = 'Isi kolom key_prefix tanpa mengganggu data lama';

    public function handle(): int
    {
        DB::table('struk_index')
            ->whereNull('key_prefix')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('struk_index')
                        ->where('id', $row->id)
                        ->update([
                            'key_prefix' => substr($row->key, 0, 6),
                        ]);
                }
            });

        $this->info('key_prefix berhasil diisi');
        return self::SUCCESS;
    }
}
