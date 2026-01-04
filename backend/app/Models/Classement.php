<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'classement';
    
    protected $fillable = [
        'utilisateur_id',
        'position',
        'points_totaux',
        'experience_totale',
        'badges_obtenus',
        'projets_termines',
        'defis_gagnes',
        'periode',
        'date_reference',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'points_totaux' => 'integer',
            'experience_totale' => 'integer',
            'badges_obtenus' => 'integer',
            'projets_termines' => 'integer',
            'defis_gagnes' => 'integer',
            'date_reference' => 'date',
        ];
    }

    // ⚡ RELATIONS
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    // 🎯 SCOPES
    public function scopeQuotidien($query)
    {
        return $query->where('periode', 'quotidien');
    }

    public function scopeHebdomadaire($query)
    {
        return $query->where('periode', 'hebdomadaire');
    }

    public function scopeMensuel($query)
    {
        return $query->where('periode', 'mensuel');
    }

    public function scopeAnnuel($query)
    {
        return $query->where('periode', 'annuel');
    }

    public function scopeGlobal($query)
    {
        return $query->where('periode', 'global');
    }

    public function scopePourDate($query, $date)
    {
        return $query->where('date_reference', $date);
    }

    public function scopeTop($query, int $limit = 10)
    {
        return $query->orderBy('position')->limit($limit);
    }

    // ✨ ATTRIBUTS
    public function getEstPremierAttribute(): bool
    {
        return $this->position === 1;
    }

    public function getEstPodiumAttribute(): bool
    {
        return $this->position <= 3;
    }

    public function getEstTop10Attribute(): bool
    {
        return $this->position <= 10;
    }
}
