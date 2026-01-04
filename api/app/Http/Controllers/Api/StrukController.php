<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StrukIndexService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StrukController extends Controller
{
    /* ========= SEARCH ========= */

    public function byNomor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tahun' => 'required|digits:4',
            'kassa' => 'required|string',
            'nomor' => 'required|string',
        ]);

        $service = new StrukIndexService($data['tahun']);
        $result = $service->findByNomor($data['kassa'], $data['nomor']);

        if (!$result) {
            return response()->json(['message' => 'Struk tidak ditemukan'], 404);
        }

        return response()->json([$result]);
    }

    public function byTanggal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tanggal' => 'required|digits:8',
            'kassa'   => 'required|string',
        ]);

        $tahun = substr($data['tanggal'], -4);
        $service = new StrukIndexService($tahun);

        return response()->json(
            $service->findByTanggalDanKassa($data['tanggal'], $data['kassa'])
        );
    }

    public function byKeyword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'keyword' => 'required|string|min:2',
            'tanggal' => 'nullable|digits:8',
            'kassa'   => 'nullable|string',
        ]);

        $tahun = $data['tanggal']
            ? substr($data['tanggal'], -4)
            : date('Y');

        $service = new StrukIndexService($tahun);

        return response()->json(
            $service->searchByKeyword(
                $data['keyword'],
                $data['tanggal'] ?? null,
                $data['kassa'] ?? null
            )
        );
    }

    /* ========= PREVIEW STREAM (BARU, CEPAT) ========= */

    public function contentStream(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'tahun' => 'required|digits:4',
            'key'   => 'required|string',
        ]);

        $service = new StrukIndexService($data['tahun']);
        $path = $service->getStreamPath($data['key']);

        if (!$path) {
            abort(404);
        }

        return response()->stream(function () use ($path) {
            $handle = fopen($path, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
