<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profil extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'profils';
    
    protected $fillable = [
        'utilisateur_id',
        'bio',
        'niveau',
        'technologies',
        'github_url',
        'linkedin_url',
        'portfolio_url',
        'ville',
        'pays',
        'est_disponible_mentorat',
    ];

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'est_disponible_mentorat' => 'boolean',
        ];
    }

    // ⚡ RELATIONS
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    // ✨ ATTRIBUTS
    public function getNombreCompetencesAttribute(): int
    {
        return $this->utilisateur->competences()->count();
    }

    public function getNombreProjetsTerminesAttribute(): int
    {
        return $this->utilisateur->projets()
            ->wherePivot('est_actif', true)
            ->where('statut', 'termine')
            ->count();
    }
}
