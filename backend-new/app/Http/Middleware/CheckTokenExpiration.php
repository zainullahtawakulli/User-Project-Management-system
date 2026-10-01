<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiration
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $token = $request->user()?->currentAccessToken();

        if (
            $token &&
            $token->expires_at &&
            now()->greaterThanOrEqualTo($token->expires_at)
        ) {
            $token->delete();

            return response()->json([
                'message' => 'Your session has expired.',
            ], 401);
        }

        return $next($request);
    }
}
