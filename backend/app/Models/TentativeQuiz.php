<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TentativeQuiz extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tentatives_quiz';
    
    protected $fillable = [
        'utilisateur_id',
        'quiz_id',
        'score',
        'score_maximum',
        'est_reussi',
        'temps_passe_secondes',
        'reponses',
        'commence_a',
        'termine_a',
    ];

    protected function casts(): array
    {
        return [
            'est_reussi' => 'boolean',
            'score' => 'integer',
            'score_maximum' => 'integer',
            'temps_passe_secondes' => 'integer',
            'reponses' => 'array',
            'commence_a' => 'datetime',
            'termine_a' => 'datetime',
        ];
    }

    // ⚡ RELATIONS
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    // ✨ ATTRIBUTS
    public function getPourcentageAttribute(): float
    {
        if ($this->score_maximum === 0) {
            return 0;
        }
        
        return round(($this->score / $this->score_maximum) * 100, 2);
    }

    public function getTempsPasseFormateAttribute(): string
    {
        $minutes = floor($this->temps_passe_secondes / 60);
        $secondes = $this->temps_passe_secondes % 60;
        
        return sprintf('%02d:%02d', $minutes, $secondes);
    }
}
