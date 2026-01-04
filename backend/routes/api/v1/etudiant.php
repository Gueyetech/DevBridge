<?php

use App\Http\Controllers\Api\V1\Etudiant\{
    ControleurTableauDeBord,
    ControleurParcoursApprentissage,
    ControleurQuiz,
    ControleurProfil,
    ControleurProjet,
    ControleurDefi,
    ControleurNotification
};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:etudiant'])->prefix('etudiant')->group(function () {
    
    // ==================== TABLEAU DE BORD ====================
    Route::prefix('tableau-de-bord')->group(function () {
        Route::get('/', [ControleurTableauDeBord::class, 'index'])
            ->name('api.v1.etudiant.tableau-de-bord.index');
        
        Route::get('/statistiques', [ControleurTableauDeBord::class, 'statistiques'])
            ->name('api.v1.etudiant.tableau-de-bord.statistiques');
    });
    
    // ==================== PROFIL ====================
    Route::prefix('profil')->group(function () {
        Route::get('/', [ControleurProfil::class, 'afficher'])
            ->name('api.v1.etudiant.profil.afficher');
        
        Route::put('/', [ControleurProfil::class, 'mettreAJour'])
            ->name('api.v1.etudiant.profil.mettre-a-jour');
        
        Route::post('/avatar', [ControleurProfil::class, 'mettreAJourAvatar'])
            ->name('api.v1.etudiant.profil.avatar');
        
        Route::get('/competences', [ControleurProfil::class, 'competences'])
            ->name('api.v1.etudiant.profil.competences');
        
        Route::post('/competences', [ControleurProfil::class, 'ajouterCompetence'])
            ->name('api.v1.etudiant.profil.competences.ajouter');
    });
    
    // ==================== PARCOURS D'APPRENTISSAGE ====================
    Route::prefix('parcours')->group(function () {
        Route::get('/', [ControleurParcoursApprentissage::class, 'index'])
            ->name('api.v1.etudiant.parcours.index');
        
        Route::get('/{id}', [ControleurParcoursApprentissage::class, 'afficher'])
            ->name('api.v1.etudiant.parcours.afficher');
        
        Route::post('/{id}/inscrire', [ControleurParcoursApprentissage::class, 'inscrire'])
            ->name('api.v1.etudiant.parcours.inscrire');
        
        Route::get('/{id}/progression', [ControleurParcoursApprentissage::class, 'progression'])
            ->name('api.v1.etudiant.parcours.progression');
        
        Route::get('/{id}/prochain-contenu', [ControleurParcoursApprentissage::class, 'prochainContenu'])
            ->name('api.v1.etudiant.parcours.prochain-contenu');
        
        Route::post('/{parcoursId}/lecons/{leconId}/terminer', 
            [ControleurParcoursApprentissage::class, 'marquerLeconTerminee'])
            ->name('api.v1.etudiant.parcours.lecons.terminer');
    });
    
    // ==================== QUIZ ====================
    Route::prefix('quiz')->group(function () {
        Route::get('/{quizId}/commencer', [ControleurQuiz::class, 'commencer'])
            ->name('api.v1.etudiant.quiz.commencer');
        
        Route::post('/{quizId}/tentatives/{tentativeId}/soumettre', 
            [ControleurQuiz::class, 'soumettre'])
            ->name('api.v1.etudiant.quiz.soumettre');
        
        Route::get('/{quizId}/tentatives/{tentativeId}/resultats', 
            [ControleurQuiz::class, 'resultats'])
            ->name('api.v1.etudiant.quiz.resultats');
        
        Route::get('/tentatives', [ControleurQuiz::class, 'tentatives'])
            ->name('api.v1.etudiant.quiz.tentatives');
        
        Route::get('/{quizId}/tentatives', [ControleurQuiz::class, 'tentatives'])
            ->name('api.v1.etudiant.quiz.tentatives.par-quiz');
        
        Route::get('/{quizId}/classement', [ControleurQuiz::class, 'classement'])
            ->name('api.v1.etudiant.quiz.classement');
    });
    
    // ==================== PROJETS ====================
    Route::prefix('projets')->group(function () {
        Route::get('/', [ControleurProjet::class, 'index'])
            ->name('api.v1.etudiant.projets.index');
        
        Route::get('/{id}', [ControleurProjet::class, 'afficher'])
            ->name('api.v1.etudiant.projets.afficher');
        
        Route::post('/', [ControleurProjet::class, 'creer'])
            ->name('api.v1.etudiant.projets.creer');
        
        Route::post('/{id}/rejoindre', [ControleurProjet::class, 'rejoindre'])
            ->name('api.v1.etudiant.projets.rejoindre');
        
        Route::put('/{id}', [ControleurProjet::class, 'mettreAJour'])
            ->name('api.v1.etudiant.projets.mettre-a-jour');
        
        Route::delete('/{id}', [ControleurProjet::class, 'supprimer'])
            ->name('api.v1.etudiant.projets.supprimer');
        
        Route::post('/{id}/completer', [ControleurProjet::class, 'completer'])
            ->name('api.v1.etudiant.projets.completer');
        
        // Tâches
        Route::prefix('/{projetId}/taches')->group(function () {
            Route::get('/', [ControleurProjet::class, 'taches'])
                ->name('api.v1.etudiant.projets.taches.index');
            
            Route::post('/', [ControleurProjet::class, 'creerTache'])
                ->name('api.v1.etudiant.projets.taches.creer');
            
            Route::put('/{tacheId}', [ControleurProjet::class, 'mettreAJourTache'])
                ->name('api.v1.etudiant.projets.taches.mettre-a-jour');
            
            Route::delete('/{tacheId}', [ControleurProjet::class, 'supprimerTache'])
                ->name('api.v1.etudiant.projets.taches.supprimer');
            
            Route::post('/{tacheId}/assigner', [ControleurProjet::class, 'assignerTache'])
                ->name('api.v1.etudiant.projets.taches.assigner');
            
            Route::post('/{tacheId}/terminer', [ControleurProjet::class, 'terminerTache'])
                ->name('api.v1.etudiant.projets.taches.terminer');
            
            // Commentaires
            Route::prefix('/{tacheId}/commentaires')->group(function () {
                Route::get('/', [ControleurProjet::class, 'commentaires'])
                    ->name('api.v1.etudiant.projets.taches.commentaires.index');
                
                Route::post('/', [ControleurProjet::class, 'creerCommentaire'])
                    ->name('api.v1.etudiant.projets.taches.commentaires.creer');
                
                Route::put('/{commentaireId}', [ControleurProjet::class, 'mettreAJourCommentaire'])
                    ->name('api.v1.etudiant.projets.taches.commentaires.mettre-a-jour');
                
                Route::delete('/{commentaireId}', [ControleurProjet::class, 'supprimerCommentaire'])
                    ->name('api.v1.etudiant.projets.taches.commentaires.supprimer');
            });
        });
    });
    
    // ==================== DÉFIS ====================
    Route::prefix('defis')->group(function () {
        Route::get('/', [ControleurDefi::class, 'index'])
            ->name('api.v1.etudiant.defis.index');
        
        Route::get('/{id}', [ControleurDefi::class, 'afficher'])
            ->name('api.v1.etudiant.defis.afficher');
        
        Route::post('/{id}/participer', [ControleurDefi::class, 'participer'])
            ->name('api.v1.etudiant.defis.participer');
        
        Route::post('/{id}/soumettre', [ControleurDefi::class, 'soumettreSolution'])
            ->name('api.v1.etudiant.defis.soumettre');
        
        Route::get('/participations', [ControleurDefi::class, 'participations'])
            ->name('api.v1.etudiant.defis.participations');
        
        Route::get('/classement', [ControleurDefi::class, 'classement'])
            ->name('api.v1.etudiant.defis.classement');
    });
    
    // ==================== MENTORAT ====================
    Route::prefix('mentorat')->group(function () {
        Route::get('/mentors', [ControleurProfil::class, 'mentorsDisponibles'])
            ->name('api.v1.etudiant.mentorat.mentors');
        
        Route::post('/demander/{mentorId}', [ControleurProfil::class, 'demanderMentorat'])
            ->name('api.v1.etudiant.mentorat.demander');
        
        Route::get('/demandes', [ControleurProfil::class, 'demandesMentorat'])
            ->name('api.v1.etudiant.mentorat.demandes');
        
        Route::get('/sessions', [ControleurProfil::class, 'sessionsMentorat'])
            ->name('api.v1.etudiant.mentorat.sessions');
        
        Route::post('/sessions/{sessionId}/feedback', [ControleurProfil::class, 'donnerFeedbackSession'])
            ->name('api.v1.etudiant.mentorat.sessions.feedback');
    });
    
    // ==================== NOTIFICATIONS ====================
    Route::prefix('notifications')->group(function () {
        Route::get('/', [ControleurNotification::class, 'index'])
            ->name('api.v1.etudiant.notifications.index');
        
        Route::get('/non-lues', [ControleurNotification::class, 'nonLues'])
            ->name('api.v1.etudiant.notifications.non-lues');
        
        Route::post('/{id}/marquer-lu', [ControleurNotification::class, 'marquerCommeLu'])
            ->name('api.v1.etudiant.notifications.marquer-lu');
        
        Route::post('/marquer-toutes-lues', [ControleurNotification::class, 'marquerToutesCommeLues'])
            ->name('api.v1.etudiant.notifications.marquer-toutes-lues');
        
        Route::delete('/{id}', [ControleurNotification::class, 'supprimer'])
            ->name('api.v1.etudiant.notifications.supprimer');
    });
    
    // ==================== SUIVI DU TEMPS ====================
    Route::prefix('suivi-temps')->group(function () {
        Route::post('/commencer', [ControleurTableauDeBord::class, 'commencerSuiviTemps'])
            ->name('api.v1.etudiant.suivi-temps.commencer');
        
        Route::post('/terminer', [ControleurTableauDeBord::class, 'terminerSuiviTemps'])
            ->name('api.v1.etudiant.suivi-temps.terminer');
        
        Route::get('/historique', [ControleurTableauDeBord::class, 'historiqueSuiviTemps'])
            ->name('api.v1.etudiant.suivi-temps.historique');
    });
    
    // ==================== BADGES & RÉCOMPENSES ====================
    Route::prefix('recompenses')->group(function () {
        Route::get('/badges', [ControleurProfil::class, 'badges'])
            ->name('api.v1.etudiant.recompenses.badges');
        
        Route::get('/points', [ControleurProfil::class, 'historiquePoints'])
            ->name('api.v1.etudiant.recompenses.points');
        
        Route::get('/classement', [ControleurTableauDeBord::class, 'classementGlobal'])
            ->name('api.v1.etudiant.recompenses.classement');
    });
});