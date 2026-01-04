<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembreProjet extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'membres_projets';
    
    protected $fillable = [
        'projet_id',
        'utilisateur_id',
        'role',
        'rejoint_a',
        'score_contribution',
        'est_actif',
    ];

    protected function casts(): array
    {
        return [
            'rejoint_a' => 'datetime',
            'score_contribution' => 'integer',
            'est_actif' => 'boolean',
        ];
    }

    // ⚡ RELATIONS
    public function projet()
    {
        return $this->belongsTo(Projet::class, 'projet_id');
    }

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    // 🎯 SCOPES
    public function scopeActifs($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeCreateurs($query)
    {
        return $query->where('role', 'createur');
    }

    public function scopeContributeurs($query)
    {
        return $query->where('role', 'contributeur');
    }
}
