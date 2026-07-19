<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureDatabaseReady
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('up') || app()->environment('testing')) {
            return $next($request);
        }

        try {
            $ready = Schema::hasTable('users')
                && Schema::hasColumn('users', 'username')
                && Schema::hasTable('sync_runs')
                && Schema::hasTable('problems')
                && Schema::hasTable('user_problem_stats');
        } catch (\Throwable) {
            $ready = false;
        }

        if ($ready) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Database is still preparing. Please retry in about a minute.',
            ], 503);
        }

        return response(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>PbTrack</title></head>'.
            '<body style="font-family:system-ui;padding:2rem;background:#0b1020;color:#e8e6df">'.
            '<h1>PbTrack is warming up</h1>'.
            '<p>The database is still preparing. Wait about a minute, then refresh.</p>'.
            '</body></html>',
            503,
            ['Content-Type' => 'text/html; charset=UTF-8', 'Retry-After' => '60']
        );
    }
}
