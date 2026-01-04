<?php

namespace App\Http\Controllers\Api\V1\Mentor;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Requests\Api\V1\Mentor\RequeteDonnerFeedback;
use App\Http\Resources\Api\V1\Mentor\FeedbackRessource;
use App\Models\FeedbackMentor;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ControleurFeedback extends ControleurApiBase
{
    /**
     * Lister tous les feedbacks donnés par le mentor
     */
    public function index(Request $requete): JsonResponse
    {
        $mentor = $requete->user();
        
        $query = FeedbackMentor::with(['etudiant.profil', 'projet', 'tache'])
            ->where('mentor_id', $mentor->id);
        
        // Filtres
        if ($requete->has('type')) {
            $query->where('type', $requete->type);
        }
        
        if ($requete->has('etudiant_id')) {
            $query->where('etudiant_id', $requete->etudiant_id);
        }
        
        if ($requete->has('est_lu')) {
            $query->where('est_lu', $requete->est_lu === 'true');
        }
        
        // Tri
        $tri = $requete->input('tri', 'created_at');
        $direction = $requete->input('direction', 'desc');
        
        $feedbacks = $query->orderBy($tri, $direction)
            ->paginate($requete->input('per_page', 20));
        
        $statistiques = [
            'total' => FeedbackMentor::where('mentor_id', $mentor->id)->count(),
            'non_lus' => FeedbackMentor::where('mentor_id', $mentor->id)
                ->where('est_lu', false)
                ->count(),
            'par_type' => FeedbackMentor::where('mentor_id', $mentor->id)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'note_moyenne' => round(FeedbackMentor::where('mentor_id', $mentor->id)
                ->whereNotNull('note_generale')
                ->avg('note_generale') ?? 0, 2),
        ];
        
        return $this->reponseSucces([
            'feedbacks' => FeedbackRessource::collection($feedbacks),
            'statistiques' => $statistiques,
            'meta' => [
                'total' => $feedbacks->total(),
                'par_page' => $feedbacks->perPage(),
                'page_courante' => $feedbacks->currentPage(),
            ],
        ]);
    }
    
    /**
     * Donner un feedback sur du code
     */
    public function donnerFeedbackCode(RequeteDonnerFeedback $requete): JsonResponse
    {
        $mentor = $requete->user();
        $donnees = $requete->validated();
        
        // Vérifier que l'étudiant existe
        $etudiant = Utilisateur::findOrFail($donnees['etudiant_id']);
        
        // Vérifier que le mentor peut donner du feedback à cet étudiant
        if (!$this->peutDonnerFeedback($mentor, $etudiant)) {
            return $this->reponseErreur('Vous ne pouvez pas donner de feedback à cet étudiant', 403);
        }
        
        $feedback = FeedbackMentor::create([
            'mentor_id' => $mentor->id,
            'etudiant_id' => $etudiant->id,
            'projet_id' => $donnees['projet_id'] ?? null,
            'tache_id' => $donnees['tache_id'] ?? null,
            'contenu' => $donnees['contenu'],
            'type' => 'code',
            'points_positifs' => $donnees['points_positifs'] ?? null,
            'points_amelioration' => $donnees['points_amelioration'] ?? null,
            'note_generale' => $donnees['note_generale'] ?? null,
            'est_lu' => false,
        ]);
        
        // Notifier l'étudiant
        event(new \App\Events\FeedbackDonne($feedback));
        
        return $this->reponseSucces([
            'message' => 'Feedback sur le code enregistré',
            'feedback' => new FeedbackRessource($feedback),
        ], 201);
    }
    
    /**
     * Donner un feedback sur un projet
     */
    public function donnerFeedbackProjet(RequeteDonnerFeedback $requete): JsonResponse
    {
        $mentor = $requete->user();
        $donnees = $requete->validated();
        
        // Vérifier que le projet existe
        $projet = Projet::with('membres')->findOrFail($donnees['projet_id']);
        
        // Vérifier que l'étudiant fait partie du projet
        $etudiant = Utilisateur::findOrFail($donnees['etudiant_id']);
        
        if (!$projet->membres->contains($etudiant)) {
            return $this->reponseErreur('Cet étudiant ne fait pas partie de ce projet', 400);
        }
        
        // Vérifier que le mentor peut donner du feedback
        if (!$this->peutDonnerFeedback($mentor, $etudiant)) {
            return $this->reponseErreur('Vous ne pouvez pas donner de feedback à cet étudiant', 403);
        }
        
        $feedback = FeedbackMentor::create([
            'mentor_id' => $mentor->id,
            'etudiant_id' => $etudiant->id,
            'projet_id' => $projet->id,
            'tache_id' => $donnees['tache_id'] ?? null,
            'contenu' => $donnees['contenu'],
            'type' => 'projet',
            'points_positifs' => $donnees['points_positifs'] ?? null,
            'points_amelioration' => $donnees['points_amelioration'] ?? null,
            'note_generale' => $donnees['note_generale'] ?? null,
            'est_lu' => false,
        ]);
        
        // Notifier l'étudiant
        event(new \App\Events\FeedbackDonne($feedback));
        
        // Accorder des points à l'étudiant si le feedback est positif
        if ($donnees['note_generale'] && $donnees['note_generale'] >= 7) {
            $etudiant->ajouterPoints(20);
        }
        
        return $this->reponseSucces([
            'message' => 'Feedback sur le projet enregistré',
            'feedback' => new FeedbackRessource($feedback),
        ], 201);
    }
    
    /**
     * Afficher un feedback spécifique
     */
    public function afficher(Request $requete, $feedbackId): JsonResponse
    {
        $mentor = $requete->user();
        
        $feedback = FeedbackMentor::with([
            'etudiant.profil',
            'projet',
            'tache',
            'mentor.profil',
        ])->findOrFail($feedbackId);
        
        // Vérifier que le feedback appartient au mentor
        if ($feedback->mentor_id !== $mentor->id) {
            return $this->reponseErreur('Ce feedback ne vous appartient pas', 403);
        }
        
        return $this->reponseSucces([
            'feedback' => new FeedbackRessource($feedback),
        ]);
    }
    
    /**
     * Mettre à jour un feedback
     */
    public function mettreAJour(Request $requete, $feedbackId): JsonResponse
    {
        $mentor = $requete->user();
        $feedback = FeedbackMentor::findOrFail($feedbackId);
        
        // Vérifier que le feedback appartient au mentor
        if ($feedback->mentor_id !== $mentor->id) {
            return $this->reponseErreur('Ce feedback ne vous appartient pas', 403);
        }
        
        $donnees = $requete->validate([
            'contenu' => 'sometimes|string',
            'points_positifs' => 'sometimes|array',
            'points_amelioration' => 'sometimes|array',
            'note_generale' => 'sometimes|integer|min:1|max:10',
        ]);
        
        $feedback->update($donnees);
        
        return $this->reponseSucces([
            'message' => 'Feedback mis à jour',
            'feedback' => new FeedbackRessource($feedback->fresh()),
        ]);
    }
    
    /**
     * Supprimer un feedback
     */
    public function supprimer(Request $requete, $feedbackId): JsonResponse
    {
        $mentor = $requete->user();
        $feedback = FeedbackMentor::findOrFail($feedbackId);
        
        // Vérifier que le feedback appartient au mentor
        if ($feedback->mentor_id !== $mentor->id) {
            return $this->reponseErreur('Ce feedback ne vous appartient pas', 403);
        }
        
        $feedback->delete();
        
        return $this->reponseSucces([
            'message' => 'Feedback supprimé',
        ]);
    }
    
    /**
     * Lister les feedbacks pour un projet spécifique
     */
    public function feedbackProjet(Request $requete, $projetId): JsonResponse
    {
        $mentor = $requete->user();
        $projet = Projet::findOrFail($projetId);
        
        $feedbacks = FeedbackMentor::with(['etudiant.profil', 'tache'])
            ->where('mentor_id', $mentor->id)
            ->where('projet_id', $projetId)
            ->orderByDesc('created_at')
            ->paginate(20);
        
        return $this->reponseSucces([
            'feedbacks' => FeedbackRessource::collection($feedbacks),
            'projet' => $projet,
            'meta' => [
                'total' => $feedbacks->total(),
                'par_page' => $feedbacks->perPage(),
            ],
        ]);
    }
    
    /**
     * Créer un feedback pour un projet spécifique
     */
    public function creerFeedbackProjet(RequeteDonnerFeedback $requete, $projetId): JsonResponse
    {
        $requete->merge(['projet_id' => $projetId]);
        return $this->donnerFeedbackProjet($requete);
    }
    
    /**
     * Marquer un feedback comme lu
     */
    public function marquerCommeLu(Request $requete, $feedbackId): JsonResponse
    {
        $feedback = FeedbackMentor::findOrFail($feedbackId);
        $mentor = $requete->user();
        
        // Vérifier que le feedback appartient au mentor
        if ($feedback->mentor_id !== $mentor->id) {
            return $this->reponseErreur('Ce feedback ne vous appartient pas', 403);
        }
        
        $feedback->update(['est_lu' => true]);
        
        return $this->reponseSucces([
            'message' => 'Feedback marqué comme lu',
        ]);
    }
    
    /**
     * Obtenir les statistiques des feedbacks
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
            'total_feedbacks' => FeedbackMentor::where('mentor_id', $mentor->id)
                ->where('created_at', '>=', $dateDebut)
                ->count(),
            
            'feedbacks_par_type' => FeedbackMentor::where('mentor_id', $mentor->id)
                ->where('created_at', '>=', $dateDebut)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            
            'note_moyenne' => round(FeedbackMentor::where('mentor_id', $mentor->id)
                ->where('created_at', '>=', $dateDebut)
                ->whereNotNull('note_generale')
                ->avg('note_generale') ?? 0, 2),
            
            'feedbacks_non_lus' => FeedbackMentor::where('mentor_id', $mentor->id)
                ->where('est_lu', false)
                ->where('created_at', '>=', $dateDebut)
                ->count(),
            
            'etudiants_ayant_recu_feedback' => FeedbackMentor::where('mentor_id', $mentor->id)
                ->where('created_at', '>=', $dateDebut)
                ->distinct('etudiant_id')
                ->count('etudiant_id'),
        ];
        
        return $this->reponseSucces([
            'statistiques' => $statistiques,
            'periode' => $depuis,
            'date_debut' => $dateDebut->format('Y-m-d'),
        ]);
    }
    
    /**
     * Vérifier si un mentor peut donner du feedback à un étudiant
     */
    private function peutDonnerFeedback($mentor, $etudiant): bool
    {
        // Le mentor peut donner du feedback si :
        // 1. L'étudiant est sous son mentorat
        // 2. OU le mentor est administrateur
        // 3. OU le mentor est un mentor "global" (à configurer)
        
        if ($mentor->role === 'administrateur') {
            return true;
        }
        
        // Vérifier si l'étudiant est sous le mentorat du mentor
        return \App\Models\Mentorat::where('mentor_id', $mentor->id)
            ->where('etudiant_id', $etudiant->id)
            ->where('statut', 'accepte')
            ->exists();
    }
}