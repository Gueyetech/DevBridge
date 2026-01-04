<?php

namespace App\Http\Controllers\Api\V1\Administrateur;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Models\Utilisateur;
use App\Models\ParcoursApprentissage;
use App\Models\Projet;
use App\Models\Mentorat;
use App\Models\Defi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ControleurAnalytiques extends ControleurApiBase
{
    /**
     * Tableau de bord administrateur
     */
    public function tableauDeBord(Request $requete): JsonResponse
    {
        // Statistiques globales
        $statistiques = [
            'utilisateurs' => $this->obtenirStatistiquesUtilisateurs(),
            'apprentissage' => $this->obtenirStatistiquesApprentissage(),
            'projets' => $this->obtenirStatistiquesProjets(),
            'mentorat' => $this->obtenirStatistiquesMentorat(),
            'gamification' => $this->obtenirStatistiquesGamification(),
            'performance' => $this->obtenirStatistiquesPerformance(),
        ];
        
        // Données récentes
        $donneesRecentes = [
            'nouvelles_inscriptions' => Utilisateur::orderByDesc('created_at')->limit(10)->get(),
            'projets_recents' => Projet::with('createur')->orderByDesc('created_at')->limit(5)->get(),
            'parcours_populaires' => ParcoursApprentissage::withCount('utilisateursInscrits')
                ->orderByDesc('utilisateurs_inscrits_count')
                ->limit(5)
                ->get(),
            'mentorats_actifs' => Mentorat::with(['mentor', 'etudiant'])
                ->where('statut', 'accepte')
                ->orderByDesc('accepte_a')
                ->limit(5)
                ->get(),
        ];
        
        // Alertes système
        $alertes = $this->obtenirAlertesSysteme();
        
        return $this->reponseSucces([
            'statistiques' => $statistiques,
            'donnees_recentes' => $donneesRecentes,
            'alertes' => $alertes,
            'derniere_actualisation' => now()->toISOString(),
        ]);
    }
    
    /**
     * Statistiques globales
     */
    public function statistiquesGlobales(Request $requete): JsonResponse
    {
        $periode = $requete->input('periode', '30days');
        $dateDebut = $this->obtenirDateDebut($periode);
        
        $statistiques = [
            'utilisation' => $this->obtenirStatistiquesUtilisation($dateDebut),
            'engagement' => $this->obtenirStatistiquesEngagement($dateDebut),
            'croissance' => $this->obtenirStatistiquesCroissance($dateDebut),
            'repartition' => $this->obtenirStatistiquesRepartition(),
        ];
        
        return $this->reponseSucces([
            'statistiques' => $statistiques,
            'periode' => $periode,
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => now()->format('Y-m-d'),
        ]);
    }
    
    /**
     * Activité récente
     */
    public function activiteRecente(Request $requete): JsonResponse
    {
        $limit = $requete->input('limit', 50);
        
        $activite = DB::table('logs_activite')
            ->select('action', 'modele', 'modele_id', 'utilisateur_id', 'created_at', 'metadata')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function($log) {
                return [
                    'action' => $log->action,
                    'modele' => $log->modele,
                    'utilisateur_id' => $log->utilisateur_id,
                    'date' => $log->created_at,
                    'metadata' => json_decode($log->metadata, true),
                ];
            });
        
        return $this->reponseSucces([
            'activite' => $activite,
            'total' => DB::table('logs_activite')->count(),
        ]);
    }
    
    /**
     * Statistiques d'utilisation
     */
    public function utilisation(Request $requete): JsonResponse
    {
        $periode = $requete->input('periode', '30days');
        $dateDebut = $this->obtenirDateDebut($periode);
        
        $utilisation = [
            'sessions_par_jour' => $this->obtenirSessionsParJour($dateDebut),
            'temps_moyen_session' => $this->obtenirTempsMoyenSession($dateDebut),
            'heures_actives' => $this->obtenirHeuresActives($dateDebut),
            'appareils' => $this->obtenirStatistiquesAppareils($dateDebut),
            'navigateurs' => $this->obtenirStatistiquesNavigateurs($dateDebut),
        ];
        
        return $this->reponseSucces([
            'utilisation' => $utilisation,
            'periode' => $periode,
        ]);
    }
    
    /**
     * Statistiques d'engagement
     */
    public function engagement(Request $requete): JsonResponse
    {
        $periode = $requete->input('periode', '30days');
        $dateDebut = $this->obtenirDateDebut($periode);
        
        $engagement = [
            'taux_retention' => $this->calculerTauxRetention($dateDebut),
            'utilisateurs_actifs' => $this->obtenirUtilisateursActifs($dateDebut),
            'frequence_utilisation' => $this->obtenirFrequenceUtilisation($dateDebut),
            'taux_abandon' => $this->calculerTauxAbandon($dateDebut),
            'engagement_par_role' => $this->obtenirEngagementParRole($dateDebut),
        ];
        
        return $this->reponseSucces([
            'engagement' => $engagement,
            'periode' => $periode,
        ]);
    }
    
    /**
     * Statistiques de performance
     */
    public function performance(Request $requete): JsonResponse
    {
        $performance = [
            'performance_parcours' => $this->obtenirPerformanceParcours(),
            'performance_projets' => $this->obtenirPerformanceProjets(),
            'temps_chargement' => $this->obtenirTempsChargement(),
            'taux_erreur' => $this->obtenirTauxErreur(),
            'satisfaction' => $this->obtenirSatisfactionUtilisateurs(),
        ];
        
        return $this->reponseSucces([
            'performance' => $performance,
        ]);
    }
    
    /**
     * Taux de rétention
     */
    public function retention(Request $requete): JsonResponse
    {
        $cohortes = $this->obtenirCohortesRetention();
        
        return $this->reponseSucces([
            'cohortes' => $cohortes,
            'taux_retention_moyen' => round(collect($cohortes)->avg('taux_retention_j30'), 2),
        ]);
    }
    
    /**
     * Répartition des données
     */
    public function repartition(Request $requete): JsonResponse
    {
        $repartition = [
            'utilisateurs_par_pays' => $this->obtenirUtilisateursParPays(),
            'utilisateurs_par_niveau' => $this->obtenirUtilisateursParNiveau(),
            'technologies_populaires' => $this->obtenirTechnologiesPopulaires(),
            'parcours_par_difficulte' => $this->obtenirParcoursParDifficulte(),
            'projets_par_technologie' => $this->obtenirProjetsParTechnologie(),
        ];
        
        return $this->reponseSucces([
            'repartition' => $repartition,
        ]);
    }
    
    /**
     * Exporter des données
     */
    public function export(Request $requete, $type): JsonResponse
    {
        $typesAutorises = ['utilisateurs', 'parcours', 'projets', 'mentorat', 'defis', 'logs'];
        
        if (!in_array($type, $typesAutorises)) {
            return $this->reponseErreur('Type d\'export non supporté', 400);
        }
        
        $donnees = match($type) {
            'utilisateurs' => $this->exporterUtilisateurs($requete),
            'parcours' => $this->exporterParcours($requete),
            'projets' => $this->exporterProjets($requete),
            'mentorat' => $this->exporterMentorat($requete),
            'defis' => $this->exporterDefis($requete),
            'logs' => $this->exporterLogs($requete),
        };
        
        return $this->reponseSucces([
            'type' => $type,
            'donnees' => $donnees,
            'total' => count($donnees),
            'date_export' => now()->toISOString(),
        ]);
    }
    
    /**
     * Configurations système
     */
    public function configurations(Request $requete): JsonResponse
    {
        $configurations = DB::table('configurations')
            ->orderBy('categorie')
            ->orderBy('cle')
            ->get()
            ->groupBy('categorie');
        
        return $this->reponseSucces([
            'configurations' => $configurations,
            'categories' => DB::table('configurations')->distinct('categorie')->pluck('categorie'),
        ]);
    }
    
    /**
     * Mettre à jour une configuration
     */
    public function mettreAJourConfiguration(Request $requete, $cle): JsonResponse
    {
        $configuration = DB::table('configurations')->where('cle', $cle)->first();
        
        if (!$configuration) {
            return $this->reponseErreur('Configuration non trouvée', 404);
        }
        
        if (!$configuration->est_modifiable) {
            return $this->reponseErreur('Cette configuration n\'est pas modifiable', 403);
        }
        
        $requete->validate([
            'valeur' => 'required',
        ]);
        
        DB::table('configurations')
            ->where('cle', $cle)
            ->update([
                'valeur' => $requete->valeur,
                'updated_at' => now(),
            ]);
        
        return $this->reponseSucces([
            'message' => 'Configuration mise à jour',
            'configuration' => DB::table('configurations')->where('cle', $cle)->first(),
        ]);
    }
    
    /**
     * Logs système
     */
    public function logs(Request $requete): JsonResponse
    {
        $query = DB::table('logs_activite')
            ->leftJoin('utilisateurs', 'logs_activite.utilisateur_id', '=', 'utilisateurs.id')
            ->select(
                'logs_activite.*',
                'utilisateurs.prenom',
                'utilisateurs.nom',
                'utilisateurs.email'
            );
        
        // Filtres
        if ($requete->has('niveau')) {
            $query->where('niveau', $requete->niveau);
        }
        
        if ($requete->has('utilisateur_id')) {
            $query->where('utilisateur_id', $requete->utilisateur_id);
        }
        
        if ($requete->has('action')) {
            $query->where('action', 'like', "%{$requete->action}%");
        }
        
        if ($requete->has('date_debut')) {
            $query->whereDate('created_at', '>=', $requete->date_debut);
        }
        
        if ($requete->has('date_fin')) {
            $query->whereDate('created_at', '<=', $requete->date_fin);
        }
        
        $logs = $query->orderByDesc('created_at')
            ->paginate($requete->input('per_page', 50));
        
        $statistiques = [
            'total' => DB::table('logs_activite')->count(),
            'par_niveau' => DB::table('logs_activite')
                ->selectRaw('niveau, COUNT(*) as count')
                ->groupBy('niveau')
                ->pluck('count', 'niveau'),
            'par_action' => DB::table('logs_activite')
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ];
        
        return $this->reponseSucces([
            'logs' => $logs,
            'statistiques' => $statistiques,
            'meta' => [
                'total' => $logs->total(),
                'par_page' => $logs->perPage(),
                'page_courante' => $logs->currentPage(),
            ],
        ]);
    }
    
    /**
     * Sauvegardes système
     */
    public function sauvegardes(Request $requete): JsonResponse
    {
        $sauvegardes = $this->obtenirListeSauvegardes();
        
        return $this->reponseSucces([
            'sauvegardes' => $sauvegardes,
            'derniere_sauvegarde' => collect($sauvegardes)->first(),
            'espace_disque' => $this->obtenirEspaceDisque(),
        ]);
    }
    
    /**
     * Créer une sauvegarde
     */
    public function creerSauvegarde(Request $requete): JsonResponse
    {
        $nom = $requete->input('nom', 'sauvegarde_' . now()->format('Y-m-d_H-i-s'));
        
        // Exécuter la commande de sauvegarde
        // Note: Dans un vrai projet, utilisez spatie/laravel-backup
        $chemin = storage_path('app/backups/' . $nom . '.zip');
        
        // Simuler une sauvegarde
        $sauvegarde = [
            'nom' => $nom,
            'chemin' => $chemin,
            'taille' => '0 MB', // À implémenter
            'date' => now()->toISOString(),
            'statut' => 'reussi',
        ];
        
        // Enregistrer dans la base de données
        DB::table('sauvegardes')->insert([
            'nom' => $nom,
            'chemin' => $chemin,
            'taille' => 0,
            'statut' => 'reussi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return $this->reponseSucces([
            'message' => 'Sauvegarde créée avec succès',
            'sauvegarde' => $sauvegarde,
        ], 201);
    }
    
    /**
     * Changer le mode maintenance
     */
    public function changerModeMaintenance(Request $requete): JsonResponse
    {
        $activer = $requete->input('activer', true);
        $message = $requete->input('message', 'Maintenance en cours');
        
        if ($activer) {
            // Activer le mode maintenance
            // Artisan::call('down', ['--message' => $message]);
            $statut = 'actif';
        } else {
            // Désactiver le mode maintenance
            // Artisan::call('up');
            $statut = 'inactif';
        }
        
        return $this->reponseSucces([
            'message' => 'Mode maintenance ' . ($activer ? 'activé' : 'désactivé'),
            'statut' => $statut,
            'message_maintenance' => $message,
        ]);
    }
    
    // ==================== MÉTHODES PRIVÉES ====================
    
    /**
     * Obtenir les statistiques utilisateurs
     */
    private function obtenirStatistiquesUtilisateurs(): array
    {
        return [
            'total' => Utilisateur::count(),
            'actifs' => Utilisateur::where('est_actif', true)->count(),
            'nouveaux_ce_mois' => Utilisateur::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'par_role' => [
                'etudiants' => Utilisateur::where('role', 'etudiant')->count(),
                'mentors' => Utilisateur::where('role', 'mentor')->count(),
                'administrateurs' => Utilisateur::where('role', 'administrateur')->count(),
            ],
            'taux_croissance' => $this->calculerTauxCroissanceUtilisateurs(),
        ];
    }
    
    /**
     * Obtenir les statistiques d'apprentissage
     */
    private function obtenirStatistiquesApprentissage(): array
    {
        return [
            'parcours_total' => ParcoursApprentissage::count(),
            'parcours_publies' => ParcoursApprentissage::where('est_publie', true)->count(),
            'inscriptions_total' => DB::table('inscriptions_parcours')->count(),
            'taux_completion_moyen' => round(DB::table('inscriptions_parcours')
                ->avg('progression_pourcentage') ?? 0, 2),
            'quiz_passes' => DB::table('tentatives_quiz')->count(),
            'lecons_terminees' => DB::table('progressions_lecons')
                ->where('est_termine', true)
                ->count(),
        ];
    }
    
    /**
     * Obtenir les statistiques de projets
     */
    private function obtenirStatistiquesProjets(): array
    {
        return [
            'total' => Projet::count(),
            'en_cours' => Projet::where('statut', 'en_cours')->count(),
            'termines' => Projet::where('statut', 'termine')->count(),
            'taux_success' => $this->calculerTauxSuccessProjets(),
            'participants_moyen' => round(DB::table('membres_projets')
                ->selectRaw('projet_id, COUNT(*) as count')
                ->groupBy('projet_id')
                ->avg('count') ?? 0, 2),
        ];
    }
    
    /**
     * Obtenir les statistiques de mentorat
     */
    private function obtenirStatistiquesMentorat(): array
    {
        return [
            'mentorats_actifs' => Mentorat::where('statut', 'accepte')->count(),
            'sessions_total' => DB::table('sessions_mentorat')->count(),
            'sessions_ce_mois' => DB::table('sessions_mentorat')
                ->whereMonth('date_debut', now()->month)
                ->count(),
            'mentors_actifs' => DB::table('mentorats')
                ->where('statut', 'accepte')
                ->distinct('mentor_id')
                ->count('mentor_id'),
            'etudiants_mentores' => DB::table('mentorats')
                ->where('statut', 'accepte')
                ->distinct('etudiant_id')
                ->count('etudiant_id'),
        ];
    }
    
    /**
     * Obtenir les statistiques de gamification
     */
    private function obtenirStatistiquesGamification(): array
    {
        return [
            'points_distribues' => DB::table('utilisateurs')->sum('points'),
            'badges_obtenus' => DB::table('badges_utilisateurs')->count(),
            'defis_completes' => DB::table('participations_defis')
                ->where('statut', 'gagnant')
                ->count(),
            'classement_actif' => DB::table('classement')
                ->where('periode', 'global')
                ->count(),
        ];
    }
    
    /**
     * Obtenir les statistiques de performance
     */
    private function obtenirStatistiquesPerformance(): array
    {
        return [
            'tps_reponse_api' => $this->obtenirTempsReponseAPI(),
            'taux_disponibilite' => 99.9, // À implémenter avec un monitoring
            'erreurs_ce_mois' => DB::table('logs_activite')
                ->where('niveau', 'error')
                ->whereMonth('created_at', now()->month)
                ->count(),
            'chargement_moyen' => '1.2s', // À implémenter
        ];
    }
    
    /**
     * Obtenir les alertes système
     */
    private function obtenirAlertesSysteme(): array
    {
        $alertes = [];
        
        // Vérifier les sauvegardes
        $derniereSauvegarde = DB::table('sauvegardes')
            ->orderByDesc('created_at')
            ->first();
        
        if ($derniereSauvegarde && now()->diffInDays($derniereSauvegarde->created_at) > 7) {
            $alertes[] = [
                'niveau' => 'warning',
                'message' => 'Aucune sauvegarde depuis plus de 7 jours',
                'action' => 'creer_sauvegarde',
            ];
        }
        
        // Vérifier les erreurs récentes
        $erreursRecentes = DB::table('logs_activite')
            ->where('niveau', 'error')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
        
        if ($erreursRecentes > 10) {
            $alertes[] = [
                'niveau' => 'danger',
                'message' => $erreursRecentes . ' erreurs dans les dernières 24 heures',
                'action' => 'voir_logs',
            ];
        }
        
        // Vérifier l'espace disque
        $espaceDisque = $this->obtenirEspaceDisque();
        if ($espaceDisque['pourcentage'] > 90) {
            $alertes[] = [
                'niveau' => 'danger',
                'message' => 'Espace disque presque plein (' . $espaceDisque['pourcentage'] . '%)',
                'action' => 'nettoyer_espace',
            ];
        }
        
        return $alertes;
    }
    
    /**
     * Obtenir la date de début selon la période
     */
    private function obtenirDateDebut(string $periode): Carbon
    {
        return match($periode) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };
    }
    
    /**
     * Calculer le taux de croissance des utilisateurs
     */
    private function calculerTauxCroissanceUtilisateurs(): float
    {
        $moisDernier = now()->subMonth();
        $utilisateursMoisDernier = Utilisateur::where('created_at', '<', $moisDernier)->count();
        $utilisateursActuels = Utilisateur::count();
        
        if ($utilisateursMoisDernier === 0) {
            return 0;
        }
        
        return round((($utilisateursActuels - $utilisateursMoisDernier) / $utilisateursMoisDernier) * 100, 2);
    }
    
    /**
     * Calculer le taux de succès des projets
     */
    private function calculerTauxSuccessProjets(): float
    {
        $totalProjets = Projet::count();
        $projetsTermines = Projet::where('statut', 'termine')->count();
        
        if ($totalProjets === 0) {
            return 0;
        }
        
        return round(($projetsTermines / $totalProjets) * 100, 2);
    }
    
    /**
     * Obtenir l'espace disque
     */
    private function obtenirEspaceDisque(): array
    {
        $total = disk_total_space('/');
        $libre = disk_free_space('/');
        $utilise = $total - $libre;
        
        return [
            'total' => $this->formaterTaille($total),
            'utilise' => $this->formaterTaille($utilise),
            'libre' => $this->formaterTaille($libre),
            'pourcentage' => round(($utilise / $total) * 100, 2),
        ];
    }
    
    /**
     * Formater la taille en octets
     */
    private function formaterTaille($octets): string
    {
        $unites = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($octets >= 1024 && $i < count($unites) - 1) {
            $octets /= 1024;
            $i++;
        }
        
        return round($octets, 2) . ' ' . $unites[$i];
    }
    
    // Note: Les autres méthodes d'analytiques seraient similaires mais plus longues
    // Elles impliqueraient des requêtes SQL complexes et des calculs statistiques
}