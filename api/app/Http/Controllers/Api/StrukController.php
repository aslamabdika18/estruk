<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StrukIndexService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StrukController extends Controller
{
    public function byNomor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tahun' => 'required|digits:4',
            'kassa' => 'required|numeric',
            'nomor' => 'required|numeric',
        ]);

        $service = new StrukIndexService($data['tahun']);

        $result = $service->findByNomor(
            $data['kassa'],
            $data['nomor']
        );

        if (!$result) {
            return response()->json([
                'message' => 'Struk tidak ditemukan',
            ], 404);
        }

        return response()->json([$result]);
    }

    public function byTanggal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tanggal' => 'required|digits:8',
            'kassa'   => 'required|numeric',
        ]);

        $tahun = substr($data['tanggal'], -4);

        $service = new StrukIndexService($tahun);

        return response()->json(
            $service->findByTanggalDanKassa(
                $data['tanggal'],
                $data['kassa']
            )
        );
    }

    public function byKeyword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'keyword' => 'required|string|min:2',
            'tanggal' => 'nullable|digits:8',
            'kassa'   => 'nullable|numeric',
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
}
