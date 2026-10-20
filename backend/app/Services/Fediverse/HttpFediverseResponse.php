<?php

declare(strict_types=1);

namespace App\Services\Fediverse;

use Illuminate\Http\JsonResponse;

class HttpFediverseResponse {
    public function get(int $statusCode, ?array $content = []): JsonResponse
    {
        return response()->json($content, $statusCode, [], JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/activity+json')
            ->header('Access-Control-Allow-Origin', '*');
    }
}
