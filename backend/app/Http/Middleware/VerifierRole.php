<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifierRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $utilisateur = $request->user();

        if (!$utilisateur) {
            return response()->json([
                'succes' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }

        // Vérifier si l'utilisateur a l'un des rôles requis
        if (!in_array($utilisateur->role, $roles)) {
            return response()->json([
                'succes' => false,
                'message' => 'Accès non autorisé. Rôle insuffisant.',
            ], 403);
        }

        // Les administrateurs ont accès à tout
        if ($utilisateur->role === 'administrateur') {
            return $next($request);
        }

        return $next($request);
    }
}
