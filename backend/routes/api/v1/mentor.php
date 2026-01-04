<?php

use App\Http\Controllers\Api\V1\Mentor\{
    ControleurMentorat,
    ControleurFeedback,
    ControleurRevisionCode
};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:mentor'])->prefix('mentor')->group(function () {
    
    // ==================== TABLEAU DE BORD MENTOR ====================
    Route::prefix('tableau-de-bord')->group(function () {
        Route::get('/', [ControleurMentorat::class, 'statistiques'])
            ->name('api.v1.mentor.tableau-de-bord.index');
    });
    
    // ==================== MENTORAT ====================
    Route::prefix('mentorat')->group(function () {
        // Demandes de mentorat
        Route::get('/demandes', [ControleurMentorat::class, 'demandes'])
            ->name('api.v1.mentor.mentorat.demandes');
        
        Route::post('/demandes/{mentoratId}/accepter', [ControleurMentorat::class, 'accepterDemande'])
            ->name('api.v1.mentor.mentorat.demandes.accepter');
        
        Route::post('/demandes/{mentoratId}/refuser', [ControleurMentorat::class, 'refuserDemande'])
            ->name('api.v1.mentor.mentorat.demandes.refuser');
        
        // Étudiants
        Route::get('/etudiants', [ControleurMentorat::class, 'etudiants'])
            ->name('api.v1.mentor.mentorat.etudiants');
        
        Route::get('/etudiants/{etudiantId}', [ControleurMentorat::class, 'etudiantDetail'])
            ->name('api.v1.mentor.mentorat.etudiants.detail');
        
        // Sessions
        Route::prefix('sessions')->group(function () {
            Route::get('/', [ControleurMentorat::class, 'planning'])
                ->name('api.v1.mentor.mentorat.sessions.index');
            
            Route::post('/', [ControleurMentorat::class, 'planifierSession'])
                ->name('api.v1.mentor.mentorat.sessions.planifier');
            
            Route::post('/{sessionId}/annuler', [ControleurMentorat::class, 'annulerSession'])
                ->name('api.v1.mentor.mentorat.sessions.annuler');
            
            Route::post('/{sessionId}/terminer', [ControleurMentorat::class, 'terminerSession'])
                ->name('api.v1.mentor.mentorat.sessions.terminer');
            
            Route::post('/{sessionId}/feedback', [ControleurMentorat::class, 'donnerFeedbackSession'])
                ->name('api.v1.mentor.mentorat.sessions.feedback');
        });
        
        // Validation de compétences
        Route::post('/etudiants/{etudiantId}/competences/valider', 
            [ControleurMentorat::class, 'validerCompetence'])
            ->name('api.v1.mentor.mentorat.competences.valider');
        
        Route::get('/competences/en-attente', [ControleurMentorat::class, 'competencesEnAttente'])
            ->name('api.v1.mentor.mentorat.competences.en-attente');
    });
    
    // ==================== FEEDBACK ====================
    Route::prefix('feedback')->group(function () {
        Route::get('/', [ControleurFeedback::class, 'index'])
            ->name('api.v1.mentor.feedback.index');
        
        Route::post('/code', [ControleurFeedback::class, 'donnerFeedbackCode'])
            ->name('api.v1.mentor.feedback.code');
        
        Route::post('/projet', [ControleurFeedback::class, 'donnerFeedbackProjet'])
            ->name('api.v1.mentor.feedback.projet');
        
        Route::get('/{feedbackId}', [ControleurFeedback::class, 'afficher'])
            ->name('api.v1.mentor.feedback.afficher');
        
        Route::put('/{feedbackId}', [ControleurFeedback::class, 'mettreAJour'])
            ->name('api.v1.mentor.feedback.mettre-a-jour');
        
        Route::delete('/{feedbackId}', [ControleurFeedback::class, 'supprimer'])
            ->name('api.v1.mentor.feedback.supprimer');
        
        // Feedback sur des projets spécifiques
        Route::prefix('projets/{projetId}')->group(function () {
            Route::get('/', [ControleurFeedback::class, 'feedbackProjet'])
                ->name('api.v1.mentor.feedback.projets.index');
            
            Route::post('/', [ControleurFeedback::class, 'creerFeedbackProjet'])
                ->name('api.v1.mentor.feedback.projets.creer');
        });
    });
    
    // ==================== REVISION DE CODE ====================
    Route::prefix('revision-code')->group(function () {
        Route::get('/demandes', [ControleurRevisionCode::class, 'demandes'])
            ->name('api.v1.mentor.revision-code.demandes');
        
        Route::get('/demandes/{demandeId}', [ControleurRevisionCode::class, 'demandeDetail'])
            ->name('api.v1.mentor.revision-code.demandes.detail');
        
        Route::post('/demandes/{demandeId}/accepter', [ControleurRevisionCode::class, 'accepterDemande'])
            ->name('api.v1.mentor.revision-code.demandes.accepter');
        
        Route::post('/demandes/{demandeId}/refuser', [ControleurRevisionCode::class, 'refuserDemande'])
            ->name('api.v1.mentor.revision-code.demandes.refuser');
        
        Route::post('/demandes/{demandeId}/reviser', [ControleurRevisionCode::class, 'reviserCode'])
            ->name('api.v1.mentor.revision-code.demandes.reviser');
        
        Route::get('/historique', [ControleurRevisionCode::class, 'historique'])
            ->name('api.v1.mentor.revision-code.historique');
    });
    
    // ==================== PROFIL MENTOR ====================
    Route::prefix('profil')->group(function () {
        Route::get('/', [ControleurMentorat::class, 'profilMentor'])
            ->name('api.v1.mentor.profil.afficher');
        
        Route::put('/', [ControleurMentorat::class, 'mettreAJourProfil'])
            ->name('api.v1.mentor.profil.mettre-a-jour');
        
        Route::put('/disponibilite', [ControleurMentorat::class, 'mettreAJourDisponibilite'])
            ->name('api.v1.mentor.profil.disponibilite');
        
        Route::get('/competences', [ControleurMentorat::class, 'competencesMentor'])
            ->name('api.v1.mentor.profil.competences');
        
        Route::post('/competences', [ControleurMentorat::class, 'ajouterCompetenceMentor'])
            ->name('api.v1.mentor.profil.competences.ajouter');
    });
    
    // ==================== DISPONIBILITÉS ====================
    Route::prefix('disponibilites')->group(function () {
        Route::get('/', [ControleurMentorat::class, 'disponibilites'])
            ->name('api.v1.mentor.disponibilites.index');
        
        Route::post('/', [ControleurMentorat::class, 'creerDisponibilite'])
            ->name('api.v1.mentor.disponibilites.creer');
        
        Route::put('/{disponibiliteId}', [ControleurMentorat::class, 'mettreAJourDisponibilite'])
            ->name('api.v1.mentor.disponibilites.mettre-a-jour');
        
        Route::delete('/{disponibiliteId}', [ControleurMentorat::class, 'supprimerDisponibilite'])
            ->name('api.v1.mentor.disponibilites.supprimer');
        
        Route::get('/calendrier', [ControleurMentorat::class, 'calendrier'])
            ->name('api.v1.mentor.disponibilites.calendrier');
    });
    
    // ==================== RAPPORTS ====================
    Route::prefix('rapports')->group(function () {
        Route::get('/etudiants', [ControleurMentorat::class, 'rapportEtudiants'])
            ->name('api.v1.mentor.rapports.etudiants');
        
        Route::get('/sessions', [ControleurMentorat::class, 'rapportSessions'])
            ->name('api.v1.mentor.rapports.sessions');
        
        Route::get('/competences', [ControleurMentorat::class, 'rapportCompetences'])
            ->name('api.v1.mentor.rapports.competences');
        
        Route::get('/activite', [ControleurMentorat::class, 'rapportActivite'])
            ->name('api.v1.mentor.rapports.activite');
    });
});