<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'mot_de_passe' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Mapper les champs français vers anglais pour le reset
        $credentials = [
            'email' => $request->email,
            'password' => $request->mot_de_passe,
            'password_confirmation' => $request->mot_de_passe_confirmation,
            'token' => $request->token,
        ];

        $status = Password::reset(
            $credentials,
            function ($utilisateur) use ($request) {
                $utilisateur->forceFill([
                    'mot_de_passe' => Hash::make($request->string('mot_de_passe')),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($utilisateur));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès',
            'status' => __($status),
        ]);
    }
}
