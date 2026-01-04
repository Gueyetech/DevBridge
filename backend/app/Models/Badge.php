<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'badges';
    
    protected $fillable = [
        'nom',
        'slug',
        'description',
        'icone',
        'rarete',
        'conditions_obtention',
        'points_recompense',
        'experience_recompense',
    ];

    protected function casts(): array
    {
        return [
            'conditions_obtention' => 'array',
            'points_recompense' => 'integer',
            'experience_recompense' => 'integer',
        ];
    }

   
    public function utilisateurs()
    {
        return $this->belongsToMany(Utilisateur::class, 'badges_utilisateurs', 'badge_id', 'utilisateur_id')
                    ->withPivot(['obtenu_a', 'raison_obtention'])
                    ->withTimestamps();
    }

   
    public function scopeCommuns($query)
    {
        return $query->where('rarete', 'commun');
    }

    public function scopePeuCommuns($query)
    {
        return $query->where('rarete', 'peu_commun');
    }

    public function scopeRares($query)
    {
        return $query->where('rarete', 'rare');
    }

    public function scopeEpiques($query)
    {
        return $query->where('rarete', 'epique');
    }

    public function scopeLegendaires($query)
    {
        return $query->where('rarete', 'legendaire');
    }

    // ✨ ATTRIBUTS
    public function getNombreDetenteursAttribute(): int
    {
        return $this->utilisateurs()->count();
    }

    public function getEstRareAttribute(): bool
    {
        return in_array($this->rarete, ['rare', 'epique', 'legendaire']);
    }

    public function getRecompenseTotaleAttribute(): int
    {
        return $this->points_recompense + $this->experience_recompense;
    }

    // 🎮 MÉTHODES
    public function attribuerA(string $utilisateurId, string $raison = null): void
    {
        if (!$this->utilisateurs()->where('utilisateur_id', $utilisateurId)->exists()) {
            $this->utilisateurs()->attach($utilisateurId, [
                'obtenu_a' => now(),
                'raison_obtention' => $raison,
            ]);

            $utilisateur = Utilisateur::find($utilisateurId);
            if ($utilisateur) {
                $utilisateur->ajouterPoints($this->points_recompense);
            }
        }
    }
}
