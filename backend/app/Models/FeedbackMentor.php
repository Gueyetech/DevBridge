<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackMentor extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'feedbacks_mentor';
    
    protected $fillable = [
        'mentor_id',
        'etudiant_id',
        'projet_id',
        'tache_id',
        'contenu',
        'type',
        'points_positifs',
        'points_amelioration',
        'note_generale',
        'est_lu',
    ];

    protected function casts(): array
    {
        return [
            'points_positifs' => 'array',
            'points_amelioration' => 'array',
            'note_generale' => 'integer',
            'est_lu' => 'boolean',
        ];
    }

    // ⚡ RELATIONS
    public function mentor()
    {
        return $this->belongsTo(Utilisateur::class, 'mentor_id');
    }

    public function etudiant()
    {
        return $this->belongsTo(Utilisateur::class, 'etudiant_id');
    }

    public function projet()
    {
        return $this->belongsTo(Projet::class, 'projet_id');
    }

    public function tache()
    {
        return $this->belongsTo(Tache::class, 'tache_id');
    }

    // 🎯 SCOPES
    public function scopeNonLus($query)
    {
        return $query->where('est_lu', false);
    }

    public function scopeLus($query)
    {
        return $query->where('est_lu', true);
    }

    public function scopePourProjet($query, string $projetId)
    {
        return $query->where('projet_id', $projetId);
    }

    public function scopePourTache($query, string $tacheId)
    {
        return $query->where('tache_id', $tacheId);
    }

    public function scopeDeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ✨ ATTRIBUTS
    public function getEstCodeAttribute(): bool
    {
        return $this->type === 'code';
    }

    public function getEstConceptionAttribute(): bool
    {
        return $this->type === 'conception';
    }

    public function getEstMethodologieAttribute(): bool
    {
        return $this->type === 'methodologie';
    }

    // 🎮 MÉTHODES
    public function marquerCommeLu(): void
    {
        $this->est_lu = true;
        $this->save();
    }
}
