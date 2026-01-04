<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notifications';
    
    protected $fillable = [
        'utilisateur_id',
        'titre',
        'contenu',
        'type',
        'donnees',
        'est_lu',
        'lu_a',
        'lien_action',
    ];

    protected function casts(): array
    {
        return [
            'donnees' => 'array',
            'est_lu' => 'boolean',
            'lu_a' => 'datetime',
        ];
    }

    // ⚡ RELATIONS
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    // 🎯 SCOPES
    public function scopeNonLues($query)
    {
        return $query->where('est_lu', false);
    }

    public function scopeLues($query)
    {
        return $query->where('est_lu', true);
    }

    public function scopePourType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecentes($query, int $jours = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($jours));
    }

    public function scopeSysteme($query)
    {
        return $query->where('type', 'systeme');
    }

    public function scopeMentorat($query)
    {
        return $query->where('type', 'mentorat');
    }

    public function scopeDefi($query)
    {
        return $query->where('type', 'defi');
    }

    public function scopeProjet($query)
    {
        return $query->where('type', 'projet');
    }

    // ✨ ATTRIBUTS
    public function getEstRecenteAttribute(): bool
    {
        return $this->created_at->greaterThan(now()->subDay());
    }

    // 🎮 MÉTHODES
    public function marquerCommeLu(): void
    {
        $this->est_lu = true;
        $this->lu_a = now();
        $this->save();
    }

    public static function creerPour(string $utilisateurId, string $titre, string $contenu, string $type, array $donnees = null, string $lienAction = null): self
    {
        return self::create([
            'utilisateur_id' => $utilisateurId,
            'titre' => $titre,
            'contenu' => $contenu,
            'type' => $type,
            'donnees' => $donnees,
            'lien_action' => $lienAction,
            'est_lu' => false,
        ]);
    }
}
