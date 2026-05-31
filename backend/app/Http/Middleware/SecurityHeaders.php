<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    private const HEADERS = [
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        'X-Permitted-Cross-Domain-Policies' => 'none',
        'X-DNS-Prefetch-Control' => 'off',
    ];

    private const CSP_SELF = "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data: https:; "
        . "font-src 'self' data:; "
        . "connect-src 'self' https:; "
        . "frame-ancestors 'none'; "
        . "form-action 'self'; "
        . "base-uri 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof Response) {
            foreach (self::HEADERS as $key => $value) {
                $response->headers->set($key, $value);
            }

            $csp = config('app.env') === 'production' ? self::CSP_SELF : self::CSP_SELF . "; report-uri /api/v1/core/csp-report";
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
