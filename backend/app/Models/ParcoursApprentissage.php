<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParcoursApprentissage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'parcours_apprentissage';
    
    protected $fillable = [
        'titre',
        'slug',
        'description',
        'technologie',
        'difficulte',
        'duree_estimee_heures',
        'est_publie',
        'ordre',
        'prerequis',
        'image',
        'competences_acquises',
    ];

    protected function casts(): array
    {
        return [
            'est_publie' => 'boolean',
            'prerequis' => 'array',
            'competences_acquises' => 'array',
            'duree_estimee_heures' => 'integer',
            'ordre' => 'integer',
        ];
    }

    // ⚡ RELATIONS
    public function modules()
    {
        return $this->hasMany(Module::class, 'parcours_id')->orderBy('ordre');
    }

    public function utilisateursInscrits()
    {
        return $this->belongsToMany(Utilisateur::class, 'inscriptions_parcours', 'parcours_id', 'utilisateur_id')
                    ->withPivot(['progression_pourcentage', 'inscrit_a', 'commence_a', 'termine_a', 'score_final'])
                    ->withTimestamps();
    }

    // 🎯 SCOPES
    public function scopePublies($query)
    {
        return $query->where('est_publie', true);
    }

    public function scopePourNiveau($query, string $niveau)
    {
        return $query->where('difficulte', $niveau);
    }

    public function scopePourTechnologie($query, string $technologie)
    {
        return $query->where('technologie', $technologie);
    }

    // ✨ ATTRIBUTS
    public function getNombreModulesAttribute(): int
    {
        return $this->modules()->count();
    }

    public function getNombreLeconsAttribute(): int
    {
        return $this->modules->sum(function($module) {
            return $module->lecons->count();
        });
    }

    public function getNombreEtudiantsAttribute(): int
    {
        return $this->utilisateursInscrits()->count();
    }

    public function getTauxCompletionMoyenAttribute(): float
    {
        $inscriptions = $this->utilisateursInscrits;
        if ($inscriptions->isEmpty()) {
            return 0;
        }
        
        return $inscriptions->avg('pivot.progression_pourcentage');
    }
}
