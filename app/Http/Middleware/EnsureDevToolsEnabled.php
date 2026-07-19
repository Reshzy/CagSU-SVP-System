<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDevToolsEnabled
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('dev-tools.enabled')) {
            abort(404);
        }

        $user = $request->user();

        if (! $user || ! $user->hasRole('System Admin')) {
            abort(403);
        }

        return $next($request);
    }
}
