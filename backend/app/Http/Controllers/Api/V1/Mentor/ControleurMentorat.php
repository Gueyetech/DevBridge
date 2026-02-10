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
use App\Models\Competence;
use App\Models\DisponibiliteMentor;
use App\Models\LogActivite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        event(new MentoratAccepte($mentorat));
        
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
                ->avg('progression_pourcentage') ?? 0;
            
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
        event(new SessionPlanifiee($session));
        
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
        event(new FeedbackDonne($feedback));
        
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
        $existe = DB::table('competences_utilisateurs')
            ->where('utilisateur_id', $etudiant->id)
            ->where('competence_id', $requete->competence_id)
            ->exists();

        if ($existe) {
            DB::table('competences_utilisateurs')
                ->where('utilisateur_id', $etudiant->id)
                ->where('competence_id', $requete->competence_id)
                ->update([
                    'niveau_maitrise' => $requete->niveau_maitrise,
                    'valide_a' => now(),
                    'valide_par' => $mentor->id,
                    'methode_validation' => 'mentor',
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('competences_utilisateurs')->insert([
                'id' => (string) Str::uuid(),
                'utilisateur_id' => $etudiant->id,
                'competence_id' => $requete->competence_id,
                'niveau_maitrise' => $requete->niveau_maitrise,
                'valide_a' => now(),
                'valide_par' => $mentor->id,
                'methode_validation' => 'mentor',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
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

            'competences_validees' => DB::table('competences_utilisateurs')
                ->where('valide_par', $mentor->id)
                ->where('valide_a', '>=', $dateDebut)
                ->count(),

            'nouveaux_etudiants' => Mentorat::where('mentor_id', $mentor->id)
                ->where('statut', 'accepte')
                ->where('accepte_a', '>=', $dateDebut)
                ->count(),

            // Ajout du total d'étudiants mentorés (tous statuts acceptés)
            'total_etudiants' => Mentorat::where('mentor_id', $mentor->id)
                ->where('statut', 'accepte')
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
        $competencesParType = DB::table('competences_utilisateurs')
            ->join('competences', 'competences_utilisateurs.competence_id', '=', 'competences.id')
            ->select('competences.categorie as type', DB::raw('count(*) as value'))
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
     * Obtenir les disponibilités du mentor (helper interne)
     */
    private function obtenirDisponibilitesMentor($mentor, $date): array
    {
        $jourSemaine = Carbon::parse($date)->dayOfWeek;

        $dispos = DisponibiliteMentor::where('mentor_id', $mentor->id)
            ->where('est_actif', true)
            ->where(function ($q) use ($jourSemaine, $date) {
                $q->where(function ($q2) use ($jourSemaine) {
                    $q2->where('recurrent', true)->where('jour_semaine', $jourSemaine);
                })->orWhere(function ($q2) use ($date) {
                    $q2->where('recurrent', false)->whereDate('date_specifique', $date);
                });
            })
            ->orderBy('heure_debut')
            ->get();

        return $dispos->map(fn ($d) => [
            'id' => $d->id,
            'debut' => $d->heure_debut,
            'fin' => $d->heure_fin,
            'type' => $d->type,
            'disponible' => true,
        ])->toArray();
    }

    // ==================== DÉTAIL ÉTUDIANT ====================

    /**
     * Détail d'un étudiant mentoré
     */
    public function etudiantDetail(Request $requete, $etudiantId): JsonResponse
    {
        $mentor = $requete->user();

        $mentorat = Mentorat::with(['etudiant.profil', 'etudiant.competences', 'sessions'])
            ->where('mentor_id', $mentor->id)
            ->where('etudiant_id', $etudiantId)
            ->where('statut', 'accepte')
            ->first();

        if (!$mentorat) {
            return $this->reponseErreur('Étudiant non trouvé dans vos mentorés', 404);
        }

        $etudiant = $mentorat->etudiant;

        $progression = $etudiant->parcoursInscrits()
            ->wherePivot('termine_a', null)
            ->avg('progression_pourcentage') ?? 0;

        $sessionsRecentes = $mentorat->sessions()
            ->orderByDesc('date_debut')
            ->limit(10)
            ->get();

        $competencesValidees = $etudiant->competences()
            ->wherePivot('valide_par', $mentor->id)
            ->get();

        return $this->reponseSucces([
            'etudiant' => $etudiant,
            'mentorat' => new MentoratRessource($mentorat),
            'progression_moyenne' => round($progression, 2),
            'sessions_recentes' => SessionMentoratRessource::collection($sessionsRecentes),
            'competences_validees' => $competencesValidees,
            'mentorat_depuis' => $mentorat->accepte_a,
        ]);
    }

    // ==================== TERMINER SESSION ====================

    /**
     * Terminer une session de mentorat
     */
    public function terminerSession(Request $requete, $sessionId): JsonResponse
    {
        $session = SessionMentorat::with('mentorat')->findOrFail($sessionId);
        $mentor = $requete->user();

        if ($session->mentorat->mentor_id !== $mentor->id) {
            return $this->reponseErreur('Cette session ne vous appartient pas', 403);
        }

        if ($session->statut === 'termine') {
            return $this->reponseErreur('Cette session est déjà terminée', 400);
        }

        if ($session->statut === 'annule') {
            return $this->reponseErreur('Impossible de terminer une session annulée', 400);
        }

        $session->update([
            'statut' => 'termine',
            'date_fin' => $requete->input('date_fin', now()),
            'notes' => $requete->input('notes'),
        ]);

        return $this->reponseSucces([
            'message' => 'Session terminée avec succès',
            'session' => new SessionMentoratRessource($session->fresh()),
        ]);
    }

    // ==================== COMPÉTENCES EN ATTENTE ====================

    /**
     * Compétences en attente de validation par le mentor
     */
    public function competencesEnAttente(Request $requete): JsonResponse
    {
        $mentor = $requete->user();

        // Récupérer les étudiants mentorés
        $etudiantIds = Mentorat::where('mentor_id', $mentor->id)
            ->where('statut', 'accepte')
            ->pluck('etudiant_id');

        // Compétences non encore validées par un mentor
        $enAttente = DB::table('competences_utilisateurs')
            ->join('competences', 'competences_utilisateurs.competence_id', '=', 'competences.id')
            ->join('utilisateurs', 'competences_utilisateurs.utilisateur_id', '=', 'utilisateurs.id')
            ->whereIn('competences_utilisateurs.utilisateur_id', $etudiantIds)
            ->whereNull('competences_utilisateurs.valide_a')
            ->select(
                'competences_utilisateurs.*',
                'competences.nom as competence_nom',
                'competences.categorie as competence_categorie',
                'utilisateurs.nom as etudiant_nom',
                'utilisateurs.prenom as etudiant_prenom',
                'utilisateurs.email as etudiant_email'
            )
            ->orderByDesc('competences_utilisateurs.created_at')
            ->get();

        return $this->reponseSucces([
            'en_attente' => $enAttente,
            'total' => $enAttente->count(),
        ]);
    }

    // ==================== PROFIL MENTOR ====================

    /**
     * Afficher le profil du mentor
     */
    public function profilMentor(Request $requete): JsonResponse
    {
        $mentor = $requete->user();
        $mentor->load(['profil', 'competences']);

        $stats = [
            'total_etudiants' => Mentorat::where('mentor_id', $mentor->id)->where('statut', 'accepte')->count(),
            'total_sessions' => SessionMentorat::whereHas('mentorat', fn($q) => $q->where('mentor_id', $mentor->id))->count(),
            'sessions_terminees' => SessionMentorat::whereHas('mentorat', fn($q) => $q->where('mentor_id', $mentor->id))->where('statut', 'termine')->count(),
            'feedbacks_donnes' => FeedbackMentor::where('mentor_id', $mentor->id)->count(),
            'competences_validees' => DB::table('competences_utilisateurs')->where('valide_par', $mentor->id)->count(),
        ];

        $disponibilites = DisponibiliteMentor::where('mentor_id', $mentor->id)
            ->where('est_actif', true)
            ->orderBy('jour_semaine')
            ->orderBy('heure_debut')
            ->get();

        return $this->reponseSucces([
            'mentor' => $mentor,
            'statistiques' => $stats,
            'disponibilites' => $disponibilites,
        ]);
    }

    /**
     * Mettre à jour le profil du mentor
     */
    public function mettreAJourProfil(Request $requete): JsonResponse
    {
        $requete->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'specialites' => 'nullable|array',
            'linkedin' => 'nullable|url|max:255',
            'github' => 'nullable|url|max:255',
            'site_web' => 'nullable|url|max:255',
        ]);

        $mentor = $requete->user();

        $mentor->update($requete->only(['nom', 'prenom']));

        if ($mentor->profil) {
            $mentor->profil->update($requete->only(['bio', 'specialites', 'linkedin', 'github', 'site_web']));
        }

        return $this->reponseSucces([
            'message' => 'Profil mis à jour avec succès',
            'mentor' => $mentor->fresh(['profil', 'competences']),
        ]);
    }

    /**
     * Mettre à jour la disponibilité globale (toggle) depuis le profil
     */
    public function mettreAJourDisponibilite(Request $requete, $disponibiliteId = null): JsonResponse
    {
        $mentor = $requete->user();

        // Si un ID est fourni, c'est une mise à jour d'un créneau spécifique
        if ($disponibiliteId) {
            $dispo = DisponibiliteMentor::where('mentor_id', $mentor->id)->findOrFail($disponibiliteId);

            $requete->validate([
                'jour' => 'sometimes|string|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
                'jour_semaine' => 'sometimes|integer|min:0|max:6',
                'heure_debut' => 'sometimes|date_format:H:i',
                'heure_fin' => 'sometimes|date_format:H:i|after:heure_debut',
                'type' => 'sometimes|string|in:en_ligne,presentiel,hybride',
                'est_actif' => 'sometimes|boolean',
                'recurrent' => 'sometimes|boolean',
                'note' => 'nullable|string|max:500',
            ]);

            $updateData = $requete->only([
                'jour_semaine', 'heure_debut', 'heure_fin', 'type', 'est_actif', 'recurrent', 'note',
            ]);
            if ($requete->has('jour') && !$requete->has('jour_semaine')) {
                $updateData['jour_semaine'] = $this->jourVersNumero($requete->jour);
            }
            $dispo->update($updateData);

            return $this->reponseSucces([
                'message' => 'Disponibilité mise à jour',
                'disponibilite' => $dispo->fresh(),
            ]);
        }

        // Sans ID — toggle global depuis le profil
        $requete->validate([
            'est_disponible' => 'required|boolean',
        ]);

        DisponibiliteMentor::where('mentor_id', $mentor->id)
            ->update(['est_actif' => $requete->est_disponible]);

        return $this->reponseSucces([
            'message' => 'Disponibilité globale mise à jour',
            'est_disponible' => $requete->est_disponible,
        ]);
    }

    /**
     * Lister les compétences du mentor
     */
    public function competencesMentor(Request $requete): JsonResponse
    {
        $mentor = $requete->user();

        $competences = $mentor->competences()->get();

        $toutesCompetences = Competence::orderBy('categorie')->orderBy('nom')->get();

        return $this->reponseSucces([
            'mes_competences' => $competences,
            'toutes_competences' => $toutesCompetences,
        ]);
    }

    /**
     * Ajouter une compétence au profil du mentor
     */
    public function ajouterCompetenceMentor(Request $requete): JsonResponse
    {
        $requete->validate([
            'competence_id' => 'required_without:nom|exists:competences,id',
            'nom' => 'required_without:competence_id|string|max:255',
            'niveau_maitrise' => 'sometimes|integer|min:1|max:5',
        ]);

        $mentor = $requete->user();

        // Si un nom est fourni au lieu d'un ID, trouver ou créer la compétence
        if ($requete->has('nom') && !$requete->has('competence_id')) {
            $competence = Competence::firstOrCreate(
                ['nom' => $requete->nom],
                ['slug' => Str::slug($requete->nom), 'categorie' => 'outils', 'description' => $requete->nom]
            );
            $competenceId = $competence->id;
        } else {
            $competenceId = $requete->competence_id;
        }

        // Vérifier si la compétence est déjà associée
        $existe = DB::table('competences_utilisateurs')
            ->where('utilisateur_id', $mentor->id)
            ->where('competence_id', $competenceId)
            ->exists();

        if ($existe) {
            DB::table('competences_utilisateurs')
                ->where('utilisateur_id', $mentor->id)
                ->where('competence_id', $competenceId)
                ->update([
                    'niveau_maitrise' => $requete->input('niveau_maitrise', 3),
                    'valide_a' => now(),
                    'valide_par' => $mentor->id,
                    'methode_validation' => 'mentor',
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('competences_utilisateurs')->insert([
                'id' => (string) Str::uuid(),
                'utilisateur_id' => $mentor->id,
                'competence_id' => $competenceId,
                'niveau_maitrise' => $requete->input('niveau_maitrise', 3),
                'valide_a' => now(),
                'valide_par' => $mentor->id,
                'methode_validation' => 'mentor',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->reponseSucces([
            'message' => 'Compétence ajoutée au profil',
            'competences' => $mentor->competences()->get(),
        ]);
    }

    // ==================== DISPONIBILITÉS CRUD ====================

    /**
     * Lister les disponibilités du mentor
     */
    public function disponibilites(Request $requete): JsonResponse
    {
        $mentor = $requete->user();

        $disponibilites = DisponibiliteMentor::where('mentor_id', $mentor->id)
            ->orderBy('jour_semaine')
            ->orderBy('heure_debut')
            ->get()
            ->map(function ($d) {
                $data = $d->toArray();
                $data['jour'] = $this->numeroVersJour($d->jour_semaine);
                return $data;
            });

        // Regrouper par jour
        $parJour = $disponibilites->groupBy('jour');

        return $this->reponseSucces([
            'disponibilites' => $disponibilites,
            'par_jour' => $parJour,
            'total' => $disponibilites->count(),
        ]);
    }

    /**
     * Mapping jour nom <-> numéro
     */
    private function jourVersNumero(?string $jour): ?int
    {
        $map = ['dimanche' => 0, 'lundi' => 1, 'mardi' => 2, 'mercredi' => 3, 'jeudi' => 4, 'vendredi' => 5, 'samedi' => 6];
        return $jour ? ($map[strtolower($jour)] ?? null) : null;
    }

    private function numeroVersJour(?int $numero): ?string
    {
        $map = [0 => 'dimanche', 1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi'];
        return $numero !== null ? ($map[$numero] ?? null) : null;
    }

    /**
     * Créer une disponibilité
     */
    public function creerDisponibilite(Request $requete): JsonResponse
    {
        $requete->validate([
            'jour' => 'required_without:jour_semaine|string|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'jour_semaine' => 'required_without:jour|integer|min:0|max:6',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'type' => 'sometimes|string|in:en_ligne,presentiel,hybride',
            'recurrent' => 'sometimes|boolean',
            'date_specifique' => 'nullable|date|after_or_equal:today',
            'note' => 'nullable|string|max:500',
        ]);

        $mentor = $requete->user();
        $jourSemaine = $requete->has('jour_semaine') ? (int) $requete->jour_semaine : $this->jourVersNumero($requete->jour);

        $dispo = DisponibiliteMentor::create([
            'mentor_id' => $mentor->id,
            'jour_semaine' => $jourSemaine,
            'heure_debut' => $requete->heure_debut,
            'heure_fin' => $requete->heure_fin,
            'type' => $requete->input('type', 'en_ligne'),
            'est_actif' => true,
            'recurrent' => $requete->input('recurrent', true),
            'date_specifique' => $requete->date_specifique,
            'note' => $requete->note,
        ]);

        $dispoData = $dispo->toArray();
        $dispoData['jour'] = $this->numeroVersJour($dispo->jour_semaine);

        return $this->reponseCree([
            'message' => 'Disponibilité créée avec succès',
            'disponibilite' => $dispoData,
        ]);
    }

    /**
     * Supprimer une disponibilité
     */
    public function supprimerDisponibilite(Request $requete, $disponibiliteId): JsonResponse
    {
        $mentor = $requete->user();

        $dispo = DisponibiliteMentor::where('mentor_id', $mentor->id)->findOrFail($disponibiliteId);
        $dispo->delete();

        return $this->reponseSupprime();
    }

    /**
     * Calendrier des disponibilités et sessions
     */
    public function calendrier(Request $requete): JsonResponse
    {
        $mentor = $requete->user();
        $mois = $requete->input('mois', now()->month);
        $annee = $requete->input('annee', now()->year);

        $debutMois = Carbon::create($annee, $mois, 1)->startOfMonth();
        $finMois = Carbon::create($annee, $mois, 1)->endOfMonth();

        // Sessions du mois
        $sessions = SessionMentorat::with(['mentorat.etudiant'])
            ->whereHas('mentorat', fn($q) => $q->where('mentor_id', $mentor->id))
            ->whereBetween('date_debut', [$debutMois, $finMois])
            ->orderBy('date_debut')
            ->get();

        // Disponibilités récurrentes
        $disponibilites = DisponibiliteMentor::where('mentor_id', $mentor->id)
            ->where('est_actif', true)
            ->get();

        // Construire la vue calendrier jour par jour
        $jours = [];
        $date = $debutMois->copy();
        while ($date->lte($finMois)) {
            $jourSemaine = $date->dayOfWeek;
            $dateStr = $date->format('Y-m-d');

            $dispoJour = $disponibilites->filter(function ($d) use ($jourSemaine, $dateStr) {
                if ($d->recurrent) {
                    return $d->jour_semaine === $jourSemaine;
                }
                return $d->date_specifique && $d->date_specifique->format('Y-m-d') === $dateStr;
            })->values();

            $sessionsJour = $sessions->filter(fn($s) => Carbon::parse($s->date_debut)->format('Y-m-d') === $dateStr)->values();

            $jours[] = [
                'date' => $dateStr,
                'jour_semaine' => $jourSemaine,
                'disponibilites' => $dispoJour,
                'sessions' => $sessionsJour,
            ];

            $date->addDay();
        }

        return $this->reponseSucces([
            'mois' => $mois,
            'annee' => $annee,
            'jours' => $jours,
            'total_sessions' => $sessions->count(),
        ]);
    }

    // ==================== RAPPORTS ====================

    /**
     * Rapport sur les étudiants mentorés
     */
    public function rapportEtudiants(Request $requete): JsonResponse
    {
        $mentor = $requete->user();

        $mentorats = Mentorat::with(['etudiant.profil', 'etudiant.competences', 'sessions'])
            ->where('mentor_id', $mentor->id)
            ->where('statut', 'accepte')
            ->get();

        $rapport = $mentorats->map(function ($m) {
            $etudiant = $m->etudiant;
            $sessionsTerminees = $m->sessions->where('statut', 'termine');

            $progression = $etudiant->parcoursInscrits()
                ->avg('progression_pourcentage') ?? 0;

            return [
                'etudiant' => [
                    'id' => $etudiant->id,
                    'nom' => $etudiant->nom,
                    'prenom' => $etudiant->prenom,
                    'email' => $etudiant->email,
                ],
                'mentorat_depuis' => $m->accepte_a,
                'sessions_total' => $m->sessions->count(),
                'sessions_terminees' => $sessionsTerminees->count(),
                'competences_validees' => $etudiant->competences()->wherePivot('valide_par', $mentor->id)->count(),
                'progression_moyenne' => round($progression, 2),
            ];
        });

        return $this->reponseSucces([
            'rapport' => $rapport,
            'total_etudiants' => $mentorats->count(),
        ]);
    }

    /**
     * Rapport sur les sessions de mentorat
     */
    public function rapportSessions(Request $requete): JsonResponse
    {
        $mentor = $requete->user();
        $periode = $requete->input('periode', '30days');

        $dateDebut = match ($periode) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };

        $sessions = SessionMentorat::with(['mentorat.etudiant'])
            ->whereHas('mentorat', fn($q) => $q->where('mentor_id', $mentor->id))
            ->where('date_debut', '>=', $dateDebut)
            ->orderByDesc('date_debut')
            ->get();

        $parStatut = $sessions->groupBy('statut')->map->count();

        $dureeTotale = 0;
        foreach ($sessions->where('statut', 'termine') as $s) {
            if ($s->date_debut && $s->date_fin) {
                $dureeTotale += Carbon::parse($s->date_fin)->diffInMinutes(Carbon::parse($s->date_debut));
            }
        }

        return $this->reponseSucces([
            'sessions' => $sessions,
            'statistiques' => [
                'total' => $sessions->count(),
                'par_statut' => $parStatut,
                'duree_totale_minutes' => $dureeTotale,
                'duree_moyenne_minutes' => $parStatut->get('termine', 0) > 0
                    ? round($dureeTotale / $parStatut->get('termine'), 2)
                    : 0,
            ],
            'periode' => $periode,
        ]);
    }

    /**
     * Rapport sur les compétences validées
     */
    public function rapportCompetences(Request $requete): JsonResponse
    {
        $mentor = $requete->user();

        $validations = DB::table('competences_utilisateurs')
            ->join('competences', 'competences_utilisateurs.competence_id', '=', 'competences.id')
            ->join('utilisateurs', 'competences_utilisateurs.utilisateur_id', '=', 'utilisateurs.id')
            ->where('competences_utilisateurs.valide_par', $mentor->id)
            ->select(
                'competences_utilisateurs.*',
                'competences.nom as competence_nom',
                'competences.categorie as competence_categorie',
                'utilisateurs.nom as etudiant_nom',
                'utilisateurs.prenom as etudiant_prenom'
            )
            ->orderByDesc('competences_utilisateurs.valide_a')
            ->get();

        $parCategorie = $validations->groupBy('competence_categorie')->map->count();
        $parEtudiant = $validations->groupBy('etudiant_nom')->map->count();

        return $this->reponseSucces([
            'validations' => $validations,
            'statistiques' => [
                'total' => $validations->count(),
                'par_categorie' => $parCategorie,
                'par_etudiant' => $parEtudiant,
            ],
        ]);
    }

    /**
     * Rapport d'activité du mentor
     */
    public function rapportActivite(Request $requete): JsonResponse
    {
        $mentor = $requete->user();
        $periode = $requete->input('periode', '30days');

        $dateDebut = match ($periode) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };

        // Sessions
        $sessionsTerminees = SessionMentorat::whereHas('mentorat', fn($q) => $q->where('mentor_id', $mentor->id))
            ->where('statut', 'termine')
            ->where('date_debut', '>=', $dateDebut)
            ->count();

        // Feedbacks
        $feedbacks = FeedbackMentor::where('mentor_id', $mentor->id)
            ->where('created_at', '>=', $dateDebut)
            ->count();

        // Compétences validées
        $competencesValidees = DB::table('competences_utilisateurs')
            ->where('valide_par', $mentor->id)
            ->where('valide_a', '>=', $dateDebut)
            ->count();

        // Demandes traitées
        $demandesAcceptees = Mentorat::where('mentor_id', $mentor->id)
            ->where('statut', 'accepte')
            ->where('accepte_a', '>=', $dateDebut)
            ->count();

        $demandesRefusees = Mentorat::where('mentor_id', $mentor->id)
            ->where('statut', 'refuse')
            ->where('updated_at', '>=', $dateDebut)
            ->count();

        // Activité par semaine
        $activiteParSemaine = [];
        for ($i = 3; $i >= 0; $i--) {
            $debutSemaine = now()->subWeeks($i)->startOfWeek();
            $finSemaine = now()->subWeeks($i)->endOfWeek();

            $activiteParSemaine[] = [
                'semaine' => $debutSemaine->format('d/m'),
                'sessions' => SessionMentorat::whereHas('mentorat', fn($q) => $q->where('mentor_id', $mentor->id))
                    ->where('statut', 'termine')
                    ->whereBetween('date_debut', [$debutSemaine, $finSemaine])
                    ->count(),
                'feedbacks' => FeedbackMentor::where('mentor_id', $mentor->id)
                    ->whereBetween('created_at', [$debutSemaine, $finSemaine])
                    ->count(),
            ];
        }

        return $this->reponseSucces([
            'resume' => [
                'sessions_terminees' => $sessionsTerminees,
                'feedbacks_donnes' => $feedbacks,
                'competences_validees' => $competencesValidees,
                'demandes_acceptees' => $demandesAcceptees,
                'demandes_refusees' => $demandesRefusees,
            ],
            'activite_par_semaine' => $activiteParSemaine,
            'periode' => $periode,
        ]);
    }
}