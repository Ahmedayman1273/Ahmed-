<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->type !== 'admin') {
            return response()->json(['message' => 'Access denied. Admins only.'], 403);
        }

        return $next($request);
    }
}
