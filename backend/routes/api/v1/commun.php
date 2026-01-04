<?php

use App\Http\Controllers\Api\V1\Commun\{
    ControleurRecherche,
    ControleurForum,
    ControleurMessagerie,
    ControleurTelechargement,
    ControleurProfil
};
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    
    // ==================== PROFIL ====================
    Route::prefix('profil')->group(function () {
        Route::get('/', [ControleurProfil::class, 'show'])
            ->name('api.v1.commun.profil.show');
        
        Route::put('/infos', [ControleurProfil::class, 'updateInfos'])
            ->name('api.v1.commun.profil.infos');
        
        Route::put('/details', [ControleurProfil::class, 'updateProfil'])
            ->name('api.v1.commun.profil.details');
        
        Route::post('/avatar', [ControleurProfil::class, 'updateAvatar'])
            ->name('api.v1.commun.profil.avatar');
        
        Route::delete('/avatar', [ControleurProfil::class, 'deleteAvatar'])
            ->name('api.v1.commun.profil.avatar.delete');
        
        Route::put('/mot-de-passe', [ControleurProfil::class, 'updatePassword'])
            ->name('api.v1.commun.profil.mot-de-passe');
        
        Route::get('/stats', [ControleurProfil::class, 'stats'])
            ->name('api.v1.commun.profil.stats');
        
        Route::get('/activite', [ControleurProfil::class, 'activiteRecente'])
            ->name('api.v1.commun.profil.activite');
        
        Route::delete('/compte', [ControleurProfil::class, 'deleteAccount'])
            ->name('api.v1.commun.profil.compte.delete');
    });
    
    // ==================== RECHERCHE ====================
    Route::prefix('recherche')->group(function () {
        Route::get('/', [ControleurRecherche::class, 'rechercheGlobale'])
            ->name('api.v1.commun.recherche.globale');
        
        Route::get('/parcours', [ControleurRecherche::class, 'rechercheParcours'])
            ->name('api.v1.commun.recherche.parcours');
        
        Route::get('/projets', [ControleurRecherche::class, 'rechercheProjets'])
            ->name('api.v1.commun.recherche.projets');
        
        Route::get('/utilisateurs', [ControleurRecherche::class, 'rechercheUtilisateurs'])
            ->name('api.v1.commun.recherche.utilisateurs');
        
        Route::get('/competences', [ControleurRecherche::class, 'rechercheCompetences'])
            ->name('api.v1.commun.recherche.competences');
    });
    
    // ==================== FORUM ====================
    Route::prefix('forum')->group(function () {
        // Catégories
        Route::get('/categories', [ControleurForum::class, 'categories'])
            ->name('api.v1.commun.forum.categories');
        
        // Discussions
        Route::get('/discussions', [ControleurForum::class, 'discussions'])
            ->name('api.v1.commun.forum.discussions');
        
        Route::post('/discussions', [ControleurForum::class, 'creerDiscussion'])
            ->name('api.v1.commun.forum.discussions.creer');
        
        Route::get('/discussions/{id}', [ControleurForum::class, 'discussionDetail'])
            ->name('api.v1.commun.forum.discussions.detail');
        
        Route::put('/discussions/{id}', [ControleurForum::class, 'mettreAJourDiscussion'])
            ->name('api.v1.commun.forum.discussions.mettre-a-jour');
        
        Route::delete('/discussions/{id}', [ControleurForum::class, 'supprimerDiscussion'])
            ->name('api.v1.commun.forum.discussions.supprimer');
        
        // Messages
        Route::prefix('/discussions/{discussionId}/messages')->group(function () {
            Route::get('/', [ControleurForum::class, 'messages'])
                ->name('api.v1.commun.forum.discussions.messages');
            
            Route::post('/', [ControleurForum::class, 'creerMessage'])
                ->name('api.v1.commun.forum.discussions.messages.creer');
            
            Route::put('/{messageId}', [ControleurForum::class, 'mettreAJourMessage'])
                ->name('api.v1.commun.forum.discussions.messages.mettre-a-jour');
            
            Route::delete('/{messageId}', [ControleurForum::class, 'supprimerMessage'])
                ->name('api.v1.commun.forum.discussions.messages.supprimer');
            
            Route::post('/{messageId}/aimer', [ControleurForum::class, 'aimerMessage'])
                ->name('api.v1.commun.forum.discussions.messages.aimer');
            
            Route::post('/{messageId}/signaler', [ControleurForum::class, 'signalerMessage'])
                ->name('api.v1.commun.forum.discussions.messages.signaler');
        });
        
        // Mes discussions
        Route::get('/mes-discussions', [ControleurForum::class, 'mesDiscussions'])
            ->name('api.v1.commun.forum.mes-discussions');
        
        Route::get('/discussions-suivies', [ControleurForum::class, 'discussionsSuivies'])
            ->name('api.v1.commun.forum.discussions-suivies');
        
        Route::post('/discussions/{id}/suivre', [ControleurForum::class, 'suivreDiscussion'])
            ->name('api.v1.commun.forum.discussions.suivre');
        
        Route::delete('/discussions/{id}/suivre', [ControleurForum::class, 'nePlusSuivreDiscussion'])
            ->name('api.v1.commun.forum.discussions.ne-plus-suivre');
    });
    
    // ==================== MESSAGERIE ====================
    Route::prefix('messagerie')->group(function () {
        // Conversations
        Route::get('/conversations', [ControleurMessagerie::class, 'conversations'])
            ->name('api.v1.commun.messagerie.conversations');
        
        Route::post('/conversations', [ControleurMessagerie::class, 'creerConversation'])
            ->name('api.v1.commun.messagerie.conversations.creer');
        
        Route::get('/conversations/{id}', [ControleurMessagerie::class, 'conversationDetail'])
            ->name('api.v1.commun.messagerie.conversations.detail');
        
        // Messages
        Route::prefix('/conversations/{conversationId}/messages')->group(function () {
            Route::get('/', [ControleurMessagerie::class, 'messages'])
                ->name('api.v1.commun.messagerie.conversations.messages');
            
            Route::post('/', [ControleurMessagerie::class, 'envoyerMessage'])
                ->name('api.v1.commun.messagerie.conversations.messages.envoyer');
            
            Route::post('/{messageId}/lire', [ControleurMessagerie::class, 'marquerCommeLu'])
                ->name('api.v1.commun.messagerie.conversations.messages.lire');
        });
        
        // Notifications de messagerie
        Route::get('/notifications', [ControleurMessagerie::class, 'notifications'])
            ->name('api.v1.commun.messagerie.notifications');
    });
    
    // ==================== TÉLÉCHARGEMENTS ====================
    Route::prefix('telechargements')->group(function () {
        Route::get('/ressources', [ControleurTelechargement::class, 'ressources'])
            ->name('api.v1.commun.telechargements.ressources');
        
        Route::get('/certificats', [ControleurTelechargement::class, 'certificats'])
            ->name('api.v1.commun.telechargements.certificats');
        
        Route::post('/certificats/generer/{competenceId}', 
            [ControleurTelechargement::class, 'genererCertificat'])
            ->name('api.v1.commun.telechargements.certificats.generer');
        
        Route::get('/rapports', [ControleurTelechargement::class, 'rapports'])
            ->name('api.v1.commun.telechargements.rapports');
        
        Route::post('/rapports/progression', [ControleurTelechargement::class, 'genererRapportProgression'])
            ->name('api.v1.commun.telechargements.rapports.progression');
    });
    
    // ==================== FEEDBACK & SUPPORT ====================
    Route::prefix('support')->group(function () {
        Route::post('/feedback', [ControleurRecherche::class, 'soumettreFeedback'])
            ->name('api.v1.commun.support.feedback');
        
        Route::post('/signaler-probleme', [ControleurRecherche::class, 'signalerProbleme'])
            ->name('api.v1.commun.support.signaler-probleme');
        
        Route::get('/faq', [ControleurRecherche::class, 'faq'])
            ->name('api.v1.commun.support.faq');
    });
});