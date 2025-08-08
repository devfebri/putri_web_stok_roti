<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KepalaBakeryMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if ($request->user()) {
            if ($request->user()->role == 'kepalabakery') {
                return $next($request);
            } else {
                return response()->json(['message' => 'Unauthorized. Kepala Bakery access required.'], 403);
            }
        } else {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
    }
}
