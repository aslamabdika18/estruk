<?php

namespace App\Services;

use Carbon\Carbon;

class StrukIndexService
{
    protected string $basePath = '';
    protected string $indexFile;
    protected int $ttl = 3600; // 1 jam

    public function __construct(protected string $year)
    {
        $base = rtrim(config('struk.base_path'), '/\\');

        if (!is_dir($base)) {
            return;
        }

        if (is_dir("$base/estruk{$year}")) {
            $this->basePath = "$base/estruk{$year}";
        } elseif ((int)$year === (int)date('Y') && is_dir("$base/estruk")) {
            $this->basePath = "$base/estruk";
        }

        $this->indexFile = storage_path("app/struk-index-{$year}.json");
    }

    /* =========================
       INDEX HANDLING
    ========================= */

    protected function ensureIndex(): void
    {
        if (!$this->basePath) {
            return;
        }

        if (
            !is_file($this->indexFile) ||
            time() - filemtime($this->indexFile) > $this->ttl
        ) {
            $this->buildIndex();
        }
    }

    protected function buildIndex(): void
    {
        $data = [];

        foreach (glob($this->basePath . '/*.txt') as $file) {
            $key = basename($file, '.txt'); // 01.000123

            if (!preg_match('/^(\d{2})\.(\d+)$/', $key, $m)) {
                continue;
            }

            $data[$key] = [
                'kassa' => $m[1],
                'nomor' => $m[2],
                'path' => $file,
                'mtime' => filemtime($file),
            ];
        }

        file_put_contents(
            $this->indexFile,
            json_encode($data, JSON_UNESCAPED_SLASHES)
        );
    }

    protected function loadIndex(): array
    {
        $this->ensureIndex();

        if (!is_file($this->indexFile)) {
            return [];
        }

        return json_decode(file_get_contents($this->indexFile), true) ?? [];
    }

    /* =========================
       FORMAT OUTPUT (API CONTRACT)
    ========================= */

    protected function format(string $key, array $info): array
    {
        $time = Carbon::createFromTimestamp($info['mtime']);

        return [
            'key'      => $key,
            'tahun'    => $this->year,
            'kassa'    => $info['kassa'],
            'nomor'    => $info['nomor'],
            'label'    => "2031.SA." . substr($this->year, -2) . ".{$key}",
            'datetime' => $time->format('d-m-Y H:i'),
            'path'     => $info['path'],
        ];
    }

    /* =========================
       PUBLIC SEARCH METHODS
    ========================= */

    public function findByNomor(string $kassa, string $nomor): ?array
    {
        $index = $this->loadIndex();
        $key = str_pad($kassa, 2, '0', STR_PAD_LEFT) . '.' . ltrim($nomor, '0');

        return isset($index[$key])
            ? $this->format($key, $index[$key])
            : null;
    }

    public function findByTanggalDanKassa(string $tanggal, string $kassa): array
    {
        $index = $this->loadIndex();
        $hasil = [];

        $targetDay = Carbon::createFromFormat('dmY', $tanggal)->startOfDay();
        $kassa = str_pad($kassa, 2, '0', STR_PAD_LEFT);

        foreach ($index as $key => $info) {
            if ($info['kassa'] !== $kassa) {
                continue;
            }

            $time = Carbon::createFromTimestamp($info['mtime']);
            if (!$time->isSameDay($targetDay)) {
                continue;
            }

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
            if ($kassa && $info['kassa'] !== $kassa) {
                continue;
            }

            $time = Carbon::createFromTimestamp($info['mtime']);
            if ($targetDay && !$time->isSameDay($targetDay)) {
                continue;
            }

            if (!is_file($info['path'])) {
                continue;
            }

            // BACA FILE HANYA SETELAH FILTER
            if (!str_contains(
                strtoupper(file_get_contents($info['path'])),
                $keyword
            )) {
                continue;
            }

            $hasil[] = $this->format($key, $info);
        }

        return $hasil;
    }

    public function getContent(string $key): ?string
    {
        $index = $this->loadIndex();

        return isset($index[$key]) && is_file($index[$key]['path'])
            ? file_get_contents($index[$key]['path'])
            : null;
    }
}
