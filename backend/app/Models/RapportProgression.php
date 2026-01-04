<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RapportProgression extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'rapports_progression';

    protected $fillable = [
        'utilisateur_id',
        'type',
        'periode_debut',
        'periode_fin',
        'donnees',
        'chemin_fichier',
        'genere_a',
    ];

    protected $casts = [
        'periode_debut' => 'datetime',
        'periode_fin' => 'datetime',
        'donnees' => 'array',
        'genere_a' => 'datetime',
    ];

    /**
     * L'utilisateur concerné par le rapport
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }
}
