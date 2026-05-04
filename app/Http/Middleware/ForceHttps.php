<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.force_https', false)) {
            $isSecure = $request->secure()
                || $request->header('X-Forwarded-Proto') === 'https'
                || str_contains((string) $request->header('CF-Visitor', ''), '"https"');

            if (!$isSecure) {
                return redirect()->secure($request->getRequestUri(), 301);
            }
        }

        return $next($request);
    }
}
