<?php

namespace App\Services;

use Carbon\Carbon;
use DirectoryIterator;
use RuntimeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // ✅ TETAP DIPAKAI

class StrukIndexService
{
    protected string $basePath;
    protected string $indexFile;
    protected string $metaFile;
    protected string $statusFile; // ✅ TETAP ADA (tidak dihapus)

    public function __construct(protected string $year)
    {
        $base = rtrim((string) config('struk.base_path'), '/\\');

        if ($base === '' || !is_dir($base)) {
            throw new RuntimeException("STRUK_BASE_PATH tidak ditemukan: {$base}");
        }

        /**
         * =====================================================
         * PENENTUAN FOLDER STRUK
         * - estruk        → tahun berjalan
         * - estruk2024    → arsip
         * =====================================================
         */
        if ((int)$year === (int)date('Y') && is_dir("{$base}/estruk")) {
            $this->basePath = "{$base}/estruk";
        } elseif (is_dir("{$base}/estruk{$year}")) {
            $this->basePath = "{$base}/estruk{$year}";
        } else {
            throw new RuntimeException("Folder struk tahun {$year} tidak ditemukan");
        }

        $dir = storage_path('app/struk');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->indexFile  = "{$dir}/{$year}.index.json";
        $this->metaFile   = "{$dir}/{$year}.meta.json";
        $this->statusFile = "{$dir}/{$year}.status.json";

        /**
         * ❌ DULU: triggerIncrementalIndex() DIPANGGIL DI SINI
         * ❗ BUG:
         * - Constructor harus READ-ONLY
         * - Search bisa memicu indexing tanpa sengaja
         *
         * ✅ SEKARANG:
         * - Indexing dipanggil EXPLICIT (lihat Bootstrap)
         */
    }

    /* =====================================================
     | INDEX LOAD (READ ONLY)
     ===================================================== */
    protected function loadIndex(): array
    {
        return is_file($this->indexFile)
            ? json_decode(file_get_contents($this->indexFile), true) ?? []
            : [];
    }

    /* =====================================================
     | INDEX TRIGGER (MAX 1 JAM)
     | KHUSUS TAHUN BERJALAN
     ===================================================== */
    public function triggerIncrementalIndex(): void
    {
        $meta = is_file($this->metaFile)
            ? json_decode(file_get_contents($this->metaFile), true)
            : ['last_run' => 0, 'last_mtime' => 0];

        // ⏱️ Batasi 1 jam (INI KUNCI UTAMA)
        if (time() - ($meta['last_run'] ?? 0) < 3600) {
            return;
        }

        $this->buildIncrementalIndex($meta);
    }

    /* =====================================================
     | BUILD INDEX
     | - TIDAK RESET
     | - HANYA FILE TXT BARU
     ===================================================== */
    protected function buildIncrementalIndex(array $meta = []): void
{
    set_time_limit(0);
    ini_set('memory_limit', '-1');

    $this->log('Index scan start');

    $index = is_file($this->indexFile)
        ? json_decode(file_get_contents($this->indexFile), true)
        : [];

    $lastMtime = (int) ($meta['last_mtime'] ?? 0);
    $maxMtime  = $lastMtime;

    $processed = 0;
    $inserted  = 0;
    $startTime = microtime(true);

    foreach (new \DirectoryIterator($this->basePath) as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'txt') {
            continue;
        }

        $processed++;

        // 🔥 LOG PROGRESS SETIAP 500 FILE
        if ($processed % 500 === 0) {
            $this->log('Index progress', [
                'year'      => $this->year,
                'processed' => $processed,
                'inserted'  => $inserted,
                'elapsed_s' => round(microtime(true) - $startTime, 2),
            ]);
        }

        $mtime = $file->getMTime();
        if ($mtime <= $lastMtime) {
            continue;
        }

        $key = $file->getBasename('.txt');
        if (!preg_match('/^\d{2}\.\d{6}$/', $key)) {
            continue;
        }

        [$kassa, $nomor] = explode('.', $key);

        // =========================
        // JSON INDEX
        // =========================
        $index[$key] = [
            'kassa' => $kassa,
            'nomor' => $nomor,
            'mtime' => $mtime,
        ];

        // =========================
        // SQLITE INDEX
        // =========================
        DB::connection('struk_sqlite')
            ->table('struk_index')
            ->updateOrInsert(
                [
                    'tahun' => (string) $this->year,
                    'key'   => $key,
                ],
                [
                    'kassa' => $kassa,
                    'nomor' => $nomor,
                    'mtime' => $mtime,
                    'path'  => "{$this->basePath}/{$key}.txt",
                ]
            );

        $inserted++;
        $maxMtime = max($maxMtime, $mtime);
    }

