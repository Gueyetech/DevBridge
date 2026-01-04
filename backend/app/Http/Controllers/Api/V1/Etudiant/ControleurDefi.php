<?php

namespace App\Http\Controllers\Api\V1\Etudiant;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Models\Defi;
use App\Models\ParticipationDefi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControleurDefi extends ControleurApiBase
{
    /**
     * Lister les défis disponibles
     */
    public function index(Request $requete): JsonResponse
    {
        $query = Defi::where('est_actif', true)
            ->where(function ($q) {
                $q->whereNull('date_fin')
                    ->orWhere('date_fin', '>=', now());
            });

        // Filtres
        if ($requete->has('difficulte')) {
            $query->where('difficulte', $requete->difficulte);
        }

        if ($requete->has('type')) {
            $query->where('type', $requete->type);
        }

        if ($requete->has('recherche')) {
            $query->where(function ($q) use ($requete) {
                $q->where('titre', 'like', "%{$requete->recherche}%")
                    ->orWhere('description', 'like', "%{$requete->recherche}%");
            });
        }

        $defis = $query->withCount('participations')
            ->orderByDesc('created_at')
            ->paginate($requete->input('par_page', 15));

        return $this->reponseSucces($defis, 'Défis récupérés avec succès');
    }

    /**
     * Afficher un défi
     */
    public function afficher(string $id, Request $requete): JsonResponse
    {
        $defi = Defi::withCount('participations')->findOrFail($id);
        $utilisateur = $requete->user();

        // Vérifier si l'utilisateur participe déjà
        $participation = ParticipationDefi::where('defi_id', $id)
            ->where('utilisateur_id', $utilisateur->id)
            ->first();

        return $this->reponseSucces([
            'defi' => $defi,
            'participation' => $participation,
            'est_participant' => $participation !== null,
        ], 'Défi récupéré avec succès');
    }

    /**
     * Participer à un défi
     */
    public function participer(string $id, Request $requete): JsonResponse
    {
        $defi = Defi::findOrFail($id);
        $utilisateur = $requete->user();

        // Vérifier si le défi est actif
        if (!$defi->est_actif) {
            return $this->reponseErreur('Ce défi n\'est plus actif', 400);
        }

        // Vérifier si le défi n'est pas expiré
        if ($defi->date_fin && $defi->date_fin < now()) {
            return $this->reponseErreur('Ce défi est terminé', 400);
        }

        // Vérifier si l'utilisateur participe déjà
        $existant = ParticipationDefi::where('defi_id', $id)
            ->where('utilisateur_id', $utilisateur->id)
            ->first();

        if ($existant) {
            return $this->reponseErreur('Vous participez déjà à ce défi', 400);
        }

        $participation = ParticipationDefi::create([
            'defi_id' => $defi->id,
            'utilisateur_id' => $utilisateur->id,
            'statut' => 'en_cours',
            'debute_a' => now(),
        ]);

        return $this->reponseSucces($participation, 'Participation enregistrée avec succès', 201);
    }

    /**
     * Soumettre une solution
     */
    public function soumettre(string $id, Request $requete): JsonResponse
    {
        $defi = Defi::findOrFail($id);
        $utilisateur = $requete->user();

        $participation = ParticipationDefi::where('defi_id', $id)
            ->where('utilisateur_id', $utilisateur->id)
            ->first();

        if (!$participation) {
            return $this->reponseErreur('Vous ne participez pas à ce défi', 400);
        }

        if ($participation->statut === 'termine') {
            return $this->reponseErreur('Vous avez déjà soumis une solution', 400);
        }

        $validees = $requete->validate([
            'solution' => 'required|string',
            'url_depot' => 'nullable|url',
            'commentaire' => 'nullable|string',
        ]);

        $participation->update([
            'solution' => $validees['solution'],
            'url_depot' => $validees['url_depot'] ?? null,
            'commentaire' => $validees['commentaire'] ?? null,
            'statut' => 'soumis',
            'soumis_a' => now(),
        ]);

        return $this->reponseSucces($participation->fresh(), 'Solution soumise avec succès');
    }

    /**
     * Abandonner un défi
     */
    public function abandonner(string $id, Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();

        $participation = ParticipationDefi::where('defi_id', $id)
            ->where('utilisateur_id', $utilisateur->id)
            ->first();

        if (!$participation) {
            return $this->reponseErreur('Vous ne participez pas à ce défi', 400);
        }

        if ($participation->statut === 'termine') {
            return $this->reponseErreur('Vous ne pouvez pas abandonner un défi terminé', 400);
        }

        $participation->update([
            'statut' => 'abandonne',
            'abandonne_a' => now(),
        ]);

        return $this->reponseSucces(null, 'Défi abandonné');
    }

    /**
     * Voir mes participations
     */
    public function mesParticipations(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();

        $participations = ParticipationDefi::where('utilisateur_id', $utilisateur->id)
            ->with('defi')
            ->orderByDesc('created_at')
            ->paginate($requete->input('par_page', 15));

        return $this->reponseSucces($participations, 'Participations récupérées avec succès');
    }

    /**
     * Voir le classement d'un défi
     */
    public function classement(string $id): JsonResponse
    {
        $defi = Defi::findOrFail($id);

        $classement = ParticipationDefi::where('defi_id', $id)
            ->where('statut', 'termine')
            ->with('utilisateur:id,prenom,nom,avatar')
            ->orderByDesc('score')
            ->orderBy('termine_a')
            ->limit(50)
            ->get();

        return $this->reponseSucces([
            'defi' => $defi,
            'classement' => $classement,
        ], 'Classement récupéré avec succès');
    }
}
