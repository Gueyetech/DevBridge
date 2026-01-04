<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\RoleUtilisateur;

class Utilisateur extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $table = 'utilisateurs';
    
    protected $fillable = [
        'prenom',
        'nom',
        'email',
        'mot_de_passe',
        'role',
        'avatar',
        'points',
        'niveau',
        'est_actif',
    ];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verifie_a' => 'datetime',
            'est_actif' => 'boolean',
            'points' => 'integer',
            'niveau' => 'integer',
            'mot_de_passe' => 'hashed',
        ];
    }

    /**
     * Get the password attribute name for authentication.
     */
    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

    // ⚡ RELATIONS
    public function profil()
    {
        return $this->hasOne(Profil::class, 'utilisateur_id');
    }

    public function parcoursInscrits()
    {
        return $this->belongsToMany(ParcoursApprentissage::class, 'inscriptions_parcours', 'utilisateur_id', 'parcours_id')
                    ->withPivot(['progression_pourcentage', 'inscrit_a', 'commence_a', 'termine_a', 'score_final'])
                    ->withTimestamps();
    }

    public function progressionsLecons()
    {
        return $this->hasMany(ProgressionLecon::class, 'utilisateur_id');
    }

    /**
     * Alias pour progressionsLecons - utilisé par les contrôleurs
     */
    public function progresLecons()
    {
        return $this->progressionsLecons();
    }

    public function tentativesQuiz()
    {
        return $this->hasMany(TentativeQuiz::class, 'utilisateur_id');
    }

    public function competences()
    {
        return $this->belongsToMany(Competence::class, 'competences_utilisateurs', 'utilisateur_id', 'competence_id')
                    ->withPivot(['niveau_maitrise', 'valide_a', 'valide_par', 'methode_validation', 'preuves'])
                    ->withTimestamps();
    }

    public function projets()
    {
        return $this->belongsToMany(Projet::class, 'membres_projets', 'utilisateur_id', 'projet_id')
                    ->withPivot(['role', 'rejoint_a', 'score_contribution', 'est_actif'])
                    ->withTimestamps();
    }

    public function projetsCrees()
    {
        return $this->hasMany(Projet::class, 'createur_id');
    }

    public function tachesAssignees()
    {
        return $this->hasMany(Tache::class, 'assignee_a');
    }

    public function mentoratsCommeMentor()
    {
        return $this->hasMany(Mentorat::class, 'mentor_id');
    }

    public function mentoratsCommeEtudiant()
    {
        return $this->hasMany(Mentorat::class, 'etudiant_id');
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'badges_utilisateurs', 'utilisateur_id', 'badge_id')
                    ->withPivot(['obtenu_a', 'raison_obtention'])
                    ->withTimestamps();
    }

    public function defisParticipations()
    {
        return $this->hasMany(ParticipationDefi::class, 'utilisateur_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'utilisateur_id');
    }

    public function suiviTemps()
    {
        return $this->hasMany(SuiviTemps::class, 'utilisateur_id');
    }

    public function statistiques()
    {
        return $this->hasMany(StatistiquesUtilisateur::class, 'utilisateur_id');
    }

    public function classements()
    {
        return $this->hasMany(Classement::class, 'utilisateur_id');
    }

    public function feedbacksDonnes()
    {
        return $this->hasMany(FeedbackMentor::class, 'mentor_id');
    }

    public function feedbacksRecus()
    {
        return $this->hasMany(FeedbackMentor::class, 'etudiant_id');
    }

    public function commentaires()
    {
        return $this->hasMany(CommentaireTache::class, 'utilisateur_id');
    }

    public function logsActivites()
    {
        return $this->hasMany(LogActivite::class, 'utilisateur_id');
    }

    // 🎯 SCOPES
    public function scopeEtudiants($query)
    {
        return $query->where('role', RoleUtilisateur::ETUDIANT->value);
    }

    public function scopeMentors($query)
    {
        return $query->where('role', RoleUtilisateur::MENTOR->value);
    }

    public function scopeAdministrateurs($query)
    {
        return $query->where('role', RoleUtilisateur::ADMINISTRATEUR->value);
    }

    public function scopeActifs($query)
    {
        return $query->where('est_actif', true);
    }

    // ✨ ATTRIBUTS
    public function getNomCompletAttribute(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function getInitialesAttribute(): string
    {
        return strtoupper(substr($this->prenom, 0, 1) . substr($this->nom, 0, 1));
    }

    public function getEstEtudiantAttribute(): bool
    {
        return $this->role === RoleUtilisateur::ETUDIANT->value;
    }

    public function getEstMentorAttribute(): bool
    {
        return $this->role === RoleUtilisateur::MENTOR->value;
    }

    public function getEstAdministrateurAttribute(): bool
    {
        return $this->role === RoleUtilisateur::ADMINISTRATEUR->value;
    }

    public function getProgressionTotaleAttribute(): float
    {
        $inscriptions = $this->parcoursInscrits;
        if ($inscriptions->isEmpty()) {
            return 0;
        }
        
        return $inscriptions->avg('pivot.progression_pourcentage');
    }

    // 🎮 MÉTHODES
    public function ajouterPoints(int $points): void
    {
        $this->points += $points;
        $this->save();
        
        $this->verifierNiveau();
    }

    public function verifierNiveau(): void
    {
        $nouveauNiveau = floor($this->points / 1000) + 1;
        if ($nouveauNiveau > $this->niveau) {
            $this->niveau = $nouveauNiveau;
            $this->save();
        }
    }
}
