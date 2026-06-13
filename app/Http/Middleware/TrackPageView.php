<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldTrack($request, $response)) {
            return $response;
        }

        $path = '/'.ltrim($request->path(), '/');
        if ($path === '//') {
            $path = '/';
        }

        $now = now();
        $routeName = $request->route()?->getName();

        DB::statement(
            'INSERT INTO page_views (view_date, path, route_name, count, last_viewed_at, created_at, updated_at)
             VALUES (?, ?, ?, 1, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                route_name = VALUES(route_name),
                count = count + 1,
                last_viewed_at = VALUES(last_viewed_at),
                updated_at = VALUES(updated_at)',
            [
                $now->toDateString(),
                $path,
                $routeName,
                $now,
                $now,
                $now,
            ]
        );

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (!$request->isMethod('GET')) {
            return false;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return false;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
            return false;
        }

        $path = '/'.ltrim($request->path(), '/');

        if (
            str_starts_with($path, '/admin') ||
            str_starts_with($path, '/api') ||
            str_starts_with($path, '/build') ||
            str_starts_with($path, '/storage') ||
            str_starts_with($path, '/media') ||
            $path === '/up'
        ) {
            return false;
        }

        $userAgent = strtolower((string) $request->userAgent());
        if ($userAgent !== '' && preg_match('/bot|crawler|spider|slurp|bingpreview|facebookexternalhit|line\\/|whatsapp/i', $userAgent)) {
            return false;
        }

        return true;
    }
}
