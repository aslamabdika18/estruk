<?php

namespace App\Http\Controllers;

use App\Services\StrukIndexService;
use Illuminate\Http\Response;

class StrukPreviewController extends Controller
{
    /**
     * GET /struk/preview/{tahun}/{key}
     */
    public function show(string $tahun, string $key): Response
    {
        $service = new StrukIndexService($tahun);

        $content = $service->getContent($key);

        if (!$content) {
            abort(404, 'Struk tidak ditemukan');
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
