<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projet extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'projets';
    
    protected $fillable = [
        'titre',
        'slug',
        'description',
        'difficulte',
        'technologies',
        'statut',
        'repository_github',
        'date_limite',
        'nombre_maximum_participants',
        'points_recompense',
        'createur_id',
    ];

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'date_limite' => 'datetime',
            'nombre_maximum_participants' => 'integer',
            'points_recompense' => 'integer',
        ];
    }

    // ⚡ RELATIONS
    public function createur()
    {
        return $this->belongsTo(Utilisateur::class, 'createur_id');
    }

    public function membres()
    {
        return $this->belongsToMany(Utilisateur::class, 'membres_projets', 'projet_id', 'utilisateur_id')
                    ->withPivot(['role', 'rejoint_a', 'score_contribution', 'est_actif'])
                    ->withTimestamps();
    }

    public function membresActifs()
    {
        return $this->membres()->wherePivot('est_actif', true);
    }

    public function taches()
    {
        return $this->hasMany(Tache::class, 'projet_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(FeedbackMentor::class, 'projet_id');
    }

    // 🎯 SCOPES
    public function scopeOuverts($query)
    {
        return $query->where('statut', 'ouvert');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeTermines($query)
    {
        return $query->where('statut', 'termine');
    }

    public function scopePourDifficulte($query, string $difficulte)
    {
        return $query->where('difficulte', $difficulte);
    }

    public function scopeAvecTechnologie($query, string $technologie)
    {
        return $query->whereJsonContains('technologies', $technologie);
    }

    // ✨ ATTRIBUTS
    public function getNombreMembresAttribute(): int
    {
        return $this->membres()->count();
    }

    public function getNombreTachesAttribute(): int
    {
        return $this->taches()->count();
    }

    public function getNombreTachesTermineesAttribute(): int
    {
        return $this->taches()->where('statut', 'termine')->count();
    }

    public function getPourcentageCompletionAttribute(): float
    {
        $total = $this->nombre_taches;
        $terminees = $this->nombre_taches_terminees;
        
        if ($total === 0) {
            return 0;
        }
        
        return round(($terminees / $total) * 100, 2);
    }

    public function getEstCompletAttribute(): bool
    {
        return $this->nombre_membres >= $this->nombre_maximum_participants;
    }

    public function getEstEnRetardAttribute(): bool
    {
        return $this->date_limite && now()->greaterThan($this->date_limite) && $this->statut !== 'termine';
    }

    // 🎮 MÉTHODES
    public function ajouterMembre(string $utilisateurId, string $role = 'contributeur'): bool
    {
        if ($this->est_complet) {
            return false;
        }
        
        $this->membres()->attach($utilisateurId, [
            'role' => $role,
            'rejoint_a' => now(),
            'est_actif' => true,
        ]);
        
        return true;
    }

    public function completerProjet(): void
    {
        $this->statut = 'termine';
        $this->save();
        
        $pointsParMembre = $this->nombre_membres > 0 
            ? floor($this->points_recompense / $this->nombre_membres) 
            : 0;
        
        foreach ($this->membres as $membre) {
            $membre->ajouterPoints($pointsParMembre);
        }
    }
}
