<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatistiquesUtilisateur extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'statistiques_utilisateurs';
    
    protected $fillable = [
        'utilisateur_id',
        'date',
        'temps_apprentissage_minutes',
        'quiz_passes',
        'quiz_reussis',
        'projets_termines',
        'points_gagnes',
        'badges_obtenus',
        'defis_participe',
        'sessions_mentorat',
        'metriques_personnalisees',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'temps_apprentissage_minutes' => 'integer',
            'quiz_passes' => 'integer',
            'quiz_reussis' => 'integer',
            'projets_termines' => 'integer',
            'points_gagnes' => 'integer',
            'badges_obtenus' => 'integer',
            'defis_participe' => 'integer',
            'sessions_mentorat' => 'integer',
            'metriques_personnalisees' => 'array',
        ];
    }

    // ⚡ RELATIONS
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    // 🎯 SCOPES
    public function scopePourUtilisateur($query, string $utilisateurId)
    {
        return $query->where('utilisateur_id', $utilisateurId);
    }

    public function scopePourDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopePourPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date', [$debut, $fin]);
    }

    public function scopeCetteSemaine($query)
    {
        return $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeCeMois($query)
    {
        return $query->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    // ✨ ATTRIBUTS
    public function getTauxReussiteQuizAttribute(): float
    {
        if ($this->quiz_passes === 0) {
            return 0;
        }
        
        return round(($this->quiz_reussis / $this->quiz_passes) * 100, 2);
    }

    public function getTempsApprentissageHeuresAttribute(): float
    {
        return round($this->temps_apprentissage_minutes / 60, 2);
    }

    public function getTempsApprentissageFormateAttribute(): string
    {
        $heures = floor($this->temps_apprentissage_minutes / 60);
        $minutes = $this->temps_apprentissage_minutes % 60;

        if ($heures > 0) {
            return sprintf('%dh %02dm', $heures, $minutes);
        }

        return sprintf('%dm', $minutes);
    }

    // 🎮 MÉTHODES
    public static function pourAujourdhui(string $utilisateurId): self
    {
        return self::firstOrCreate(
            ['utilisateur_id' => $utilisateurId, 'date' => now()->toDateString()],
            [
                'temps_apprentissage_minutes' => 0,
                'quiz_passes' => 0,
                'quiz_reussis' => 0,
                'projets_termines' => 0,
                'points_gagnes' => 0,
                'badges_obtenus' => 0,
                'defis_participe' => 0,
                'sessions_mentorat' => 0,
            ]
        );
    }

    public function incrementerQuizPasse(bool $reussi = false): void
    {
        $this->quiz_passes++;
        if ($reussi) {
            $this->quiz_reussis++;
        }
        $this->save();
    }

    public function ajouterTempsApprentissage(int $minutes): void
    {
        $this->temps_apprentissage_minutes += $minutes;
        $this->save();
    }

    public function ajouterPoints(int $points): void
    {
        $this->points_gagnes += $points;
        $this->save();
    }
}
