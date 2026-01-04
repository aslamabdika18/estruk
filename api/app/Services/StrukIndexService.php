<?php

namespace App\Services;

use Carbon\Carbon;
use DirectoryIterator;
use RuntimeException;

class StrukIndexService
{
    protected string $basePath;
    protected string $indexFile;
    protected int $ttl = 3600;

    public function __construct(protected string $year)
    {
        $base = rtrim((string) config('struk.base_path'), '/\\');

        if ($base === '' || !is_dir($base)) {
            throw new RuntimeException("STRUK_BASE_PATH tidak ditemukan: {$base}");
        }

        if (is_dir("{$base}/estruk{$year}")) {
            $this->basePath = "{$base}/estruk{$year}";
        } elseif ((int)$year === (int)date('Y') && is_dir("{$base}/estruk")) {
            $this->basePath = "{$base}/estruk";
        } else {
            throw new RuntimeException("Folder struk tahun {$year} tidak ditemukan");
        }

        $this->indexFile = storage_path("app/struk-index-{$year}.json");
    }

    /* ================= INDEX (SEARCH) ================= */

    protected function ensureIndex(): void
    {
        if (!is_file($this->indexFile) || time() - filemtime($this->indexFile) > $this->ttl) {
            $this->buildIndex();
        }
    }

    protected function buildIndex(): void
    {
        $data = [];

        foreach (new DirectoryIterator($this->basePath) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'txt') continue;

            $key = $file->getBasename('.txt'); // 01.000006
            if (!preg_match('/^\d{2}\.\d{6}$/', $key)) continue;

            [$kassa, $nomor] = explode('.', $key);

            $data[$key] = [
                'kassa' => $kassa,
                'nomor' => $nomor,
                'mtime' => $file->getMTime(),
            ];
        }

        file_put_contents($this->indexFile, json_encode($data));
    }

    protected function loadIndex(): array
    {
        $this->ensureIndex();
        return is_file($this->indexFile)
            ? json_decode(file_get_contents($this->indexFile), true) ?? []
            : [];
    }

    protected function format(string $key, array $info): array
    {
        return [
            'key'      => $key,
            'tahun'    => $this->year,
            'kassa'    => $info['kassa'],
            'nomor'    => $info['nomor'],
            'label'    => '2031.SA.' . substr($this->year, -2) . '.' . $key,
            'datetime' => Carbon::createFromTimestamp($info['mtime'])->format('d-m-Y H:i'),
        ];
    }

    /* ================= SEARCH API ================= */

    public function findByNomor(string $kassa, string $nomor): ?array
    {
        $index = $this->loadIndex();
        $key = str_pad($kassa, 2, '0', STR_PAD_LEFT)
             . '.'
             . str_pad($nomor, 6, '0', STR_PAD_LEFT);

        return isset($index[$key]) ? $this->format($key, $index[$key]) : null;
    }

    public function findByTanggalDanKassa(string $tanggal, string $kassa): array
    {
        $index = $this->loadIndex();
        $hasil = [];

        $targetDay = Carbon::createFromFormat('dmY', $tanggal)->startOfDay();
        $kassa = str_pad($kassa, 2, '0', STR_PAD_LEFT);

        foreach ($index as $key => $info) {
            if ($info['kassa'] !== $kassa) continue;

            $time = Carbon::createFromTimestamp($info['mtime']);
            if (!$time->isSameDay($targetDay)) continue;

            $hasil[] = $this->format($key, $info);
        }

        return $hasil;
    }

    public function searchByKeyword(
        string $keyword,
        ?string $tanggal = null,
        ?string $kassa = null
    ): array {
        $index = $this->loadIndex();
        $hasil = [];

        $keyword = strtoupper(trim($keyword));
        $kassa = $kassa ? str_pad($kassa, 2, '0', STR_PAD_LEFT) : null;
        $targetDay = $tanggal
            ? Carbon::createFromFormat('dmY', $tanggal)->startOfDay()
            : null;

        foreach ($index as $key => $info) {
            if ($kassa && $info['kassa'] !== $kassa) continue;

            $time = Carbon::createFromTimestamp($info['mtime']);
            if ($targetDay && !$time->isSameDay($targetDay)) continue;

            $path = $this->basePath . DIRECTORY_SEPARATOR . $key . '.txt';
            if (!is_file($path)) continue;

            if (!str_contains(strtoupper(file_get_contents($path)), $keyword)) continue;

            $hasil[] = $this->format($key, $info);
        }

        return $hasil;
    }

    /* ================= PREVIEW STREAM (TAMBAHAN) ================= */

    public function getStreamPath(string $key): ?string
    {
        if (!preg_match('/^\d{2}\.\d{6}$/', $key)) {
            return null;
        }

        $path = $this->basePath . DIRECTORY_SEPARATOR . $key . '.txt';
        return is_file($path) ? $path : null;
    }

    /* ================= AVAILABLE YEARS ================= */

    public static function availableYears(): array
    {
        $base = rtrim((string) config('struk.base_path'), '/\\');
        if (!is_dir($base)) return [];

        $years = [];

        foreach (scandir($base) as $dir) {
            if (preg_match('/^estruk(\d{4})$/', $dir, $m)) {
                $years[] = $m[1];
            }
            if ($dir === 'estruk') {
                $years[] = (string)date('Y');
            }
        }

        $years = array_unique($years);
        rsort($years);

        return array_values($years);
    }
}
