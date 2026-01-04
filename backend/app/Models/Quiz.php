<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'quiz';
    
    protected $fillable = [
        'module_id',
        'lecon_id',
        'titre',
        'description',
        'duree_limite_minutes',
        'score_minimum_reussite',
        'tentatives_maximum',
        'est_actif',
    ];

    protected function casts(): array
    {
        return [
            'duree_limite_minutes' => 'integer',
            'score_minimum_reussite' => 'integer',
            'tentatives_maximum' => 'integer',
            'est_actif' => 'boolean',
        ];
    }

    // ⚡ RELATIONS
    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function lecon()
    {
        return $this->belongsTo(Lecon::class, 'lecon_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'quiz_id')->orderBy('ordre');
    }

    public function tentatives()
    {
        return $this->hasMany(TentativeQuiz::class, 'quiz_id');
    }

    public function utilisateursAyantTente()
    {
        return $this->belongsToMany(Utilisateur::class, 'tentatives_quiz', 'quiz_id', 'utilisateur_id')
                    ->withPivot(['score', 'score_maximum', 'est_reussi', 'temps_passe_secondes', 'commence_a', 'termine_a', 'reponses'])
                    ->withTimestamps();
    }

    // 🎯 SCOPES
    public function scopeActifs($query)
    {
        return $query->where('est_actif', true);
    }

    // ✨ ATTRIBUTS
    public function getScoreMaximumAttribute(): int
    {
        return $this->questions()->sum('points');
    }

    public function getNombreQuestionsAttribute(): int
    {
        return $this->questions()->count();
    }

    public function getPourcentageReussiteAttribute(): float
    {
        $tentativesReussies = $this->tentatives()->where('est_reussi', true)->count();
        $tentativesTotales = $this->tentatives()->count();
        
        if ($tentativesTotales === 0) {
            return 0;
        }
        
        return round(($tentativesReussies / $tentativesTotales) * 100, 2);
    }

    // 🎮 MÉTHODES
    public function evaluerReponses(array $reponsesUtilisateur): array
    {
        $score = 0;
        $corrections = [];
        
        foreach ($this->questions as $question) {
            $reponseCorrecte = $question->verifierReponse($reponsesUtilisateur[$question->id] ?? null);
            
            if ($reponseCorrecte) {
                $score += $question->points;
            }
            
            $corrections[] = [
                'question_id' => $question->id,
                'reponse_utilisateur' => $reponsesUtilisateur[$question->id] ?? null,
                'reponse_correcte' => $question->reponses_correctes,
                'est_correct' => $reponseCorrecte,
                'points' => $question->points,
                'explication' => $question->explication,
            ];
        }
        
        $scoreMaximum = $this->score_maximum;
        $estReussi = $scoreMaximum > 0 && (($score / $scoreMaximum) * 100) >= $this->score_minimum_reussite;
        
        return [
            'score' => $score,
            'score_maximum' => $scoreMaximum,
            'est_reussi' => $estReussi,
            'pourcentage' => $scoreMaximum > 0 ? round(($score / $scoreMaximum) * 100, 2) : 0,
            'corrections' => $corrections,
        ];
    }
}