    // =========================
    // SIMPAN META & INDEX
    // =========================
    file_put_contents(
        $this->indexFile,
        json_encode($index, JSON_THROW_ON_ERROR)
    );

    file_put_contents(
        $this->metaFile,
        json_encode([
            'last_mtime' => $maxMtime,
            'last_run'   => time(),
        ])
    );

    $this->log('Index update selesai', [
        'year'      => $this->year,
        'processed' => $processed,
        'inserted'  => $inserted,
        'duration'  => round(microtime(true) - $startTime, 2) . 's',
        'last_mtime'=> $maxMtime,
    ]);
}


    /* =====================================================
     | PAKSA BUILD ARSIP (SEKALI SAJA)
     ===================================================== */
    public function forceBuildArchive(): void
{
    // Arsip = folder estrukYYYY, constructor sudah validasi
    if (!is_dir($this->basePath)) {
        throw new \RuntimeException("Folder arsip tidak valid: {$this->basePath}");
    }

    $this->log('Force build archive index');

    $this->buildIncrementalIndex([
        'last_mtime' => 0,
        'last_run'   => 0,
    ]);
}

    /* =====================================================
     | SEARCH (TIDAK DIUBAH)
     ===================================================== */
    public function findByNomor(string $kassa, string $nomor): ?array
    {
        $key = str_pad($kassa, 2, '0', STR_PAD_LEFT)
             . '.' . str_pad($nomor, 6, '0', STR_PAD_LEFT);

        $row = DB::connection('struk_sqlite')
            ->table('struk_index')
            ->where('tahun', $this->year)
            ->where('key', $key)
            ->first();

        if (!$row || !is_file($row->path)) return null;

        return [
            'key'      => $row->key,
            'tahun'    => (string)$this->year,
            'kassa'    => $row->kassa,
            'nomor'    => $row->nomor,
            'label'    => '2031.SA.' . substr($this->year, -2) . '.' . $row->key,
            'datetime' => Carbon::createFromTimestamp($row->mtime)->format('d-m-Y H:i'),
        ];
    }

    protected function log(string $message, array $context = []): void
    {
        Log::channel('struk')->info("[{$this->year}] {$message}", $context);
    }

    public function findByTanggalDanKassa(string $tanggal, string $kassa): array
{
    /**
     * $tanggal format: ddmmyyyy
     * contoh: 12012026
     */

    $start = Carbon::createFromFormat('dmY', $tanggal)
        ->startOfDay()
        ->timestamp;

    $end = Carbon::createFromFormat('dmY', $tanggal)
        ->endOfDay()
        ->timestamp;

    return DB::connection('struk_sqlite')
        ->table('struk_index')
        ->where('tahun', $this->year)
        ->where('kassa', str_pad($kassa, 2, '0', STR_PAD_LEFT))
        ->whereBetween('mtime', [$start, $end])
        ->orderBy('mtime')
        ->get()
        ->map(fn ($r) => [
            'key'      => $r->key,
            'tahun'    => (string) $this->year,
            'kassa'    => $r->kassa,
            'nomor'    => $r->nomor,
            'label'    => '2031.SA.' . substr($this->year, -2) . '.' . $r->key,
            'datetime' => Carbon::createFromTimestamp($r->mtime)->format('d-m-Y H:i'),
        ])
        ->toArray();
}

public function findByKeyword(string $keyword): array
{
    /**
     * Keyword dicocokkan ke:
     * - key (01.000123)
     * - kassa
     * - nomor
     */

    return DB::connection('struk_sqlite')
        ->table('struk_index')
        ->where('tahun', $this->year)
        ->where(function ($q) use ($keyword) {
            $q->where('key', 'like', "%{$keyword}%")
              ->orWhere('kassa', 'like', "%{$keyword}%")
              ->orWhere('nomor', 'like', "%{$keyword}%");
        })
        ->orderByDesc('mtime')
        ->limit(200) // 🔒 pengaman
        ->get()
        ->map(fn ($r) => [
            'key'      => $r->key,
            'tahun'    => (string) $this->year,
            'kassa'    => $r->kassa,
            'nomor'    => $r->nomor,
            'label'    => '2031.SA.' . substr($this->year, -2) . '.' . $r->key,
            'datetime' => Carbon::createFromTimestamp($r->mtime)->format('d-m-Y H:i'),
        ])
        ->toArray();
}

/**
 * Ambil path file TXT untuk preview / stream
 * DIPAKAI OLEH contentStream()
 */
/**
     * Ambil path file TXT untuk preview
     */
    public function getStreamPath(string $key): ?string
    {
        $row = DB::connection('struk_sqlite')
            ->table('struk_index')
            ->where('tahun', $this->year)
            ->where('key', $key)
            ->first();

        if (!$row || empty($row->path)) {
            return null;
        }

        return is_file($row->path) ? $row->path : null;
    }


}
