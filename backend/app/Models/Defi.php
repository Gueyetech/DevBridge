<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Defi extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'defis';
    
    protected $fillable = [
        'titre',
        'slug',
        'description',
        'type',
        'difficulte',
        'technologies',
        'points_recompense',
        'experience_recompense',
        'date_debut',
        'date_fin',
        'participants_maximum',
        'est_actif',
    ];

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'points_recompense' => 'integer',
            'experience_recompense' => 'integer',
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'participants_maximum' => 'integer',
            'est_actif' => 'boolean',
        ];
    }

    // ⚡ RELATIONS
    public function participants()
    {
        return $this->belongsToMany(Utilisateur::class, 'participations_defis', 'defi_id', 'utilisateur_id')
                    ->withPivot(['statut', 'solution_url', 'description_solution', 'score', 'feedback_jury', 'inscrit_a', 'soumis_a'])
                    ->withTimestamps();
    }

    public function participations()
    {
        return $this->hasMany(ParticipationDefi::class, 'defi_id');
    }

    // 🎯 SCOPES
    public function scopeActifs($query)
    {
        return $query->where('est_actif', true)
                     ->where('date_debut', '<=', now())
                     ->where('date_fin', '>=', now());
    }

    public function scopeAVenir($query)
    {
        return $query->where('est_actif', true)
                     ->where('date_debut', '>', now());
    }

    public function scopeTermines($query)
    {
        return $query->where('date_fin', '<', now());
    }

    public function scopeQuotidiens($query)
    {
        return $query->where('type', 'quotidien');
    }

    public function scopeHebdomadaires($query)
    {
        return $query->where('type', 'hebdomadaire');
    }

    public function scopeMensuels($query)
    {
        return $query->where('type', 'mensuel');
    }

    public function scopePourDifficulte($query, string $difficulte)
    {
        return $query->where('difficulte', $difficulte);
    }

    // ✨ ATTRIBUTS
    public function getEstActuellementActifAttribute(): bool
    {
        return $this->est_actif && 
               now()->between($this->date_debut, $this->date_fin);
    }

    public function getNombreParticipantsAttribute(): int
    {
        return $this->participants()->count();
    }

    public function getEstCompletAttribute(): bool
    {
        return $this->participants_maximum && 
               $this->nombre_participants >= $this->participants_maximum;
    }

    public function getTempsRestantAttribute(): ?string
    {
        if (now()->greaterThan($this->date_fin)) {
            return null;
        }

        return now()->diffForHumans($this->date_fin, ['parts' => 2]);
    }

    // 🎮 MÉTHODES
    public function inscrireUtilisateur(string $utilisateurId): bool
    {
        if ($this->est_complet) {
            return false;
        }

        if ($this->participants()->where('utilisateur_id', $utilisateurId)->exists()) {
            return false;
        }
        
        $this->participants()->attach($utilisateurId, [
            'statut' => 'inscrit',
            'inscrit_a' => now(),
        ]);
        
        return true;
    }
}
