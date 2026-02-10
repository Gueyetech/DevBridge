<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisponibiliteMentor extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'disponibilites_mentors';

    protected $fillable = [
        'mentor_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'type',
        'est_actif',
        'recurrent',
        'date_specifique',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'est_actif' => 'boolean',
            'recurrent' => 'boolean',
            'date_specifique' => 'date',
        ];
    }

    // ⚡ RELATIONS
    public function mentor()
    {
        return $this->belongsTo(Utilisateur::class, 'mentor_id');
    }

    // 🎯 SCOPES
    public function scopeActives($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopePourJour($query, int $jour)
    {
        return $query->where('jour_semaine', $jour);
    }

    public function scopeRecurrentes($query)
    {
        return $query->where('recurrent', true);
    }
}
