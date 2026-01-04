<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'questions';
    
    protected $fillable = [
        'quiz_id',
        'texte',
        'type',
        'options',
        'reponses_correctes',
        'explication',
        'points',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'reponses_correctes' => 'array',
            'points' => 'integer',
            'ordre' => 'integer',
        ];
    }

    // ⚡ RELATIONS
    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    // ✨ ATTRIBUTS
    public function getEstChoixMultipleAttribute(): bool
    {
        return $this->type === 'choix_multiple';
    }

    public function getEstVraiFauxAttribute(): bool
    {
        return $this->type === 'vrai_faux';
    }

    public function getEstCodeAttribute(): bool
    {
        return $this->type === 'code';
    }

    // 🎮 MÉTHODES
    public function verifierReponse($reponseUtilisateur): bool
    {
        if ($reponseUtilisateur === null) {
            return false;
        }

        $reponsesCorrectes = $this->reponses_correctes ?? [];

        if ($this->type === 'choix_unique' || $this->type === 'vrai_faux') {
            return in_array($reponseUtilisateur, $reponsesCorrectes);
        } elseif ($this->type === 'choix_multiple') {
            if (!is_array($reponseUtilisateur)) {
                return false;
            }
            sort($reponseUtilisateur);
            $correct = $reponsesCorrectes;
            sort($correct);
            return $reponseUtilisateur === $correct;
        }
        
        return false;
    }
}
