<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mentorat extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'mentorats';
    
    protected $fillable = [
        'mentor_id',
        'etudiant_id',
        'statut',
        'message_demande',
        'message_acceptation',
        'competences_ciblees',
        'objectifs',
        'demande_a',
        'accepte_a',
        'termine_a',
    ];

    protected function casts(): array
    {
        return [
            'competences_ciblees' => 'array',
            'objectifs' => 'array',
            'demande_a' => 'datetime',
            'accepte_a' => 'datetime',
            'termine_a' => 'datetime',
        ];
    }

    // ⚡ RELATIONS
    public function mentor()
    {
        return $this->belongsTo(Utilisateur::class, 'mentor_id');
    }

    public function etudiant()
    {
        return $this->belongsTo(Utilisateur::class, 'etudiant_id');
    }

    public function sessions()
    {
        return $this->hasMany(SessionMentorat::class, 'mentorat_id');
    }

    // 🎯 SCOPES
    public function scopeActifs($query)
    {
        return $query->whereIn('statut', ['accepte', 'en_cours']);
    }

    public function scopeDemandes($query)
    {
        return $query->where('statut', 'demande');
    }

    public function scopeTermines($query)
    {
        return $query->where('statut', 'termine');
    }

    public function scopePourMentor($query, string $mentorId)
    {
        return $query->where('mentor_id', $mentorId);
    }

    public function scopePourEtudiant($query, string $etudiantId)
    {
        return $query->where('etudiant_id', $etudiantId);
    }

    // ✨ ATTRIBUTS
    public function getEstActifAttribute(): bool
    {
        return in_array($this->statut, ['accepte', 'en_cours']);
    }

    public function getEstEnAttenteAttribute(): bool
    {
        return $this->statut === 'demande';
    }

    public function getNombreSessionsAttribute(): int
    {
        return $this->sessions()->count();
    }

    public function getDureeTotaleMinutesAttribute(): int
    {
        return $this->sessions->sum(function($session) {
            if (!$session->date_fin || !$session->date_debut) {
                return 0;
            }
            return $session->date_fin->diffInMinutes($session->date_debut);
        });
    }

    // 🎮 MÉTHODES
    public function accepter(string $message = null): void
    {
        $this->statut = 'accepte';
        $this->accepte_a = now();
        if ($message) {
            $this->message_acceptation = $message;
        }
        $this->save();
    }

    public function terminer(): void
    {
        $this->statut = 'termine';
        $this->termine_a = now();
        $this->save();
    }

    public function annuler(): void
    {
        $this->statut = 'annule';
        $this->save();
    }
}
