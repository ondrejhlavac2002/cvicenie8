<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PremiumOnly
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $user = $request->user();

        if (!$user || !$user->hasActivePremium()) {
            return response()->json([
                'message' => 'Táto funkcia je dostupná len pre prémiových používateľov.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
