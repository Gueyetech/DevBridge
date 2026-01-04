<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - DevBridge
|--------------------------------------------------------------------------
|
| Routes de l'API pour la plateforme d'apprentissage DevBridge
|
*/

// Routes publiques (non authentifiées)
Route::prefix('auth')->group(function () {
    Route::post('/inscription', [RegisteredUserController::class, 'store'])
        ->name('auth.inscription');
    
    Route::post('/connexion', [AuthenticatedSessionController::class, 'store'])
        ->name('auth.connexion');
    
    Route::post('/mot-de-passe-oublie', [PasswordResetLinkController::class, 'store'])
        ->name('auth.mot-de-passe-oublie');
    
    Route::post('/reinitialiser-mot-de-passe', [NewPasswordController::class, 'store'])
        ->name('auth.reinitialiser-mot-de-passe');
});

// Routes protégées (authentifiées via Sanctum)
Route::middleware(['auth:sanctum'])->group(function () {
    // Authentification
    Route::prefix('auth')->group(function () {
        Route::get('/utilisateur', [AuthenticatedSessionController::class, 'utilisateur'])
            ->name('auth.utilisateur');
        
        Route::post('/deconnexion', [AuthenticatedSessionController::class, 'destroy'])
            ->name('auth.deconnexion');
    });
});

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Routes versionnées de l'API V1
|
*/

Route::prefix('v1')->group(function () {
    // Routes Étudiant
    require __DIR__.'/api/v1/etudiant.php';
    
    // Routes Mentor
    require __DIR__.'/api/v1/mentor.php';
    
    // Routes Administrateur
    require __DIR__.'/api/v1/administrateur.php';
    
    // Routes Communes (authentifiées)
    require __DIR__.'/api/v1/commun.php';
});
