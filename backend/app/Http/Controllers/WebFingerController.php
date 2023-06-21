<?php

namespace App\Http\Controllers;

use App\Services\Fediverse\WebFingerService;
use Illuminate\Http\Request;

class WebFingerController extends Controller
{
    public const RESOURCE_BAD = 'resource_bad';
    public const RESOURCE_NOT_FOUND = 'resource_not_found';

    public function handle(Request $request)
    {
        $webFingerService = new WebFingerService();
        try {
            $resource = $this->resource($request, $webFingerService);
        } catch(\Exception $e) {
            switch($e->getMessage()) {
                case self::RESOURCE_NOT_FOUND:
                    return response('', 404);
                case self::RESOURCE_BAD:
                default:
                    return response('', 400);
            }

        }

        $webFinger = $webFingerService->webfingerObject($resource);
        return response()
            ->json($webFinger, 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Access-Control-Allow-Origin', '*');
    }

    private function resource(Request $request, WebFingerService $webFingerService): array
    {
        if( !$request->has('resource') || !$request->filled('resource')) {
            throw new \Exception(self::RESOURCE_BAD);
        }

        $resource = $webFingerService->resourceFromProfileUrl($request->input('resource'));
        if(empty($resource)) {
            throw new \Exception(self::RESOURCE_BAD);
        }

        if($resource['domain'] !== $request->getHost()) {
            throw new \Exception(self::RESOURCE_NOT_FOUND);
        }
        return $resource;
    }
}
