<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionMentorat extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sessions_mentorat';
    
    protected $fillable = [
        'mentorat_id',
        'titre',
        'description',
        'date_debut',
        'date_fin',
        'statut',
        'lien_visioconference',
        'notes',
        'note_etudiant',
        'note_mentor',
        'feedback_etudiant',
        'feedback_mentor',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'note_etudiant' => 'integer',
            'note_mentor' => 'integer',
        ];
    }

    // ⚡ RELATIONS
    public function mentorat()
    {
        return $this->belongsTo(Mentorat::class, 'mentorat_id');
    }

    // 🎯 SCOPES
    public function scopePlanifiees($query)
    {
        return $query->where('statut', 'planifie');
    }

    public function scopeTerminees($query)
    {
        return $query->where('statut', 'termine');
    }

    public function scopeAVenir($query)
    {
        return $query->where('date_debut', '>', now())->where('statut', 'planifie');
    }

    public function scopePassees($query)
    {
        return $query->where('date_fin', '<', now());
    }

    // ✨ ATTRIBUTS
    public function getDureeMinutesAttribute(): int
    {
        if (!$this->date_debut || !$this->date_fin) {
            return 0;
        }
        
        return $this->date_fin->diffInMinutes($this->date_debut);
    }

    public function getEstTermineeAttribute(): bool
    {
        return $this->statut === 'termine';
    }

    public function getEstPlanifieeAttribute(): bool
    {
        return $this->statut === 'planifie';
    }

    public function getEstEnCoursAttribute(): bool
    {
        return $this->statut === 'en_cours';
    }

    public function getNoteMoyenneAttribute(): ?float
    {
        if ($this->note_etudiant === null && $this->note_mentor === null) {
            return null;
        }

        $notes = array_filter([$this->note_etudiant, $this->note_mentor], fn($n) => $n !== null);
        return count($notes) > 0 ? array_sum($notes) / count($notes) : null;
    }

    // 🎮 MÉTHODES
    public function demarrer(): void
    {
        $this->statut = 'en_cours';
        $this->save();
    }

    public function terminer(): void
    {
        $this->statut = 'termine';
        $this->save();
    }

    public function annuler(): void
    {
        $this->statut = 'annule';
        $this->save();
    }
}
