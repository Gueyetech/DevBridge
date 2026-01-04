<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuiviTemps extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'suivi_temps';
    
    protected $fillable = [
        'utilisateur_id',
        'type_activite',
        'activite_id',
        'type_activite_morph',
        'debut_a',
        'fin_a',
        'duree_secondes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'debut_a' => 'datetime',
            'fin_a' => 'datetime',
            'duree_secondes' => 'integer',
            'metadata' => 'array',
        ];
    }

    // ⚡ RELATIONS
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    /**
     * Relation polymorphique vers l'activité suivie.
     * 
     * Types morphiques supportés:
     * - App\Models\Lecon (type_activite: 'lecon')
     * - App\Models\Projet (type_activite: 'projet')
     * - App\Models\Defi (type_activite: 'defi')
     * - App\Models\Quiz (type_activite: 'quiz')
     * - App\Models\Mentorat (type_activite: 'mentorat')
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function activite()
    {
        return $this->morphTo('activite', 'type_activite_morph', 'activite_id');
    }

    // 🎯 SCOPES
    public function scopePourUtilisateur($query, string $utilisateurId)
    {
        return $query->where('utilisateur_id', $utilisateurId);
    }

    public function scopePourDate($query, $date)
    {
        return $query->whereDate('debut_a', $date);
    }

    public function scopePourPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('debut_a', [$debut, $fin]);
    }

    public function scopePourType($query, string $type)
    {
        return $query->where('type_activite', $type);
    }

    public function scopeLecons($query)
    {
        return $query->where('type_activite', 'lecon');
    }

    public function scopeProjets($query)
    {
        return $query->where('type_activite', 'projet');
    }

    public function scopeQuiz($query)
    {
        return $query->where('type_activite', 'quiz');
    }

    public function scopeEnCours($query)
    {
        return $query->whereNull('fin_a');
    }

    public function scopeTermines($query)
    {
        return $query->whereNotNull('fin_a');
    }

    // ✨ ATTRIBUTS
    public function getDureeMinutesAttribute(): float
    {
        return round($this->duree_secondes / 60, 2);
    }

    public function getDureeHeuresAttribute(): float
    {
        return round($this->duree_secondes / 3600, 2);
    }

    public function getDureeFormateeAttribute(): string
    {
        $heures = floor($this->duree_secondes / 3600);
        $minutes = floor(($this->duree_secondes % 3600) / 60);
        $secondes = $this->duree_secondes % 60;

        if ($heures > 0) {
            return sprintf('%dh %02dm %02ds', $heures, $minutes, $secondes);
        }

        return sprintf('%02dm %02ds', $minutes, $secondes);
    }

    public function getEstEnCoursAttribute(): bool
    {
        return $this->fin_a === null;
    }

    // 🎮 MÉTHODES
    public function terminer(): void
    {
        $this->fin_a = now();
        $this->duree_secondes = $this->fin_a->diffInSeconds($this->debut_a);
        $this->save();
    }

    public static function demarrer(string $utilisateurId, string $typeActivite, string $activiteId = null, string $typeMorph = null, array $metadata = null): self
    {
        return self::create([
            'utilisateur_id' => $utilisateurId,
            'type_activite' => $typeActivite,
            'activite_id' => $activiteId,
            'type_activite_morph' => $typeMorph,
            'debut_a' => now(),
            'metadata' => $metadata,
        ]);
    }
}
