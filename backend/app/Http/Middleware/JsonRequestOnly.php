<?php

namespace App\Http\Middleware;

use App\Services\VueAppService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonRequestOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->wantsJson()) {
            return $next($request);
        }

        return response((new VueAppService)->view());
    }
}
