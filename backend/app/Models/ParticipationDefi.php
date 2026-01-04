<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipationDefi extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'participations_defis';
    
    protected $fillable = [
        'utilisateur_id',
        'defi_id',
        'statut',
        'solution_url',
        'description_solution',
        'score',
        'feedback_jury',
        'inscrit_a',
        'soumis_a',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'inscrit_a' => 'datetime',
            'soumis_a' => 'datetime',
        ];
    }

    // ⚡ RELATIONS
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function defi()
    {
        return $this->belongsTo(Defi::class, 'defi_id');
    }

    // 🎯 SCOPES
    public function scopeInscrits($query)
    {
        return $query->where('statut', 'inscrit');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeSoumis($query)
    {
        return $query->where('statut', 'soumis');
    }

    public function scopeEvalues($query)
    {
        return $query->where('statut', 'evalue');
    }

    public function scopeGagnants($query)
    {
        return $query->where('statut', 'gagnant');
    }

    // ✨ ATTRIBUTS
    public function getEstGagnantAttribute(): bool
    {
        return $this->statut === 'gagnant';
    }

    public function getEstSoumisAttribute(): bool
    {
        return in_array($this->statut, ['soumis', 'evalue', 'gagnant']);
    }

    public function getEstEvalueAttribute(): bool
    {
        return in_array($this->statut, ['evalue', 'gagnant']);
    }

    // 🎮 MÉTHODES
    public function soumettreSolution(string $solutionUrl, string $description = null): void
    {
        $this->solution_url = $solutionUrl;
        $this->description_solution = $description;
        $this->statut = 'soumis';
        $this->soumis_a = now();
        $this->save();
    }

    public function evaluer(int $score, string $feedback = null): void
    {
        $this->score = $score;
        $this->feedback_jury = $feedback;
        $this->statut = 'evalue';
        $this->save();
    }

    public function declarerGagnant(): void
    {
        $this->statut = 'gagnant';
        $this->save();

        $this->utilisateur->ajouterPoints($this->defi->points_recompense);
    }
}
