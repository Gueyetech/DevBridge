<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressionLecon extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'progressions_lecons';
    
    protected $fillable = [
        'utilisateur_id',
        'lecon_id',
        'est_termine',
        'temps_passe_secondes',
        'commence_a',
        'termine_a',
    ];

    protected function casts(): array
    {
        return [
            'est_termine' => 'boolean',
            'temps_passe_secondes' => 'integer',
            'commence_a' => 'datetime',
            'termine_a' => 'datetime',
        ];
    }

    // ⚡ RELATIONS
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function lecon()
    {
        return $this->belongsTo(Lecon::class, 'lecon_id');
    }

    // ✨ ATTRIBUTS
    public function getTempsPasseFormateAttribute(): string
    {
        $heures = floor($this->temps_passe_secondes / 3600);
        $minutes = floor(($this->temps_passe_secondes % 3600) / 60);
        $secondes = $this->temps_passe_secondes % 60;
        
        if ($heures > 0) {
            return sprintf('%dh %02dm', $heures, $minutes);
        }
        
        return sprintf('%02dm %02ds', $minutes, $secondes);
    }
}
