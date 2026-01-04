<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ControleurApiBase extends Controller
{
    /**
     * Réponse de succès standardisée
     */
    protected function reponseSucces($data = [], $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], $code);
    }
    
    /**
     * Réponse d'erreur standardisée
     */
    protected function reponseErreur($message, $code = 400, $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return response()->json($response, $code);
    }
    
    /**
     * Réponse de ressource créée
     */
    protected function reponseCree($data = []): JsonResponse
    {
        return $this->reponseSucces($data, 201);
    }
    
    /**
     * Réponse de ressource supprimée
     */
    protected function reponseSupprime(): JsonResponse
    {
        return $this->reponseSucces(['message' => 'Supprimé avec succès']);
    }
    
    /**
     * Réponse de validation échouée
     */
    protected function reponseValidationErreur($errors): JsonResponse
    {
        return $this->reponseErreur('Erreur de validation', 422, $errors);
    }
    
    /**
     * Réponse non autorisée
     */
    protected function reponseNonAutorise($message = 'Non autorisé'): JsonResponse
    {
        return $this->reponseErreur($message, 401);
    }
    
    /**
     * Réponse interdite
     */
    protected function reponseInterdit($message = 'Accès interdit'): JsonResponse
    {
        return $this->reponseErreur($message, 403);
    }
    
    /**
     * Réponse non trouvée
     */
    protected function reponseNonTrouve($message = 'Ressource non trouvée'): JsonResponse
    {
        return $this->reponseErreur($message, 404);
    }
}