<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResponseFormat
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $format = $request->header('Accept') === 'application/xml' ? 'xml' : 'json';

        if ($format === 'xml' && method_exists($response, 'toXml')) {
            return $response->toXml();
        }

        return $response;
    }
}
