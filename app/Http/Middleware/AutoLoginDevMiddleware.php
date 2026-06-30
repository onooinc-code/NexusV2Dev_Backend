<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoLoginDevMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        if (app()->environment('local')) {
            $user = \App\Models\User::first();
            if ($user) {
                // Use stateless Auth to prevent session locks
                \Illuminate\Support\Facades\Auth::guard('sanctum')->setUser($user);
                \Illuminate\Support\Facades\Auth::shouldUse('sanctum');
                $request->setUserResolver(function () use ($user) {
                    return $user;
                });
            }
        }
        return $next($request);
    }
}
