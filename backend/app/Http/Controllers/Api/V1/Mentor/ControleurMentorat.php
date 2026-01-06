<?php

namespace App\Http\Controllers\Api\V1\Mentor;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Requests\Api\V1\Mentor\RequetePlanifierSession;
use App\Http\Requests\Api\V1\Mentor\RequeteDonnerFeedback;
use App\Http\Resources\Api\V1\Mentor\MentoratRessource;
use App\Http\Resources\Api\V1\Mentor\SessionMentoratRessource;
use App\Models\Mentorat;
use App\Models\SessionMentorat;
use App\Models\FeedbackMentor;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ControleurMentorat extends ControleurApiBase
{
    /**
     * Lister les demandes de mentorat reçues
     */
    public function demandes(Request $requete): JsonResponse
    {
        $mentor = $requete->user();
        
        $demandes = Mentorat::with(['etudiant.profil'])
            ->where('mentor_id', $mentor->id)
            ->where('statut', 'demande')
            ->orderByDesc('demande_a')
            ->paginate(10);
        
        return $this->reponseSucces([
            'demandes' => MentoratRessource::collection($demandes),
            'meta' => [
                'total' => $demandes->total(),
                'en_attente' => Mentorat::where('mentor_id', $mentor->id)
                    ->where('statut', 'demande')
                    ->count(),
            ],
        ]);
    }
    
    /**
     * Accepter une demande de mentorat
     */
    public function accepterDemande(Request $requete, $mentoratId): JsonResponse
    {
        $mentorat = Mentorat::findOrFail($mentoratId);
        $mentor = $requete->user();
        
        // Vérifier que le mentorat appartient au mentor
        if ($mentorat->mentor_id !== $mentor->id) {
            return $this->reponseErreur('Cette demande ne vous est pas destinée', 403);
        }
        
        // Vérifier que la demande est encore en attente
        if ($mentorat->statut !== 'demande') {
            return $this->reponseErreur('Cette demande a déjà été traitée', 400);
        }
        
        $mentorat->update([
            'statut' => 'accepte',
            'accepte_a' => now(),
            'message_acceptation' => $requete->input('message', 'Bienvenue dans le programme de mentorat!'),
        ]);
        
        // Notifier l'étudiant
        event(new \App\Events\MentoratAccepte($mentorat));
        
        return $this->reponseSucces([
            'message' => 'Demande de mentorat acceptée',
            'mentorat' => new MentoratRessource($mentorat),
        ]);
    }
    
    /**
     * Refuser une demande de mentorat
     */
    public function refuserDemande(Request $requete, $mentoratId): JsonResponse
    {
        $mentorat = Mentorat::findOrFail($mentoratId);
        $mentor = $requete->user();
        
        // Vérifier que le mentorat appartient au mentor
        if ($mentorat->mentor_id !== $mentor->id) {
            return $this->reponseErreur('Cette demande ne vous est pas destinée', 403);
        }
        
        // Vérifier que la demande est encore en attente
        if ($mentorat->statut !== 'demande') {
            return $this->reponseErreur('Cette demande a déjà été traitée', 400);
        }
        
        $mentorat->update([
            'statut' => 'refuse',
            'message_refus' => $requete->input('raison', 'Je ne suis pas disponible pour le moment.'),
        ]);
        
        return $this->reponseSucces([
            'message' => 'Demande de mentorat refusée',
        ]);
    }
    
    /**
     * Lister les étudiants du mentor
     */
    public function etudiants(Request $requete): JsonResponse
    {
        $mentor = $requete->user();
        
        $etudiants = Mentorat::with([
            'etudiant.profil',
            'etudiant.competences',
            'sessions' => function($query) {
                $query->where('statut', 'termine')->orderByDesc('date_debut')->limit(5);
            },
        ])
        ->where('mentor_id', $mentor->id)
        ->where('statut', 'accepte')
        ->get()
        ->map(function($mentorat) {
            $etudiant = $mentorat->etudiant;
            
            // Calculer la progression moyenne
            $progression = $etudiant->parcoursInscrits()
                ->wherePivot('termine_a', null)
                ->avg('pivot.progression_pourcentage') ?? 0;
            
            // Dernière activité
            $derniereActivite = $etudiant->suiviTemps()
                ->orderByDesc('debut_a')
                ->first();
            
            return [
                'etudiant' => $etudiant,
                'progression_moyenne' => $progression,
                'sessions_total' => $mentorat->sessions->count(),
                'derniere_session' => $mentorat->sessions->first(),
                'derniere_activite' => $derniereActivite,
                'mentorat_depuis' => $mentorat->accepte_a,
            ];
        });
        
        $statistiques = [
            'total_etudiants' => $etudiants->count(),
            'sessions_ce_mois' => SessionMentorat::whereHas('mentorat', function($query) use ($mentor) {
                $query->where('mentor_id', $mentor->id);
            })
            ->whereMonth('date_debut', now()->month)
            ->count(),
            'feedback_donnes' => FeedbackMentor::where('mentor_id', $mentor->id)->count(),
            // Correction SQLite : calculer la durée totale en minutes en PHP
            'temps_total_mentorat' => (function() use ($mentor) {
                $sessions = SessionMentorat::whereHas('mentorat', function($query) use ($mentor) {
                        $query->where('mentor_id', $mentor->id);
                    })
                    ->where('statut', 'termine')
                    ->get(['date_debut', 'date_fin']);
                $total = 0;
                foreach ($sessions as $session) {
                    if ($session->date_debut && $session->date_fin) {
                        $debut = \Carbon\Carbon::parse($session->date_debut);
                        $fin = \Carbon\Carbon::parse($session->date_fin);
                        $total += $fin->diffInMinutes($debut);
                    }
                }
                return $total;
            })(),
        ];
        
        return $this->reponseSucces([
            'etudiants' => $etudiants,
            'statistiques' => $statistiques,
        ]);
    }
    
    /**
     * Planifier une session de mentorat
     */
    public function planifierSession(RequetePlanifierSession $requete, $mentoratId): JsonResponse
    {
        $mentorat = Mentorat::findOrFail($mentoratId);
        $mentor = $requete->user();
        
        // Vérifier que le mentorat appartient au mentor
        if ($mentorat->mentor_id !== $mentor->id) {
            return $this->reponseErreur('Ce mentorat ne vous appartient pas', 403);
        }
        
        // Vérifier que le mentorat est accepté
        if ($mentorat->statut !== 'accepte') {
            return $this->reponseErreur('Le mentorat n\'est pas actif', 400);
        }
        
        $donnees = $requete->validated();
        
        $session = SessionMentorat::create([
            'mentorat_id' => $mentoratId,
            'titre' => $donnees['titre'],
            'description' => $donnees['description'] ?? null,
            'date_debut' => $donnees['date_debut'],
            'date_fin' => $donnees['date_fin'],
            'statut' => 'planifie',
            'lien_visioconference' => $donnees['lien_visioconference'] ?? null,
        ]);
        
        // Notifier l'étudiant
        event(new \App\Events\SessionPlanifiee($session));
        
        return $this->reponseSucces([
            'message' => 'Session planifiée avec succès',
            'session' => new SessionMentoratRessource($session),
        ], 201);
    }
    
    /**
     * Annuler une session
     */
    public function annulerSession(Request $requete, $sessionId): JsonResponse
    {
        $session = SessionMentorat::with('mentorat')->findOrFail($sessionId);
        $mentor = $requete->user();
        
        // Vérifier que la session appartient au mentor
        if ($session->mentorat->mentor_id !== $mentor->id) {
            return $this->reponseErreur('Cette session ne vous appartient pas', 403);
        }
        
        // Vérifier que la session n'est pas déjà terminée
        if ($session->statut === 'termine') {
            return $this->reponseErreur('Cette session est déjà terminée', 400);
        }
        
        $session->update([
            'statut' => 'annule',
            'raison_annulation' => $requete->input('raison'),
        ]);
        
        // Notifier l'étudiant
        event(new \App\Events\SessionAnnulee($session));
        
        return $this->reponseSucces([
            'message' => 'Session annulée',
        ]);
    }
    
    /**
     * Donner un feedback après une session
     */
    public function donnerFeedbackSession(RequeteDonnerFeedback $requete, $sessionId): JsonResponse
    {
        $session = SessionMentorat::with('mentorat')->findOrFail($sessionId);
        $mentor = $requete->user();
        
        // Vérifier que la session appartient au mentor
        if ($session->mentorat->mentor_id !== $mentor->id) {
            return $this->reponseErreur('Cette session ne vous appartient pas', 403);
        }
        
        // Vérifier que la session est terminée
        if ($session->statut !== 'termine') {
            return $this->reponseErreur('La session n\'est pas encore terminée', 400);
        }
        
        $donnees = $requete->validated();
        
        $session->update([
            'notes' => $donnees['notes'] ?? null,
            'feedback_mentor' => $donnees['feedback'],
            'note_etudiant' => $donnees['note_etudiant'] ?? null,
        ]);
        
        // Créer un feedback formel si nécessaire
        if ($requete->has('creer_feedback_formel') && $requete->creer_feedback_formel) {
            FeedbackMentor::create([
                'mentor_id' => $mentor->id,
                'etudiant_id' => $session->mentorat->etudiant_id,
                'contenu' => $donnees['feedback'],
                'type' => 'session_mentorat',
                'points_positifs' => $donnees['points_positifs'] ?? null,
                'points_amelioration' => $donnees['points_amelioration'] ?? null,
                'note_generale' => $donnees['note_etudiant'] ?? null,
            ]);
        }
        
        return $this->reponseSucces([
            'message' => 'Feedback enregistré',
            'session' => new SessionMentoratRessource($session),
        ]);
    }
    
    /**
     * Donner un feedback sur du code
     */
    public function donnerFeedbackCode(RequeteDonnerFeedback $requete): JsonResponse
    {
        $mentor = $requete->user();
        $donnees = $requete->validated();
        
        $feedback = FeedbackMentor::create([
            'mentor_id' => $mentor->id,
            'etudiant_id' => $donnees['etudiant_id'],
            'projet_id' => $donnees['projet_id'] ?? null,
            'tache_id' => $donnees['tache_id'] ?? null,
            'contenu' => $donnees['contenu'],
            'type' => 'code',
            'points_positifs' => $donnees['points_positifs'] ?? null,
            'points_amelioration' => $donnees['points_amelioration'] ?? null,
            'note_generale' => $donnees['note_generale'] ?? null,
        ]);
        
        // Notifier l'étudiant
        event(new \App\Events\FeedbackDonne($feedback));
        
        return $this->reponseSucces([
            'message' => 'Feedback sur le code enregistré',
            'feedback' => $feedback,
        ], 201);
    }
    
    /**
     * Valider une compétence pour un étudiant
     */
    public function validerCompetence(Request $requete, $etudiantId): JsonResponse
    {
        $requete->validate([
            'competence_id' => 'required|exists:competences,id',
            'niveau_maitrise' => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string',
        ]);
        
        $mentor = $requete->user();
        $etudiant = Utilisateur::findOrFail($etudiantId);
        
        // Vérifier que l'étudiant est bien mentoré par ce mentor
        $mentorat = Mentorat::where('mentor_id', $mentor->id)
            ->where('etudiant_id', $etudiant->id)
            ->where('statut', 'accepte')
            ->first();
        
        if (!$mentorat) {
            return $this->reponseErreur('Cet étudiant n\'est pas sous votre mentorat', 403);
        }
        
        // Valider la compétence
        $etudiant->competences()->syncWithoutDetaching([
            $requete->competence_id => [
                'niveau_maitrise' => $requete->niveau_maitrise,
                'valide_a' => now(),
                'valide_par' => $mentor->id,
                'methode_validation' => 'mentor',
                'commentaire_validation' => $requete->commentaire,
            ],
        ]);
        
        // Accorder des points à l'étudiant
        $points = $requete->niveau_maitrise * 20; // 20 points par niveau
        $etudiant->ajouterPoints($points);
        
        return $this->reponseSucces([
            'message' => 'Compétence validée avec succès',
            'points_accordes' => $points,
        ]);
    }
    
    /**
     * Obtenir le planning du mentor
     */
    public function planning(Request $requete): JsonResponse
    {
        $mentor = $requete->user();
        $date = $requete->input('date', now()->format('Y-m-d'));
        
        $sessions = SessionMentorat::with(['mentorat.etudiant.profil'])
            ->whereHas('mentorat', function($query) use ($mentor) {
                $query->where('mentor_id', $mentor->id);
            })
            ->whereDate('date_debut', $date)
            ->orderBy('date_debut')
            ->get();
        
        $disponibilites = $this->obtenirDisponibilitesMentor($mentor, $date);
        
        return $this->reponseSucces([
            'sessions' => SessionMentoratRessource::collection($sessions),
            'disponibilites' => $disponibilites,
            'date' => $date,
        ]);
    }
    
    /**
     * Obtenir les statistiques du mentor
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
            'sessions_terminees' => SessionMentorat::whereHas('mentorat', function($query) use ($mentor) {
                $query->where('mentor_id', $mentor->id);
            })
            ->where('statut', 'termine')
            ->where('date_debut', '>=', $dateDebut)
            ->count(),
            
            // Correction SQLite : calculer la durée totale en minutes en PHP
            'temps_total_mentorat' => (function() use ($mentor, $dateDebut) {
                $sessions = SessionMentorat::whereHas('mentorat', function($query) use ($mentor) {
                        $query->where('mentor_id', $mentor->id);
                    })
                    ->where('statut', 'termine')
                    ->where('date_debut', '>=', $dateDebut)
                    ->get(['date_debut', 'date_fin']);
                $total = 0;
                foreach ($sessions as $session) {
                    if ($session->date_debut && $session->date_fin) {
                        $debut = \Carbon\Carbon::parse($session->date_debut);
                        $fin = \Carbon\Carbon::parse($session->date_fin);
                        $total += $fin->diffInMinutes($debut);
                    }
                }
                return $total;
            })(),
            
            'feedback_donnes' => FeedbackMentor::where('mentor_id', $mentor->id)
                ->where('created_at', '>=', $dateDebut)
                ->count(),
            
            'competences_validees' => \DB::table('competences_utilisateurs')
                ->where('valide_par', $mentor->id)
                ->where('valide_a', '>=', $dateDebut)
                ->count(),
            
            'nouveaux_etudiants' => Mentorat::where('mentor_id', $mentor->id)
                ->where('statut', 'accepte')
                ->where('accepte_a', '>=', $dateDebut)
                ->count(),
        ];
        
        $statistiques['temps_moyen_session'] = $statistiques['sessions_terminees'] > 0 
            ? round($statistiques['temps_total_mentorat'] / $statistiques['sessions_terminees'], 2)
            : 0;
        
        // Sessions terminées par mois (12 derniers mois)
        $sessionsParMois = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $mois = $date->format('M');
            $count = SessionMentorat::whereHas('mentorat', function($query) use ($mentor) {
                    $query->where('mentor_id', $mentor->id);
                })
                ->where('statut', 'termine')
                ->whereMonth('date_debut', $date->month)
                ->whereYear('date_debut', $date->year)
                ->count();
            $sessionsParMois[] = ['mois' => $mois, 'sessions' => $count];
        }

        // Compétences validées par type
        $competencesParType = \DB::table('competences_utilisateurs')
            ->join('competences', 'competences_utilisateurs.competence_id', '=', 'competences.id')
            ->select('competences.categorie as type', \DB::raw('count(*) as value'))
            ->where('competences_utilisateurs.valide_par', $mentor->id)
            ->groupBy('competences.categorie')
            ->get()
            ->map(function($row) {
                return ['name' => $row->type, 'value' => $row->value];
            });

        // Progression moyenne par mois (calculée dynamiquement)
        $progressionMoyenneParMois = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $mois = $date->format('M');
            $mentorats = \App\Models\Mentorat::where('mentor_id', $mentor->id)
                ->whereMonth('accepte_a', $date->month)
                ->whereYear('accepte_a', $date->year)
                ->where('statut', 'accepte')
                ->get();
            $progressions = [];
            foreach ($mentorats as $mentorat) {
                $etudiant = $mentorat->etudiant;
                if ($etudiant && method_exists($etudiant, 'parcoursInscrits')) {
                    $parcours = $etudiant->parcoursInscrits()->get();
                    foreach ($parcours as $p) {
                        if (isset($p->pivot->progression_pourcentage)) {
                            $progressions[] = $p->pivot->progression_pourcentage;
                        }
                    }
                }
            }
            $progression = count($progressions) > 0 ? array_sum($progressions) / count($progressions) : 0;
            $progressionMoyenneParMois[] = ['mois' => $mois, 'progression' => round($progression, 2)];
        }

        return $this->reponseSucces([
            'statistiques' => $statistiques,
            'sessions_par_mois' => $sessionsParMois,
            'competences_par_type' => $competencesParType,
            'progression_moyenne_par_mois' => $progressionMoyenneParMois,
            'periode' => $depuis,
        ]);
    }
    
    /**
     * Obtenir les disponibilités du mentor
     */
    private function obtenirDisponibilitesMentor($mentor, $date): array
    {
        // Ici, on pourrait intégrer avec un calendrier externe
        // Pour l'instant, retourner des créneaux par défaut
        return [
            ['debut' => '09:00', 'fin' => '10:00', 'disponible' => true],
            ['debut' => '10:30', 'fin' => '11:30', 'disponible' => true],
            ['debut' => '14:00', 'fin' => '15:00', 'disponible' => false],
            ['debut' => '16:00', 'fin' => '17:00', 'disponible' => true],
        ];
    }
}