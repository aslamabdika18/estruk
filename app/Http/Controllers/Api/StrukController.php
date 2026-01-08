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
        $result  = $service->findByNomor($data['kassa'], $data['nomor']);

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

    $service = new StrukIndexService(date('Y'));

    $result = $service->findByTanggalDanKassa(
        $data['tanggal'],
        $data['kassa']
    );

    if (empty($result)) {
        return response()->json([
            'message' => "Tidak ada struk pada tanggal {$data['tanggal']} untuk kassa {$data['kassa']}"
        ], 404);
    }

    return response()->json($result);
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

    /* ================= PREVIEW STREAM ================= */

public function contentStream(Request $request)
    {
        $request->validate([
            'tahun' => 'required|string',
            'key'   => 'required|string',
        ]);

        $service = new StrukIndexService($request->tahun);
        $path = $service->getStreamPath($request->key);

        if (!$path) {
            return response('File tidak ditemukan', 404);
        }

        // KIRIM ISI TXT APA ADANYA
        return response()->make(
            file_get_contents($path),
            200,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'no-cache',
            ]
        );
    }


    /* ========= AVAILABLE YEARS (AMAN) ========= */

    public function tahun(): JsonResponse
    {
        $base = rtrim((string) config('struk.base_path'), '/\\');
        $years = [];

        if (is_dir($base)) {
            foreach (scandir($base) as $dir) {
                if (preg_match('/^estruk(\d{4})$/', $dir, $m)) {
                    $years[] = $m[1];
                }

                if ($dir === 'estruk') {
                    $years[] = (string) date('Y');
                }
            }
        }

        $years = array_unique($years);
        rsort($years);

        return response()->json(array_values($years));
    }
}
