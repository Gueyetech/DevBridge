<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tache extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'taches';
    
    protected $fillable = [
        'projet_id',
        'titre',
        'description',
        'statut',
        'priorite',
        'duree_estimee_heures',
        'duree_reelle_heures',
        'assignee_a',
        'date_echeance',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'duree_estimee_heures' => 'integer',
            'duree_reelle_heures' => 'integer',
            'date_echeance' => 'datetime',
            'tags' => 'array',
        ];
    }

    // ⚡ RELATIONS
    public function projet()
    {
        return $this->belongsTo(Projet::class, 'projet_id');
    }

    public function assignee()
    {
        return $this->belongsTo(Utilisateur::class, 'assignee_a');
    }

    public function commentaires()
    {
        return $this->hasMany(CommentaireTache::class, 'tache_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(FeedbackMentor::class, 'tache_id');
    }

    // 🎯 SCOPES
    public function scopeAFaire($query)
    {
        return $query->where('statut', 'a_faire');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeTerminees($query)
    {
        return $query->where('statut', 'termine');
    }

    public function scopeBloquees($query)
    {
        return $query->where('statut', 'bloque');
    }

    public function scopeHautePriorite($query)
    {
        return $query->whereIn('priorite', ['haute', 'critique']);
    }

    public function scopeNonAssignees($query)
    {
        return $query->whereNull('assignee_a');
    }

    // ✨ ATTRIBUTS
    public function getEstEnRetardAttribute(): bool
    {
        return $this->date_echeance && now()->greaterThan($this->date_echeance) && $this->statut !== 'termine';
    }

    public function getNombreCommentairesAttribute(): int
    {
        return $this->commentaires()->count();
    }

    public function getEstAssigneeAttribute(): bool
    {
        return $this->assignee_a !== null;
    }

    // 🎮 MÉTHODES
    public function assignerA(string $utilisateurId): void
    {
        $this->assignee_a = $utilisateurId;
        if ($this->statut === 'a_faire') {
            $this->statut = 'en_cours';
        }
        $this->save();
    }

    public function terminer(): void
    {
        $this->statut = 'termine';
        $this->save();
        
        if ($this->assignee) {
            $points = match($this->priorite) {
                'critique' => 50,
                'haute' => 30,
                'moyenne' => 20,
                default => 10,
            };
            
            $this->assignee->ajouterPoints($points);
        }
    }
}
