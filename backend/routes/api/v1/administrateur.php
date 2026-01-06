<?php

use App\Http\Controllers\Api\V1\Administrateur\{
    ControleurGestionUtilisateurs,
    ControleurGestionContenu,
    ControleurAnalytiques
};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:administrateur'])->prefix('admin')->group(function () {
    
    // ==================== TABLEAU DE BORD ADMIN ====================
    Route::prefix('tableau-de-bord')->group(function () {
        Route::get('/', [ControleurAnalytiques::class, 'tableauDeBord'])
            ->name('api.v1.admin.tableau-de-bord.index');
        
        Route::get('/statistiques', [ControleurAnalytiques::class, 'statistiquesGlobales'])
            ->name('api.v1.admin.tableau-de-bord.statistiques');
        
        Route::get('/activite-recente', [ControleurAnalytiques::class, 'activiteRecente'])
            ->name('api.v1.admin.tableau-de-bord.activite-recente');
    });
    
    // ==================== GESTION DES UTILISATEURS ====================
    Route::prefix('utilisateurs')->group(function () {
        Route::get('/', [ControleurGestionUtilisateurs::class, 'index'])
            ->name('api.v1.admin.utilisateurs.index');
        
        Route::post('/', [ControleurGestionUtilisateurs::class, 'creer'])
            ->name('api.v1.admin.utilisateurs.creer');
        
        Route::get('/{id}', [ControleurGestionUtilisateurs::class, 'afficher'])
            ->name('api.v1.admin.utilisateurs.afficher');
        
        Route::put('/{id}', [ControleurGestionUtilisateurs::class, 'mettreAJour'])
            ->name('api.v1.admin.utilisateurs.mettre-a-jour');
        
        Route::delete('/{id}', [ControleurGestionUtilisateurs::class, 'supprimer'])
            ->name('api.v1.admin.utilisateurs.supprimer');
        
        Route::post('/{id}/restaurer', [ControleurGestionUtilisateurs::class, 'restaurer'])
            ->name('api.v1.admin.utilisateurs.restaurer');
        
        Route::delete('/{id}/forcer', [ControleurGestionUtilisateurs::class, 'forcerSuppression'])
            ->name('api.v1.admin.utilisateurs.forcer-suppression');
        
        Route::post('/{id}/desactiver', [ControleurGestionUtilisateurs::class, 'desactiver'])
            ->name('api.v1.admin.utilisateurs.desactiver');
        
        Route::post('/{id}/reactiver', [ControleurGestionUtilisateurs::class, 'reactiver'])
            ->name('api.v1.admin.utilisateurs.reactiver');
        
        Route::post('/{id}/changer-role', [ControleurGestionUtilisateurs::class, 'changerRole'])
            ->name('api.v1.admin.utilisateurs.changer-role');
        
        Route::post('/{id}/reinitialiser-mot-de-passe', 
            [ControleurGestionUtilisateurs::class, 'reinitialiserMotDePasse'])
            ->name('api.v1.admin.utilisateurs.reinitialiser-mot-de-passe');
        
        // Statistiques utilisateurs
        Route::get('/statistiques/inscriptions', 
            [ControleurGestionUtilisateurs::class, 'statistiquesInscriptions'])
            ->name('api.v1.admin.utilisateurs.statistiques.inscriptions');
        
        Route::get('/statistiques/activite', 
            [ControleurGestionUtilisateurs::class, 'statistiquesActivite'])
            ->name('api.v1.admin.utilisateurs.statistiques.activite');
        
        Route::get('/statistiques/retention', 
            [ControleurGestionUtilisateurs::class, 'statistiquesRetention'])
            ->name('api.v1.admin.utilisateurs.statistiques.retention');
    });
    
    // ==================== GESTION DES PARCOURS ====================
    Route::prefix('parcours')->group(function () {
        Route::get('/', [ControleurGestionContenu::class, 'parcoursIndex'])
            ->name('api.v1.admin.parcours.index');
        
        Route::post('/', [ControleurGestionContenu::class, 'parcoursCreer'])
            ->name('api.v1.admin.parcours.creer');
        
        Route::get('/{id}', [ControleurGestionContenu::class, 'parcoursAfficher'])
            ->name('api.v1.admin.parcours.afficher');
        
        Route::put('/{id}', [ControleurGestionContenu::class, 'parcoursMettreAJour'])
            ->name('api.v1.admin.parcours.mettre-a-jour');
        
        Route::delete('/{id}', [ControleurGestionContenu::class, 'parcoursSupprimer'])
            ->name('api.v1.admin.parcours.supprimer');
        
        Route::post('/{id}/publier', [ControleurGestionContenu::class, 'parcoursPublier'])
            ->name('api.v1.admin.parcours.publier');
        
        Route::post('/{id}/depublier', [ControleurGestionContenu::class, 'parcoursDepublier'])
            ->name('api.v1.admin.parcours.depublier');
        
        // Modules
        Route::prefix('/{parcoursId}/modules')->group(function () {
            Route::get('/', [ControleurGestionContenu::class, 'modulesIndex'])
                ->name('api.v1.admin.parcours.modules.index');
            
            Route::post('/', [ControleurGestionContenu::class, 'moduleCreer'])
                ->name('api.v1.admin.parcours.modules.creer');
            
            Route::get('/{moduleId}', [ControleurGestionContenu::class, 'moduleAfficher'])
                ->name('api.v1.admin.parcours.modules.afficher');
            
            Route::put('/{moduleId}', [ControleurGestionContenu::class, 'moduleMettreAJour'])
                ->name('api.v1.admin.parcours.modules.mettre-a-jour');
            
            Route::delete('/{moduleId}', [ControleurGestionContenu::class, 'moduleSupprimer'])
                ->name('api.v1.admin.parcours.modules.supprimer');
            
            // Leçons
            Route::prefix('/{moduleId}/lecons')->group(function () {
                Route::get('/', [ControleurGestionContenu::class, 'leconsIndex'])
                    ->name('api.v1.admin.parcours.modules.lecons.index');
                
                Route::post('/', [ControleurGestionContenu::class, 'leconCreer'])
                    ->name('api.v1.admin.parcours.modules.lecons.creer');
                
                Route::get('/{leconId}', [ControleurGestionContenu::class, 'leconAfficher'])
                    ->name('api.v1.admin.parcours.modules.lecons.afficher');
                
                Route::put('/{leconId}', [ControleurGestionContenu::class, 'leconMettreAJour'])
                    ->name('api.v1.admin.parcours.modules.lecons.mettre-a-jour');
                
                Route::delete('/{leconId}', [ControleurGestionContenu::class, 'leconSupprimer'])
                    ->name('api.v1.admin.parcours.modules.lecons.supprimer');
            });
            
            // Quiz
            Route::prefix('/{moduleId}/quiz')->group(function () {
                Route::get('/', [ControleurGestionContenu::class, 'quizIndex'])
                    ->name('api.v1.admin.parcours.modules.quiz.index');
                
                Route::post('/', [ControleurGestionContenu::class, 'quizCreer'])
                    ->name('api.v1.admin.parcours.modules.quiz.creer');
                
                Route::get('/{quizId}', [ControleurGestionContenu::class, 'quizAfficher'])
                    ->name('api.v1.admin.parcours.modules.quiz.afficher');
                
                Route::put('/{quizId}', [ControleurGestionContenu::class, 'quizMettreAJour'])
                    ->name('api.v1.admin.parcours.modules.quiz.mettre-a-jour');
                
                Route::delete('/{quizId}', [ControleurGestionContenu::class, 'quizSupprimer'])
                    ->name('api.v1.admin.parcours.modules.quiz.supprimer');
                
                // Questions
                Route::prefix('/{quizId}/questions')->group(function () {
                    Route::get('/', [ControleurGestionContenu::class, 'questionsIndex'])
                        ->name('api.v1.admin.parcours.modules.quiz.questions.index');
                    
                    Route::post('/', [ControleurGestionContenu::class, 'questionCreer'])
                        ->name('api.v1.admin.parcours.modules.quiz.questions.creer');
                    
                    Route::get('/{questionId}', [ControleurGestionContenu::class, 'questionAfficher'])
                        ->name('api.v1.admin.parcours.modules.quiz.questions.afficher');
                    
                    Route::put('/{questionId}', [ControleurGestionContenu::class, 'questionMettreAJour'])
                        ->name('api.v1.admin.parcours.modules.quiz.questions.mettre-a-jour');
                    
                    Route::delete('/{questionId}', [ControleurGestionContenu::class, 'questionSupprimer'])
                        ->name('api.v1.admin.parcours.modules.quiz.questions.supprimer');
                });
            });
        });
        
        // Statistiques parcours
        Route::get('/{id}/statistiques', [ControleurGestionContenu::class, 'parcoursStatistiques'])
            ->name('api.v1.admin.parcours.statistiques');
    });
    
    // ==================== GESTION DES PROJETS ====================
    Route::prefix('projets')->group(function () {
        Route::get('/', [ControleurGestionContenu::class, 'projetsIndex'])
            ->name('api.v1.admin.projets.index');
        
        Route::get('/{id}', [ControleurGestionContenu::class, 'projetAfficher'])
            ->name('api.v1.admin.projets.afficher');
        
        Route::put('/{id}/statut', [ControleurGestionContenu::class, 'projetChangerStatut'])
            ->name('api.v1.admin.projets.changer-statut');
        
        Route::delete('/{id}', [ControleurGestionContenu::class, 'projetSupprimer'])
            ->name('api.v1.admin.projets.supprimer');
        
        Route::get('/{id}/statistiques', [ControleurGestionContenu::class, 'projetStatistiques'])
            ->name('api.v1.admin.projets.statistiques');
    });
    
    // ==================== GESTION DES COMPÉTENCES ====================
    Route::prefix('competences')->group(function () {
        Route::get('/', [ControleurGestionContenu::class, 'competencesIndex'])
            ->name('api.v1.admin.competences.index');
        
        Route::post('/', [ControleurGestionContenu::class, 'competenceCreer'])
            ->name('api.v1.admin.competences.creer');
        
        Route::get('/{id}', [ControleurGestionContenu::class, 'competenceAfficher'])
            ->name('api.v1.admin.competences.afficher');
        
        Route::put('/{id}', [ControleurGestionContenu::class, 'competenceMettreAJour'])
            ->name('api.v1.admin.competences.mettre-a-jour');
        
        Route::delete('/{id}', [ControleurGestionContenu::class, 'competenceSupprimer'])
            ->name('api.v1.admin.competences.supprimer');
    });
    
    // ==================== GESTION DES DÉFIS ====================
    Route::prefix('defis')->group(function () {
        Route::get('/', [ControleurGestionContenu::class, 'defisIndex'])
            ->name('api.v1.admin.defis.index');
        
        Route::post('/', [ControleurGestionContenu::class, 'defiCreer'])
            ->name('api.v1.admin.defis.creer');
        
        Route::get('/{id}', [ControleurGestionContenu::class, 'defiAfficher'])
            ->name('api.v1.admin.defis.afficher');
        
        Route::put('/{id}', [ControleurGestionContenu::class, 'defiMettreAJour'])
            ->name('api.v1.admin.defis.mettre-a-jour');
        
        Route::delete('/{id}', [ControleurGestionContenu::class, 'defiSupprimer'])
            ->name('api.v1.admin.defis.supprimer');
        
        Route::post('/{id}/activer', [ControleurGestionContenu::class, 'defiActiver'])
            ->name('api.v1.admin.defis.activer');
        
        Route::post('/{id}/desactiver', [ControleurGestionContenu::class, 'defiDesactiver'])
            ->name('api.v1.admin.defis.desactiver');
        
        Route::get('/{id}/participations', [ControleurGestionContenu::class, 'defiParticipations'])
            ->name('api.v1.admin.defis.participations');
    });
    
    // ==================== GESTION DES BADGES ====================
    Route::prefix('badges')->group(function () {
        Route::get('/', [ControleurGestionContenu::class, 'badgesIndex'])
            ->name('api.v1.admin.badges.index');
        
        Route::post('/', [ControleurGestionContenu::class, 'badgeCreer'])
            ->name('api.v1.admin.badges.creer');
        
        Route::get('/{id}', [ControleurGestionContenu::class, 'badgeAfficher'])
            ->name('api.v1.admin.badges.afficher');
        
        Route::put('/{id}', [ControleurGestionContenu::class, 'badgeMettreAJour'])
            ->name('api.v1.admin.badges.mettre-a-jour');
        
        Route::delete('/{id}', [ControleurGestionContenu::class, 'badgeSupprimer'])
            ->name('api.v1.admin.badges.supprimer');
        
        Route::post('/{id}/attribuer/{utilisateurId}', 
            [ControleurGestionContenu::class, 'badgeAttribuer'])
            ->name('api.v1.admin.badges.attribuer');
        
        Route::delete('/{id}/retirer/{utilisateurId}', 
            [ControleurGestionContenu::class, 'badgeRetirer'])
            ->name('api.v1.admin.badges.retirer');
    });
    
    // ==================== GESTION DES MENTORS ====================
    Route::prefix('mentors')->group(function () {
        Route::get('/', [ControleurGestionUtilisateurs::class, 'mentorsIndex'])
            ->name('api.v1.admin.mentors.index');
        
        Route::post('/{id}/valider', [ControleurGestionUtilisateurs::class, 'mentorValider'])
            ->name('api.v1.admin.mentors.valider');
        
        Route::post('/{id}/revoquer', [ControleurGestionUtilisateurs::class, 'mentorRevoquer'])
            ->name('api.v1.admin.mentors.revoquer');
        
        Route::get('/demandes', [ControleurGestionUtilisateurs::class, 'mentorsDemandes'])
            ->name('api.v1.admin.mentors.demandes');
    });
    
    // ==================== ANALYTIQUES AVANCÉES ====================
    Route::prefix('analytiques')->group(function () {
        Route::get('/utilisation', [ControleurAnalytiques::class, 'utilisation'])
            ->name('api.v1.admin.analytiques.utilisation');
        
        Route::get('/engagement', [ControleurAnalytiques::class, 'engagement'])
            ->name('api.v1.admin.analytiques.engagement');
        
        Route::get('/performance', [ControleurAnalytiques::class, 'performance'])
            ->name('api.v1.admin.analytiques.performance');
        
        Route::get('/retention', [ControleurAnalytiques::class, 'retention'])
            ->name('api.v1.admin.analytiques.retention');
        
        Route::get('/repartition', [ControleurAnalytiques::class, 'repartition'])
            ->name('api.v1.admin.analytiques.repartition');
        
        Route::get('/export/{type}', [ControleurAnalytiques::class, 'export'])
            ->name('api.v1.admin.analytiques.export');
    });
    
    // ==================== SYSTÈME & CONFIGURATION ====================
    Route::prefix('systeme')->group(function () {
        Route::get('/configurations', [ControleurAnalytiques::class, 'configurations'])
            ->name('api.v1.admin.systeme.configurations');
        
        Route::put('/configurations/{cle}', [ControleurAnalytiques::class, 'mettreAJourConfiguration'])
            ->name('api.v1.admin.systeme.configurations.mettre-a-jour');
        
        Route::get('/logs', [ControleurAnalytiques::class, 'logs'])
            ->name('api.v1.admin.systeme.logs');
        
        Route::get('/sauvegardes', [ControleurAnalytiques::class, 'sauvegardes'])
            ->name('api.v1.admin.systeme.sauvegardes');

        Route::post('/sauvegardes/creer', [ControleurAnalytiques::class, 'creerSauvegarde'])
            ->name('api.v1.admin.systeme.sauvegardes.creer');
        
        Route::post('/maintenance/mode', [ControleurAnalytiques::class, 'changerModeMaintenance'])
            ->name('api.v1.admin.systeme.maintenance.mode');
    });
});