<?php

namespace App\Http\Controllers\Api\V1\Commun;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Resources\Api\V1\Apprentissage\CertificatRessource;
use App\Http\Resources\Api\V1\Apprentissage\RapportProgressionRessource;
use App\Models\Certificat;
use App\Models\RapportProgression;
use App\Models\Competence;
use App\Models\Utilisateur;
use App\Models\ParcoursApprentissage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ControleurTelechargement extends ControleurApiBase
{
    /**
     * Lister les ressources téléchargeables
     */
    public function ressources(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $ressources = [
            'certificats' => $this->obtenirCertificats($utilisateur),
            'rapports' => $this->obtenirRapports($utilisateur),
            'documents_guides' => $this->obtenirDocumentsGuides(),
            'modeles' => $this->obtenirModeles(),
        ];
        
        return $this->reponseSucces([
            'ressources' => $ressources,
            'espace_utilise' => $this->calculerEspaceUtilise($utilisateur),
            'limite_espace' => '500 MB', // Configuration
        ]);
    }
    
    /**
     * Lister les certificats de l'utilisateur
     */
    public function certificats(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $query = Certificat::with(['competence', 'parcours', 'validePar.profil'])
            ->where('utilisateur_id', $utilisateur->id);
        
        // Filtres
        if ($requete->has('type')) {
            $query->where('type', $requete->type);
        }
        
        if ($requete->has('competence_id')) {
            $query->where('competence_id', $requete->competence_id);
        }
        
        if ($requete->has('parcours_id')) {
            $query->where('parcours_id', $requete->parcours_id);
        }
        
        if ($requete->has('date_debut')) {
            $query->where('date_emission', '>=', $requete->date_debut);
        }
        
        if ($requete->has('date_fin')) {
            $query->where('date_emission', '<=', $requete->date_fin);
        }
        
        // Tri
        $certificats = $query->orderByDesc('date_emission')
            ->paginate($requete->input('per_page', 20));
        
        $statistiques = [
            'total' => Certificat::where('utilisateur_id', $utilisateur->id)->count(),
            'par_type' => Certificat::where('utilisateur_id', $utilisateur->id)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'dernier' => Certificat::where('utilisateur_id', $utilisateur->id)
                ->orderByDesc('date_emission')
                ->first(),
        ];
        
        return $this->reponseSucces([
            'certificats' => CertificatRessource::collection($certificats),
            'statistiques' => $statistiques,
            'meta' => [
                'total' => $certificats->total(),
                'par_page' => $certificats->perPage(),
                'page_courante' => $certificats->currentPage(),
            ],
        ]);
    }
    
    /**
     * Générer un certificat pour une compétence
     */
    public function genererCertificat(Request $requete, $competenceId): JsonResponse
    {
        $utilisateur = $requete->user();
        $competence = Competence::findOrFail($competenceId);
        
        // Vérifier que l'utilisateur possède cette compétence
        $possedeCompetence = $utilisateur->competences()
            ->where('competence_id', $competenceId)
            ->whereNotNull('valide_a')
            ->exists();
        
        if (!$possedeCompetence) {
            return $this->reponseErreur('Vous ne possédez pas cette compétence', 400);
        }
        
        // Vérifier si un certificat existe déjà
        $certificatExistant = Certificat::where('utilisateur_id', $utilisateur->id)
            ->where('competence_id', $competenceId)
            ->first();
        
        if ($certificatExistant) {
            return $this->reponseSucces([
                'message' => 'Certificat déjà généré',
                'certificat' => new CertificatRessource($certificatExistant),
                'url_download' => Storage::url($certificatExistant->chemin_fichier),
            ]);
        }
        
        // Générer le certificat
        $certificat = $this->creerCertificatCompetence($utilisateur, $competence);
        
        return $this->reponseSucces([
            'message' => 'Certificat généré avec succès',
            'certificat' => new CertificatRessource($certificat),
            'url_download' => Storage::url($certificat->chemin_fichier),
        ], 201);
    }
    
    /**
     * Télécharger un certificat
     */
    public function telechargerCertificat(Request $requete, $certificatId): JsonResponse
    {
        $utilisateur = $requete->user();
        $certificat = Certificat::findOrFail($certificatId);
        
        // Vérifier que le certificat appartient à l'utilisateur
        if ($certificat->utilisateur_id !== $utilisateur->id && !$utilisateur->est_administrateur) {
            return $this->reponseErreur('Accès non autorisé', 403);
        }
        
        // Vérifier que le fichier existe
        if (!Storage::exists($certificat->chemin_fichier)) {
            // Regénérer le certificat
            $certificat = $this->regenererCertificat($certificat);
        }
        
        // Enregistrer le téléchargement
        DB::table('telechargements_certificats')->insert([
            'certificat_id' => $certificatId,
            'utilisateur_id' => $utilisateur->id,
            'telecharge_a' => now(),
            'user_agent' => $requete->userAgent(),
            'adresse_ip' => $requete->ip(),
        ]);
        
        // Mettre à jour le compteur de téléchargements
        $certificat->increment('nombre_telechargements');
        
        return $this->reponseSucces([
            'message' => 'Certificat prêt au téléchargement',
            'url_download' => Storage::url($certificat->chemin_fichier),
            'nom_fichier' => $this->genererNomFichierCertificat($certificat),
            'dernier_telechargement' => now()->toISOString(),
            'total_telechargements' => $certificat->nombre_telechargements + 1,
        ]);
    }
    
    /**
     * Partager un certificat (générer un lien public temporaire)
     */
    public function partagerCertificat(Request $requete, $certificatId): JsonResponse
    {
        $utilisateur = $requete->user();
        $certificat = Certificat::findOrFail($certificatId);
        
        // Vérifier que le certificat appartient à l'utilisateur
        if ($certificat->utilisateur_id !== $utilisateur->id) {
            return $this->reponseErreur('Accès non autorisé', 403);
        }
        
        $requete->validate([
            'duree_validite' => 'sometimes|in:1,7,30,365',
            'mot_de_passe' => 'sometimes|string|min:4|max:20',
            'max_telechargements' => 'sometimes|integer|min:1|max:100',
        ]);
        
        // Générer un token de partage
        $token = bin2hex(random_bytes(32));
        $expireA = now()->addDays($requete->input('duree_validite', 7));
        
        DB::table('partages_certificats')->insert([
            'certificat_id' => $certificatId,
            'token' => $token,
            'utilisateur_id' => $utilisateur->id,
            'mot_de_passe' => $requete->mot_de_passe ? bcrypt($requete->mot_de_passe) : null,
            'max_telechargements' => $requete->input('max_telechargements', 10),
            'expire_a' => $expireA,
            'created_at' => now(),
        ]);
        
        $lienPartage = url('/certificats/partage/' . $token);
        
        return $this->reponseSucces([
            'message' => 'Certificat partagé avec succès',
            'lien_partage' => $lienPartage,
            'token' => $token,
            'expire_le' => $expireA->format('d/m/Y H:i'),
            'parametres' => [
                'avec_mot_de_passe' => !empty($requete->mot_de_passe),
                'max_telechargements' => $requete->input('max_telechargements', 10),
                'duree_validite' => $requete->input('duree_validite', 7) . ' jours',
            ],
        ]);
    }
    
    /**
     * Lister les rapports de progression
     */
    public function rapports(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $query = RapportProgression::with(['utilisateur.profil', 'parcours'])
            ->where('utilisateur_id', $utilisateur->id);
        
        // Filtres
        if ($requete->has('type')) {
            $query->where('type', $requete->type);
        }
        
        if ($requete->has('parcours_id')) {
            $query->where('parcours_id', $requete->parcours_id);
        }
        
        if ($requete->has('date_debut')) {
            $query->where('periode_debut', '>=', $requete->date_debut);
        }
        
        if ($requete->has('date_fin')) {
            $query->where('periode_fin', '<=', $requete->date_fin);
        }
        
        // Tri
        $rapports = $query->orderByDesc('created_at')
            ->paginate($requete->input('per_page', 20));
        
        $statistiques = [
            'total' => RapportProgression::where('utilisateur_id', $utilisateur->id)->count(),
            'dernier' => RapportProgression::where('utilisateur_id', $utilisateur->id)
                ->orderByDesc('created_at')
                ->first(),
            'espace_utilise' => $this->calculerEspaceRapports($utilisateur),
        ];
        
        return $this->reponseSucces([
            'rapports' => RapportProgressionRessource::collection($rapports),
            'statistiques' => $statistiques,
            'meta' => [
                'total' => $rapports->total(),
                'par_page' => $rapports->perPage(),
                'page_courante' => $rapports->currentPage(),
            ],
        ]);
    }
    
    /**
     * Générer un rapport de progression
     */
    public function genererRapportProgression(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $requete->validate([
            'type' => 'required|in:complet,parcours,competences,activite',
            'format' => 'required|in:pdf,excel,json',
            'periode_debut' => 'required|date',
            'periode_fin' => 'required|date|after_or_equal:periode_debut',
            'parcours_id' => 'nullable|exists:parcours_apprentissage,id',
            'inclure_graphiques' => 'sometimes|boolean',
            'inclure_recommandations' => 'sometimes|boolean',
        ]);
        
        // Vérifier la limite de rapports
        if (!$this->peutGenererRapport($utilisateur)) {
            return $this->reponseErreur('Limite de rapports atteinte. Maximum 5 rapports par jour.', 429);
        }
        
        // Collecter les données
        $donnees = $this->collecterDonneesRapport($utilisateur, $requete->all());
        
        // Générer le rapport
        $rapport = $this->creerRapport($utilisateur, $donnees, $requete->all());
        
        return $this->reponseSucces([
            'message' => 'Rapport généré avec succès',
            'rapport' => new RapportProgressionRessource($rapport),
            'url_download' => Storage::url($rapport->chemin_fichier),
            'statistiques' => $donnees['statistiques'],
        ], 201);
    }
    
    /**
     * Télécharger un rapport
     */
    public function telechargerRapport(Request $requete, $rapportId): JsonResponse
    {
        $utilisateur = $requete->user();
        $rapport = RapportProgression::findOrFail($rapportId);
        
        // Vérifier que le rapport appartient à l'utilisateur
        if ($rapport->utilisateur_id !== $utilisateur->id && !$utilisateur->est_administrateur) {
            return $this->reponseErreur('Accès non autorisé', 403);
        }
        
        // Vérifier que le fichier existe
        if (!Storage::exists($rapport->chemin_fichier)) {
            // Regénérer le rapport
            $rapport = $this->regenererRapport($rapport);
        }
        
        // Enregistrer le téléchargement
        DB::table('telechargements_rapports')->insert([
            'rapport_id' => $rapportId,
            'utilisateur_id' => $utilisateur->id,
            'telecharge_a' => now(),
            'user_agent' => $requete->userAgent(),
            'adresse_ip' => $requete->ip(),
        ]);
        
        // Mettre à jour le compteur de téléchargements
        $rapport->increment('nombre_telechargements');
        
        return $this->reponseSucces([
            'message' => 'Rapport prêt au téléchargement',
            'url_download' => Storage::url($rapport->chemin_fichier),
            'nom_fichier' => $this->genererNomFichierRapport($rapport),
            'format' => $rapport->format,
            'taille' => $this->formaterTaille(Storage::size($rapport->chemin_fichier)),
            'dernier_telechargement' => now()->toISOString(),
        ]);
    }
    
    /**
     * Générer un rapport CSV pour l'activité
     */
    public function genererRapportCSV(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $requete->validate([
            'type_donnees' => 'required|in:activite,progression,competences,projets',
            'periode_debut' => 'required|date',
            'periode_fin' => 'required|date|after_or_equal:periode_debut',
        ]);
        
        // Générer le CSV
        $csvData = $this->genererCSV($utilisateur, $requete->all());
        
        // Sauvegarder le fichier
        $nomFichier = 'rapport_' . $requete->type_donnees . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $chemin = 'rapports/csv/' . $nomFichier;
        
        Storage::put($chemin, $csvData);
        
        // Créer l'entrée dans la base
        $rapport = RapportProgression::create([
            'utilisateur_id' => $utilisateur->id,
            'type' => 'csv_' . $requete->type_donnees,
            'format' => 'csv',
            'chemin_fichier' => $chemin,
            'periode_debut' => $requete->periode_debut,
            'periode_fin' => $requete->periode_fin,
            'parametres' => $requete->all(),
            'taille_fichier' => Storage::size($chemin),
            'nombre_telechargements' => 0,
        ]);
        
        return $this->reponseSucces([
            'message' => 'Rapport CSV généré',
            'url_download' => Storage::url($chemin),
            'nom_fichier' => $nomFichier,
            'nombre_lignes' => substr_count($csvData, "\n"),
            'taille' => $this->formaterTaille(Storage::size($chemin)),
        ], 201);
    }
    
    /**
     * Exporter les données au format JSON
     */
    public function exporterDonneesJSON(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $requete->validate([
            'donnees_a_exporter' => 'required|array',
            'donnees_a_exporter.*' => 'in:profil,parcours,projets,competences,quiz,mentorat,badges',
            'format' => 'sometimes|in:json,json_pretty',
        ]);
        
        // Collecter les données
        $donnees = $this->collecterDonneesExport($utilisateur, $requete->donnees_a_exporter);
        
        // Formater le JSON
        $json = $requete->input('format', 'json_pretty') === 'json_pretty' 
            ? json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : json_encode($donnees, JSON_UNESCAPED_UNICODE);
        
        // Sauvegarder le fichier
        $nomFichier = 'export_donnees_' . now()->format('Y-m-d_H-i-s') . '.json';
        $chemin = 'exports/json/' . $nomFichier;
        
        Storage::put($chemin, $json);
        
        return $this->reponseSucces([
            'message' => 'Export JSON généré',
            'url_download' => Storage::url($chemin),
            'nom_fichier' => $nomFichier,
            'taille' => $this->formaterTaille(Storage::size($chemin)),
            'nombre_elements' => $this->compterElementsExport($donnees),
            'date_export' => now()->toISOString(),
        ]);
    }
    
    /**
     * Télécharger des modèles (CV, portfolio, etc.)
     */
    public function telechargerModele(Request $requete, $modeleId): JsonResponse
    {
        $modeles = [
            '1' => ['nom' => 'CV_Developpeur.pdf', 'chemin' => 'modeles/cv_developpeur.pdf', 'type' => 'cv'],
            '2' => ['nom' => 'Portfolio_Template.zip', 'chemin' => 'modeles/portfolio_template.zip', 'type' => 'portfolio'],
            '3' => ['nom' => 'README_Professionnel.md', 'chemin' => 'modeles/readme_professionnel.md', 'type' => 'documentation'],
            '4' => ['nom' => 'Contrat_Collaboration.pdf', 'chemin' => 'modeles/contrat_collaboration.pdf', 'type' => 'contrat'],
            '5' => ['nom' => 'Presentation_Projet.pptx', 'chemin' => 'modeles/presentation_projet.pptx', 'type' => 'presentation'],
        ];
        
        if (!isset($modeles[$modeleId])) {
            return $this->reponseErreur('Modèle non trouvé', 404);
        }
        
        $modele = $modeles[$modeleId];
        
        // Vérifier que le fichier existe
        if (!Storage::exists($modele['chemin'])) {
            return $this->reponseErreur('Fichier modèle non disponible', 404);
        }
        
        // Enregistrer le téléchargement
        DB::table('telechargements_modeles')->insert([
            'modele_id' => $modeleId,
            'utilisateur_id' => $requete->user()->id,
            'telecharge_a' => now(),
            'user_agent' => $requete->userAgent(),
        ]);
        
        return $this->reponseSucces([
            'message' => 'Modèle prêt au téléchargement',
            'url_download' => Storage::url($modele['chemin']),
            'nom_fichier' => $modele['nom'],
            'type' => $modele['type'],
            'taille' => $this->formaterTaille(Storage::size($modele['chemin'])),
        ]);
    }
    
    /**
     * Lister les téléchargements récents
     */
    public function historiqueTelechargements(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $historique = DB::table('telechargements_certificats')
            ->select('telecharge_a', 'certificat_id', DB::raw("'certificat' as type"))
            ->where('utilisateur_id', $utilisateur->id)
            ->unionAll(
                DB::table('telechargements_rapports')
                    ->select('telecharge_a', 'rapport_id', DB::raw("'rapport' as type"))
                    ->where('utilisateur_id', $utilisateur->id)
            )
            ->unionAll(
                DB::table('telechargements_modeles')
                    ->select('telecharge_a', 'modele_id', DB::raw("'modele' as type"))
                    ->where('utilisateur_id', $utilisateur->id)
            )
            ->orderByDesc('telecharge_a')
            ->limit(50)
            ->get();
        
        return $this->reponseSucces([
            'historique' => $historique,
            'total_telechargements' => DB::table('telechargements_certificats')
                ->where('utilisateur_id', $utilisateur->id)
                ->count() + DB::table('telechargements_rapports')
                ->where('utilisateur_id', $utilisateur->id)
                ->count() + DB::table('telechargements_modeles')
                ->where('utilisateur_id', $utilisateur->id)
                ->count(),
        ]);
    }
    
    // ==================== MÉTHODES PRIVÉES ====================
    
    /**
     * Obtenir les certificats de l'utilisateur
     */
    private function obtenirCertificats($utilisateur): array
    {
        return Certificat::where('utilisateur_id', $utilisateur->id)
            ->with(['competence', 'parcours'])
            ->orderByDesc('date_emission')
            ->limit(5)
            ->get()
            ->map(function($certificat) {
                return [
                    'id' => $certificat->id,
                    'nom' => $certificat->competence ? $certificat->competence->nom : 'Certificat de parcours',
                    'type' => $certificat->type,
                    'date_emission' => $certificat->date_emission,
                    'url' => route('api.v1.commun.telechargements.certificats.telecharger', $certificat->id),
                    'taille' => $this->formaterTaille(Storage::size($certificat->chemin_fichier)),
                ];
            })
            ->toArray();
    }
    
    /**
     * Obtenir les rapports de l'utilisateur
     */
    private function obtenirRapports($utilisateur): array
    {
        return RapportProgression::where('utilisateur_id', $utilisateur->id)
            ->with('parcours')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function($rapport) {
                return [
                    'id' => $rapport->id,
                    'type' => $rapport->type,
                    'periode' => $rapport->periode_debut . ' au ' . $rapport->periode_fin,
                    'format' => $rapport->format,
                    'url' => route('api.v1.commun.telechargements.rapports.telecharger', $rapport->id),
                    'taille' => $this->formaterTaille($rapport->taille_fichier),
                ];
            })
            ->toArray();
    }
    
    /**
     * Obtenir les documents guides
     */
    private function obtenirDocumentsGuides(): array
    {
        return [
            [
                'id' => 'guide_debutant',
                'nom' => 'Guide du Débutant.pdf',
                'description' => 'Guide complet pour bien commencer sur DevBridge',
                'taille' => '2.5 MB',
                'url' => '/guides/guide_debutant.pdf',
            ],
            [
                'id' => 'mentorat_guide',
                'nom' => 'Guide du Mentorat.pdf',
                'description' => 'Comment tirer le meilleur parti du mentorat',
                'taille' => '1.8 MB',
                'url' => '/guides/mentorat_guide.pdf',
            ],
            [
                'id' => 'projets_collaboratifs',
                'nom' => 'Guide des Projets Collaboratifs.pdf',
                'description' => 'Comment réussir vos projets en équipe',
                'taille' => '3.2 MB',
                'url' => '/guides/projets_collaboratifs.pdf',
            ],
        ];
    }
    
    /**
     * Obtenir les modèles disponibles
     */
    private function obtenirModeles(): array
    {
        return [
            [
                'id' => '1',
                'nom' => 'CV Développeur',
                'type' => 'cv',
                'format' => 'PDF',
                'taille' => '450 KB',
                'url' => route('api.v1.commun.telechargements.modeles.telecharger', 1),
            ],
            [
                'id' => '2',
                'nom' => 'Template Portfolio',
                'type' => 'portfolio',
                'format' => 'ZIP',
                'taille' => '5.2 MB',
                'url' => route('api.v1.commun.telechargements.modeles.telecharger', 2),
            ],
            [
                'id' => '3',
                'nom' => 'README Professionnel',
                'type' => 'documentation',
                'format' => 'MD',
                'taille' => '25 KB',
                'url' => route('api.v1.commun.telechargements.modeles.telecharger', 3),
            ],
        ];
    }
    
    /**
     * Calculer l'espace utilisé
     */
    private function calculerEspaceUtilise($utilisateur): string
    {
        $tailleCertificats = DB::table('certificats')
            ->where('utilisateur_id', $utilisateur->id)
            ->sum('taille_fichier');
        
        $tailleRapports = DB::table('rapports_progression')
            ->where('utilisateur_id', $utilisateur->id)
            ->sum('taille_fichier');
        
        $total = $tailleCertificats + $tailleRapports;
        
        return $this->formaterTaille($total);
    }
    
    /**
     * Calculer l'espace utilisé par les rapports
     */
    private function calculerEspaceRapports($utilisateur): string
    {
        $taille = DB::table('rapports_progression')
            ->where('utilisateur_id', $utilisateur->id)
            ->sum('taille_fichier');
        
        return $this->formaterTaille($taille);
    }
    
    /**
     * Formater la taille en octets
     */
    private function formaterTaille($octets): string
    {
        if ($octets == 0) return '0 B';
        
        $unites = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($octets >= 1024 && $i < count($unites) - 1) {
            $octets /= 1024;
            $i++;
        }
        
        return round($octets, 2) . ' ' . $unites[$i];
    }
    
    /**
     * Créer un certificat de compétence
     */
    private function creerCertificatCompetence($utilisateur, $competence): Certificat
    {
        // Générer le PDF du certificat
        $pdf = Pdf::loadView('certificats.competence', [
            'utilisateur' => $utilisateur,
            'competence' => $competence,
            'date' => now()->format('d/m/Y'),
            'numero_certificat' => 'CERT-' . strtoupper(uniqid()),
        ]);
        
        // Sauvegarder le fichier
        $nomFichier = 'certificat_' . $competence->slug . '_' . now()->format('Y-m-d') . '.pdf';
        $chemin = 'certificats/' . $utilisateur->id . '/' . $nomFichier;
        
        Storage::put($chemin, $pdf->output());
        
        // Créer l'entrée dans la base
        return Certificat::create([
            'utilisateur_id' => $utilisateur->id,
            'competence_id' => $competence->id,
            'type' => 'competence',
            'chemin_fichier' => $chemin,
            'numero_certificat' => 'CERT-' . strtoupper(uniqid()),
            'date_emission' => now(),
            'valide_par' => null, // Auto-validé
            'taille_fichier' => Storage::size($chemin),
            'nombre_telechargements' => 0,
        ]);
    }
    
    /**
     * Régénérer un certificat
     */
    private function regenererCertificat($certificat): Certificat
    {
        // Supprimer l'ancien fichier
        if (Storage::exists($certificat->chemin_fichier)) {
            Storage::delete($certificat->chemin_fichier);
        }
        
        // Regénérer le certificat
        if ($certificat->competence_id) {
            $competence = Competence::find($certificat->competence_id);
            $utilisateur = $certificat->utilisateur;
            
            $pdf = Pdf::loadView('certificats.competence', [
                'utilisateur' => $utilisateur,
                'competence' => $competence,
                'date' => $certificat->date_emission->format('d/m/Y'),
                'numero_certificat' => $certificat->numero_certificat,
            ]);
        } else {
            $parcours = ParcoursApprentissage::find($certificat->parcours_id);
            $utilisateur = $certificat->utilisateur;
            
            $pdf = Pdf::loadView('certificats.parcours', [
                'utilisateur' => $utilisateur,
                'parcours' => $parcours,
                'date' => $certificat->date_emission->format('d/m/Y'),
                'numero_certificat' => $certificat->numero_certificat,
            ]);
        }
        
        Storage::put($certificat->chemin_fichier, $pdf->output());
        
        return $certificat;
    }
    
    /**
     * Générer le nom de fichier pour un certificat
     */
    private function genererNomFichierCertificat($certificat): string
    {
        if ($certificat->competence_id) {
            $competence = Competence::find($certificat->competence_id);
            $nom = 'Certificat_' . $competence->nom . '_' . $certificat->date_emission->format('Y-m-d');
        } else {
            $parcours = ParcoursApprentissage::find($certificat->parcours_id);
            $nom = 'Certificat_' . $parcours->titre . '_' . $certificat->date_emission->format('Y-m-d');
        }
        
        // Nettoyer le nom de fichier
        $nom = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nom);
        
        return $nom . '.pdf';
    }
    
    /**
     * Vérifier si l'utilisateur peut générer un rapport
     */
    private function peutGenererRapport($utilisateur): bool
    {
        $rapportsAujourdhui = RapportProgression::where('utilisateur_id', $utilisateur->id)
            ->whereDate('created_at', today())
            ->count();
        
        return $rapportsAujourdhui < 5 || $utilisateur->est_administrateur;
    }
    
    /**
     * Collecter les données pour un rapport
     */
    private function collecterDonneesRapport($utilisateur, $parametres): array
    {
        $donnees = [
            'utilisateur' => $utilisateur->load('profil'),
            'periode' => [
                'debut' => $parametres['periode_debut'],
                'fin' => $parametres['periode_fin'],
            ],
            'statistiques' => [],
            'activites' => [],
            'progression' => [],
        ];
        
        // Collecter les statistiques selon le type
        switch ($parametres['type']) {
            case 'complet':
                $donnees['statistiques'] = $this->collecterStatistiquesCompletes($utilisateur, $parametres);
                break;
            case 'parcours':
                $donnees['statistiques'] = $this->collecterStatistiquesParcours($utilisateur, $parametres);
                break;
            case 'competences':
                $donnees['statistiques'] = $this->collecterStatistiquesCompetences($utilisateur, $parametres);
                break;
            case 'activite':
                $donnees['statistiques'] = $this->collecterStatistiquesActivite($utilisateur, $parametres);
                break;  
        }
    
        return $donnees;
    }
 }
        