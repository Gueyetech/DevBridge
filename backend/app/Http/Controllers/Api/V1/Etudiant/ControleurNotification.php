<?php

namespace App\Http\Controllers\Api\V1\Etudiant;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControleurNotification extends ControleurApiBase
{
    /**
     * Lister les notifications
     */
    public function index(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();

        $query = Notification::where('utilisateur_id', $utilisateur->id);

        // Filtrer par statut de lecture
        if ($requete->has('non_lues') && $requete->non_lues === 'true') {
            $query->whereNull('lu_a');
        }

        // Filtrer par type
        if ($requete->has('type')) {
            $query->where('type', $requete->type);
        }

        $notifications = $query->orderByDesc('created_at')
            ->paginate($requete->input('par_page', 20));

        return $this->reponseSucces($notifications, 'Notifications récupérées avec succès');
    }

    /**
     * Compter les notifications non lues
     */
    public function compteurNonLues(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();

        $compteur = Notification::where('utilisateur_id', $utilisateur->id)
            ->whereNull('lu_a')
            ->count();

        return $this->reponseSucces(['non_lues' => $compteur], 'Compteur récupéré');
    }

    /**
     * Marquer une notification comme lue
     */
    public function marquerLue(string $id, Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();

        $notification = Notification::where('utilisateur_id', $utilisateur->id)
            ->findOrFail($id);

        $notification->update(['lu_a' => now()]);

        return $this->reponseSucces($notification->fresh(), 'Notification marquée comme lue');
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function marquerToutesLues(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();

        Notification::where('utilisateur_id', $utilisateur->id)
            ->whereNull('lu_a')
            ->update(['lu_a' => now()]);

        return $this->reponseSucces(null, 'Toutes les notifications ont été marquées comme lues');
    }

    /**
     * Supprimer une notification
     */
    public function supprimer(string $id, Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();

        $notification = Notification::where('utilisateur_id', $utilisateur->id)
            ->findOrFail($id);

        $notification->delete();

        return $this->reponseSucces(null, 'Notification supprimée');
    }

    /**
     * Supprimer toutes les notifications lues
     */
    public function supprimerLues(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();

        Notification::where('utilisateur_id', $utilisateur->id)
            ->whereNotNull('lu_a')
            ->delete();

        return $this->reponseSucces(null, 'Notifications lues supprimées');
    }

    /**
     * Configurer les préférences de notification
     */
    public function preferences(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        $profil = $utilisateur->profil;

        if (!$profil) {
            return $this->reponseErreur('Profil non trouvé', 404);
        }

        $validees = $requete->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'notifications_parcours' => 'boolean',
            'notifications_mentorat' => 'boolean',
            'notifications_forum' => 'boolean',
            'notifications_projet' => 'boolean',
        ]);

        $preferencesActuelles = $profil->preferences_notifications ?? [];
        $nouvellesPreferences = array_merge($preferencesActuelles, $validees);

        $profil->update(['preferences_notifications' => $nouvellesPreferences]);

        return $this->reponseSucces(
            ['preferences' => $nouvellesPreferences],
            'Préférences mises à jour'
        );
    }

    /**
     * Obtenir les préférences de notification
     */
    public function obtenirPreferences(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        $profil = $utilisateur->profil;

        $preferencesDefaut = [
            'email_notifications' => true,
            'push_notifications' => true,
            'notifications_parcours' => true,
            'notifications_mentorat' => true,
            'notifications_forum' => true,
            'notifications_projet' => true,
        ];

        $preferences = $profil?->preferences_notifications ?? $preferencesDefaut;

        return $this->reponseSucces(
            ['preferences' => array_merge($preferencesDefaut, $preferences)],
            'Préférences récupérées'
        );
    }
}
