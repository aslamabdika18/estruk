<?php

namespace App\Services;

use RuntimeException;
use DirectoryIterator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StrukIndexService
{
    protected string $basePath;
    protected string $indexFile;
    protected string $metaFile;
    protected string $statusFile;

    public function __construct(protected string $year)
    {
        $base = rtrim((string) config('struk.base_path'), '/\\');

        if ($base === '' || !is_dir($base)) {
            throw new RuntimeException("STRUK_BASE_PATH tidak ditemukan: {$base}");
        }

        if ((int)$year === (int)date('Y') && is_dir("{$base}/estruk")) {
            $this->basePath = "{$base}/estruk";
        } elseif (is_dir("{$base}/estruk{$year}")) {
            $this->basePath = "{$base}/estruk{$year}";
        } else {
            throw new RuntimeException("Folder struk tahun {$year} tidak ditemukan");
        }

        $dir = storage_path('app/struk');
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $this->indexFile  = "{$dir}/{$year}.index.json";
        $this->metaFile   = "{$dir}/{$year}.meta.json";
        $this->statusFile = "{$dir}/{$year}.status.json";
    }

    /* ==============================
     | ENTRY POINT
     ============================== */
    public function run(): void
    {
        $meta = is_file($this->metaFile)
            ? json_decode(file_get_contents($this->metaFile), true)
            : ['last_run' => 0, 'last_mtime' => 0];

        if ((int)$this->year === (int)date('Y')) {
            if (time() - ($meta['last_run'] ?? 0) < 3600) {
                return;
            }
        }

        $this->buildIncrementalIndex($meta);
    }

    /* ==============================
     | CORE INDEX
     ============================== */
    protected function buildIncrementalIndex(array $meta): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $this->enableSqliteFastMode();

        $this->writeStatus([
            'state' => 'running',
            'year' => $this->year,
            'processed' => 0,
            'inserted' => 0,
            'started_at' => time(),
        ]);

        $index = is_file($this->indexFile)
            ? json_decode(file_get_contents($this->indexFile), true)
            : [];

        $lastMtime = (int)($meta['last_mtime'] ?? 0);
        $maxMtime  = $lastMtime;

        $batch = [];
        $batchSize = 1000;
        $processed = 0;
        $inserted  = 0;
        $start = microtime(true);

        foreach (new DirectoryIterator($this->basePath) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'txt') continue;

            $processed++;
            $mtime = $file->getMTime();
            if ($mtime <= $lastMtime) continue;

            $key = $file->getBasename('.txt');
            if (!preg_match('/^\d{2}\.\d{6}$/', $key)) continue;

            [$kassa, $nomor] = explode('.', $key);

            $index[$key] = compact('kassa', 'nomor', 'mtime');

            $batch[] = [
                'tahun' => $this->year,
                'key'   => $key,
                'kassa' => $kassa,
                'nomor' => $nomor,
                'mtime' => $mtime,
                'path'  => "{$this->basePath}/{$key}.txt",
            ];

            $inserted++;
            $maxMtime = max($maxMtime, $mtime);

            if (count($batch) >= $batchSize) {
                $this->insertBatch($batch);
                $batch = [];
            }

            if ($processed % 500 === 0) {
                $this->writeStatus([
                    'state' => 'running',
                    'year' => $this->year,
                    'processed' => $processed,
                    'inserted' => $inserted,
                    'elapsed_s' => round(microtime(true) - $start, 2),
                ]);
            }
        }

        if ($batch) $this->insertBatch($batch);

        file_put_contents($this->indexFile, json_encode($index));
        file_put_contents($this->metaFile, json_encode([
            'last_run' => time(),
            'last_mtime' => $maxMtime,
        ]));

        $this->writeStatus([
            'state' => 'done',
            'year' => $this->year,
            'processed' => $processed,
            'inserted' => $inserted,
            'elapsed_s' => round(microtime(true) - $start, 2),
        ]);

        Log::info("Index struk selesai", [
            'year' => $this->year,
            'processed' => $processed,
            'inserted' => $inserted,
        ]);
    }

    /* ==============================
     | SQLITE
     ============================== */
    protected function enableSqliteFastMode(): void
    {
        DB::statement('PRAGMA synchronous = OFF');
        DB::statement('PRAGMA journal_mode = MEMORY');
        DB::statement('PRAGMA temp_store = MEMORY');
    }

    protected function insertBatch(array $rows): void
    {
        DB::transaction(fn () =>
            DB::table('struk_index')->upsert(
                $rows,
                ['tahun', 'key'],
                ['kassa', 'nomor', 'mtime', 'path']
            )
        );
    }

    /* ==============================
     | STATUS
     ============================== */
    protected function writeStatus(array $data): void
    {
        $data['updated_at'] = time();
        file_put_contents($this->statusFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    /* ==============================
     | ARSIP
     ============================== */
    public function forceBuildArchive(): void
    {
        $this->buildIncrementalIndex([
            'last_run' => 0,
            'last_mtime' => 0,
        ]);
    }

    public function findByNomor(string $kassa, string $nomor): ?array
{
    $kassa = str_pad(preg_replace('/\D/', '', $kassa), 2, '0', STR_PAD_LEFT);
    $nomor = str_pad(preg_replace('/\D/', '', $nomor), 6, '0', STR_PAD_LEFT);

    $key = "{$kassa}.{$nomor}";

    $row = DB::table('struk_index')
        ->where('tahun', $this->year)
        ->where('key', $key)
        ->first();

    if (!$row || !is_file($row->path)) {
        return null;
    }

    return [
        'key'      => $row->key,
        'tahun'    => (string)$this->year,
        'kassa'    => $row->kassa,
        'nomor'    => $row->nomor,
        'label'    => substr($this->year, -2) . '.' . $row->key,
        'datetime' => date('d-m-Y H:i', $row->mtime),
    ];
}

   public function findByTanggalDanKassa(string $tanggal, string $kassa): array
{
    if (!preg_match('/^\d{8}$/', $tanggal)) {
        return [];
    }

    $dt = \DateTime::createFromFormat('dmY', $tanggal);
    if (!$dt) return [];

    $start = (clone $dt)->setTime(0, 0)->getTimestamp();
    $end   = (clone $dt)->setTime(23, 59, 59)->getTimestamp();

    $kassa = str_pad(preg_replace('/\D/', '', $kassa), 2, '0', STR_PAD_LEFT);

    return DB::table('struk_index')
        ->where('kassa', $kassa)
        ->whereBetween('mtime', [$start, $end])
        ->orderBy('mtime')
        ->limit(500)
        ->get()
        ->map(fn ($r) => [
            'key'      => $r->key,
            'tahun'    => (string) $r->tahun,
            'kassa'    => $r->kassa,
            'nomor'    => $r->nomor,
            // 🔥 LABEL LENGKAP
            'label'    => '2031.SA.' . substr($r->tahun, -2) . '.' . $r->key,
            'datetime' => date('d-m-Y H:i', $r->mtime),
        ])
        ->toArray();
}



    public function searchByKeyword(
    string $keyword,
    ?string $tanggal = null,
    ?string $kassa = null
): array {
    $keyword = strtoupper(trim($keyword));
    if (strlen($keyword) < 3) return [];

    $query = DB::table('struk_index')
        ->select('key','kassa','nomor','mtime')
        ->where('tahun', $this->year);

    if ($kassa) {
        $query->where('kassa', str_pad($kassa, 2, '0', STR_PAD_LEFT));
    }

    if ($tanggal) {
        $dt = \DateTime::createFromFormat('dmY', $tanggal);
        if ($dt) {
            $query->whereBetween('mtime', [
                $dt->setTime(0,0)->getTimestamp(),
                $dt->setTime(23,59,59)->getTimestamp()
            ]);
        }
    }

    // 🔥 SEARCH ISI STRUK
    $query->where('content_index', 'like', "%{$keyword}%");

    return $query
        ->orderByDesc('mtime')
        ->limit(100)
        ->get()
        ->map(fn ($r) => [
            'key'      => $r->key,
            'tahun'    => $this->year,
            'kassa'    => $r->kassa,
            'nomor'    => $r->nomor,
            'label'    => substr($this->year,-2).'.'.$r->key,
            'datetime' => date('d-m-Y H:i', $r->mtime),
        ])
        ->toArray();
}



    public function getStreamPath(string $key): ?string
{
    if (!preg_match('/^\d{2}\.\d{6}$/', $key)) {
        return null;
    }

    $row = DB::table('struk_index')
        ->where('tahun', $this->year)
        ->where('key', $key)
        ->first();

    if (!$row || empty($row->path)) {
        return null;
    }

    return is_file($row->path) ? $row->path : null;
}


}
