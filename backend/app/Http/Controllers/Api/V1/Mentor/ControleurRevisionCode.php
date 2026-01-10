<?php

namespace App\Http\Controllers\Api\V1\Mentor;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Requests\Api\V1\Mentor\RequeteRevisionCode;
use App\Http\Resources\Api\V1\Mentor\DemandeRevisionCodeRessource;
use App\Models\DemandeRevisionCode;
use App\Models\RevisionCode;
use App\Models\Projet;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ControleurRevisionCode extends ControleurApiBase
{
    /**
     * Lister les demandes de révision de code
     */
    public function demandes(Request $requete): JsonResponse
    {
        $mentor = $requete->user();
        
        $query = DemandeRevisionCode::with([
            'etudiant.profil',
            'projet',
            'tache',
            'revisions' => function($query) use ($mentor) {
                $query->where('mentor_id', $mentor->id);
            },
        ])
        ->whereDoesntHave('revisions', function($query) use ($mentor) {
            $query->where('mentor_id', $mentor->id);
        });
        
        // Filtres
        if ($requete->has('statut')) {
            $query->where('statut', $requete->statut);
        }
        
        if ($requete->has('urgence')) {
            $query->where('urgence', $requete->urgence);
        }
        
        if ($requete->has('technologie')) {
            $query->whereJsonContains('technologies', $requete->technologie);
        }
        
        // Filtrer par compétences du mentor
        if ($requete->has('mes_competences')) {
            $competencesMentor = $mentor->competences()->pluck('competences.id');
            $query->where(function($q) use ($competencesMentor) {
                foreach ($competencesMentor as $competenceId) {
                    $q->orWhereJsonContains('competences_ciblees', $competenceId);
                }
            });
        }
        
        // Tri
        $tri = $requete->input('tri', 'urgence');
        $direction = $requete->input('direction', 'desc');
        
        $demandes = $query->orderBy($tri, $direction)
            ->paginate($requete->input('per_page', 15));
        
        $statistiques = [
            'total' => DemandeRevisionCode::count(),
            'en_attente' => DemandeRevisionCode::where('statut', 'en_attente')->count(),
            'en_cours' => DemandeRevisionCode::where('statut', 'en_cours')->count(),
            'terminees' => DemandeRevisionCode::where('statut', 'terminee')->count(),
            'refusees' => RevisionCode::where('mentor_id', $mentor->id)->where('statut', 'refusee')->count(),
            'mes_revisions' => RevisionCode::where('mentor_id', $mentor->id)->count(),
        ];
        
        return $this->reponseSucces([
            'demandes' => DemandeRevisionCodeRessource::collection($demandes),
            'statistiques' => $statistiques,
            'meta' => [
                'total' => $demandes->total(),
                'par_page' => $demandes->perPage(),
                'page_courante' => $demandes->currentPage(),
            ],
        ]);
    }
    
    /**
     * Afficher les détails d'une demande de révision
     */
    public function demandeDetail(Request $requete, $demandeId): JsonResponse
    {
        $demande = DemandeRevisionCode::with([
            'etudiant.profil',
            'projet',
            'tache',
            'revisions.mentor.profil',
            'fichiers',
        ])->findOrFail($demandeId);
        
        $mentor = $requete->user();
        $maRevision = $demande->revisions->where('mentor_id', $mentor->id)->first();
        
        return $this->reponseSucces([
            'demande' => new DemandeRevisionCodeRessource($demande),
            'ma_revision' => $maRevision,
            'peut_accepter' => $this->peutAccepterDemande($demande, $mentor),
        ]);
    }
    
    /**
     * Accepter une demande de révision de code
     */
    public function accepterDemande(Request $requete, $demandeId): JsonResponse
    {
        $demande = DemandeRevisionCode::findOrFail($demandeId);
        $mentor = $requete->user();
        
        // Vérifier si la demande peut être acceptée
        if (!$this->peutAccepterDemande($demande, $mentor)) {
            return $this->reponseErreur('Vous ne pouvez pas accepter cette demande', 403);
        }
        
        // Vérifier si la demande est déjà prise en charge
        if ($demande->statut === 'en_cours') {
            return $this->reponseErreur('Cette demande est déjà en cours de traitement', 400);
        }
        
        // Créer la révision
        $revision = RevisionCode::create([
            'demande_id' => $demande->id,
            'mentor_id' => $mentor->id,
            'statut' => 'en_cours',
            'accepte_a' => now(),
        ]);
        
        // Mettre à jour le statut de la demande
        $demande->update([
            'statut' => 'en_cours',
        ]);
        
        // Notifier l'étudiant
        event(new \App\Events\RevisionCodeAcceptee($demande, $mentor));
        
        return $this->reponseSucces([
            'message' => 'Demande de révision acceptée',
            'revision' => $revision,
            'demande' => new DemandeRevisionCodeRessource($demande->fresh()),
        ]);
    }
    
    /**
     * Refuser une demande de révision
     */
    public function refuserDemande(Request $requete, $demandeId): JsonResponse
    {
        $demande = DemandeRevisionCode::findOrFail($demandeId);
        $mentor = $requete->user();
        
        $requete->validate([
            'raison' => 'required|string|max:500',
        ]);
        
        // Créer une entrée de refus
        RevisionCode::create([
            'demande_id' => $demande->id,
            'mentor_id' => $mentor->id,
            'statut' => 'refusee',
            'commentaire' => $requete->raison,
            'refuse_a' => now(),
        ]);
        
        return $this->reponseSucces([
            'message' => 'Demande de révision refusée',
        ]);
    }
    
    /**
     * Réviser le code (soumettre des commentaires)
     */
    public function reviserCode(RequeteRevisionCode $requete, $demandeId): JsonResponse
    {
        $demande = DemandeRevisionCode::findOrFail($demandeId);
        $mentor = $requete->user();
        
        // Vérifier que le mentor a accepté cette demande
        $revision = RevisionCode::where('demande_id', $demande->id)
            ->where('mentor_id', $mentor->id)
            ->where('statut', 'en_cours')
            ->first();
        
        if (!$revision) {
            return $this->reponseErreur('Vous n\'avez pas accepté cette demande de révision', 403);
        }
        
        $donnees = $requete->validated();
        
        // Mettre à jour la révision
        $revision->update([
            'commentaires' => $donnees['commentaires'],
            'points_positifs' => $donnees['points_positifs'] ?? null,
            'points_amelioration' => $donnees['points_amelioration'] ?? null,
            'note_generale' => $donnees['note_generale'] ?? null,
            'conseils' => $donnees['conseils'] ?? null,
            'statut' => 'termine',
            'termine_a' => now(),
        ]);
        
        // Mettre à jour le statut de la demande
        $demande->update([
            'statut' => 'termine',
            'termine_a' => now(),
        ]);
        
        // Notifier l'étudiant
        event(new \App\Events\RevisionCodeTerminee($demande, $mentor));
        
        // Accorder des points au mentor
        $mentor->ajouterPoints(50); // 50 points pour une révision complète
        
        // Accorder des points à l'étudiant
        $demande->etudiant->ajouterPoints(30); // 30 points pour avoir demandé une révision
        
        return $this->reponseSucces([
            'message' => 'Révision de code soumise avec succès',
            'revision' => $revision,
        ]);
    }
    
    /**
     * Obtenir l'historique des révisions du mentor
     */
    public function historique(Request $requete): JsonResponse
    {
        $mentor = $requete->user();
        
        $query = RevisionCode::with([
            'demande.etudiant.profil',
            'demande.projet',
            'demande.tache',
        ])
        ->where('mentor_id', $mentor->id);
        
        // Filtres
        if ($requete->has('statut')) {
            $query->where('statut', $requete->statut);
        }
        
        if ($requete->has('date_debut')) {
            $query->where('created_at', '>=', $requete->date_debut);
        }
        
        if ($requete->has('date_fin')) {
            $query->where('created_at', '<=', $requete->date_fin);
        }
        
        // Tri
        $revisions = $query->orderByDesc('created_at')
            ->paginate($requete->input('per_page', 20));
        
        $statistiques = [
            'total' => $revisions->total(),
            'terminees' => RevisionCode::where('mentor_id', $mentor->id)
                ->where('statut', 'termine')
                ->count(),
            'en_cours' => RevisionCode::where('mentor_id', $mentor->id)
                ->where('statut', 'en_cours')
                ->count(),
            'refusees' => RevisionCode::where('mentor_id', $mentor->id)
                ->where('statut', 'refuse')
                ->count(),
            'note_moyenne' => round(RevisionCode::where('mentor_id', $mentor->id)
                ->whereNotNull('note_generale')
                ->avg('note_generale') ?? 0, 2),
            'points_gagnes' => RevisionCode::where('mentor_id', $mentor->id)
                ->where('statut', 'termine')
                ->count() * 50, // 50 points par révision
        ];
        
        return $this->reponseSucces([
            'revisions' => $revisions,
            'statistiques' => $statistiques,
            'meta' => [
                'total' => $revisions->total(),
                'par_page' => $revisions->perPage(),
                'page_courante' => $revisions->currentPage(),
            ],
        ]);
    }
    
    /**
     * Obtenir les statistiques des révisions
     */
    public function statistiques(Request $requete): JsonResponse
    {
        $mentor = $requete->user();
        $depuis = $requete->input('depuis', '30days');
        
        $dateDebut = match($depuis) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };
        
        $statistiques = [
            'revisions_terminees' => RevisionCode::where('mentor_id', $mentor->id)
                ->where('statut', 'termine')
                ->where('created_at', '>=', $dateDebut)
                ->count(),
            
            'temps_moyen_revision' => round(RevisionCode::where('mentor_id', $mentor->id)
                ->where('statut', 'termine')
                ->where('created_at', '>=', $dateDebut)
                ->avg(\DB::raw('TIMESTAMPDIFF(MINUTE, accepte_a, termine_a)')) ?? 0, 2),
            
            'note_moyenne_donnee' => round(RevisionCode::where('mentor_id', $mentor->id)
                ->where('statut', 'termine')
                ->where('created_at', '>=', $dateDebut)
                ->whereNotNull('note_generale')
                ->avg('note_generale') ?? 0, 2),
            
            'revisions_par_technologie' => $this->obtenirRevisionsParTechnologie($mentor, $dateDebut),
            
            'etudiants_aides' => RevisionCode::where('mentor_id', $mentor->id)
                ->where('statut', 'termine')
                ->where('created_at', '>=', $dateDebut)
                ->distinct('demande_id')
                ->join('demandes_revision_code', 'revisions_code.demande_id', '=', 'demandes_revision_code.id')
                ->distinct('etudiant_id')
                ->count('etudiant_id'),
            
            'points_gagnes' => RevisionCode::where('mentor_id', $mentor->id)
                ->where('statut', 'termine')
                ->where('created_at', '>=', $dateDebut)
                ->count() * 50,
        ];
        
        return $this->reponseSucces([
            'statistiques' => $statistiques,
            'periode' => $depuis,
        ]);
    }
    
    /**
     * Vérifier si un mentor peut accepter une demande
     */
    private function peutAccepterDemande($demande, $mentor): bool
    {
        // Un mentor peut accepter une demande si :
        // 1. La demande est en attente
        // 2. Le mentor n'a pas déjà accepté/refusé cette demande
        // 3. Le mentor a les compétences requises (optionnel)
        
        if ($demande->statut !== 'en_attente') {
            return false;
        }
        
        // Vérifier si le mentor a déjà interagi avec cette demande
        $dejaInteragi = RevisionCode::where('demande_id', $demande->id)
            ->where('mentor_id', $mentor->id)
            ->exists();
        
        if ($dejaInteragi) {
            return false;
        }
        
        // // Vérifier les compétences si nécessaire
        // if ($demande->competences_ciblees && !empty($demande->competences_ciblees)) {
        //     $competencesMentor = $mentor->competences()->pluck('competences.id')->map(fn($id) => (string)$id)->toArray();
        //     $ciblees = is_array($demande->competences_ciblees) ? $demande->competences_ciblees : json_decode($demande->competences_ciblees, true);
        //     foreach ($ciblees as $competenceId) {
        //         if (!in_array((string)$competenceId, $competencesMentor)) {
        //             return false;
        //         }
        //     }
        // }
        
        return true;
    }
    
    /**
     * Obtenir les révisions par technologie
     */
    private function obtenirRevisionsParTechnologie($mentor, $dateDebut): array
    {
        return DemandeRevisionCode::whereHas('revisions', function($query) use ($mentor, $dateDebut) {
            $query->where('mentor_id', $mentor->id)
                  ->where('statut', 'termine')
                  ->where('created_at', '>=', $dateDebut);
        })
        ->select('technologies')
        ->get()
        ->flatMap(function($demande) {
            return $demande->technologies ?? [];
        })
        ->countBy()
        ->toArray();
    }
}