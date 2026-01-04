<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class InscriptionParcours extends Pivot
{
    use HasFactory, HasUuids;

    protected $table = 'inscriptions_parcours';

    protected $fillable = [
        'utilisateur_id',
        'parcours_id',
        'progression_pourcentage',
        'inscrit_a',
        'commence_a',
        'termine_a',
        'score_final',
    ];

    protected function casts(): array
    {
        return [
            'progression_pourcentage' => 'integer',
            'inscrit_a' => 'datetime',
            'commence_a' => 'datetime',
            'termine_a' => 'datetime',
            'score_final' => 'integer',
        ];
    }

    // ⚡ RELATIONS
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function parcours()
    {
        return $this->belongsTo(ParcoursApprentissage::class, 'parcours_id');
    }

    // ✨ ATTRIBUTS
    public function getEstTermineAttribute(): bool
    {
        return $this->termine_a !== null;
    }

    public function getEstEnCoursAttribute(): bool
    {
        return $this->commence_a !== null && $this->termine_a === null;
    }
}
