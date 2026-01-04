<?php

namespace App\Enums;

enum RoleUtilisateur: string
{
    case ETUDIANT = 'etudiant';
    case MENTOR = 'mentor';
    case ADMINISTRATEUR = 'administrateur';
    
    public function label(): string
    {
        return match($this) {
            self::ETUDIANT => 'Étudiant',
            self::MENTOR => 'Mentor',
            self::ADMINISTRATEUR => 'Administrateur',
        };
    }
    
    public function permissions(): array
    {
        return match($this) {
            self::ETUDIANT => [
                'voir_lecons',
                'passer_quiz',
                's_inscrire_parcours',
                'participer_projets',
                'demander_mentorat',
            ],
            self::MENTOR => [
                'donner_feedback',
                'voir_progression_etudiants',
                'planifier_sessions',
                'reviser_code',
            ],
            self::ADMINISTRATEUR => [
                'gerer_utilisateurs',
                'gerer_contenu',
                'voir_analytiques',
                'modifier_parametres',
            ],
        };
    }
}
