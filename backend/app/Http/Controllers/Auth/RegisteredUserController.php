<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Models\Profil;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'prenom' => ['required', 'string', 'max:255'],
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:utilisateurs,email'],
            'mot_de_passe' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $utilisateur = DB::transaction(function () use ($request) {
            $utilisateur = Utilisateur::create([
                'prenom' => $request->prenom,
                'nom' => $request->nom,
                'email' => $request->email,
                'mot_de_passe' => Hash::make($request->string('mot_de_passe')),
                'role' => 'etudiant',
                'est_actif' => true,
                'points' => 0,
                'niveau' => 1,
            ]);

            // Créer le profil associé
            Profil::create([
                'utilisateur_id' => $utilisateur->id,
                'niveau' => 'debutant',
                'est_disponible_mentorat' => false,
            ]);

            return $utilisateur;
        });

        event(new Registered($utilisateur));

        // Générer un token API pour l'utilisateur
        $token = $utilisateur->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'utilisateur' => [
                'id' => $utilisateur->id,
                'prenom' => $utilisateur->prenom,
                'nom' => $utilisateur->nom,
                'nom_complet' => $utilisateur->nom_complet,
                'email' => $utilisateur->email,
                'role' => $utilisateur->role,
                'niveau' => $utilisateur->niveau,
                'points' => $utilisateur->points,
            ],
            'token' => $token,
        ], 201);
    }
}
