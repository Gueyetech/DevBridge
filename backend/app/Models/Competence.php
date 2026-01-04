<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competence extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'competences';
    
    protected $fillable = [
        'nom',
        'slug',
        'categorie',
        'description',
        'icone',
        'niveau',
    ];

    // ⚡ RELATIONS
    public function utilisateurs()
    {
        return $this->belongsToMany(Utilisateur::class, 'competences_utilisateurs', 'competence_id', 'utilisateur_id')
                    ->withPivot(['niveau_maitrise', 'valide_a', 'valide_par', 'methode_validation', 'preuves'])
                    ->withTimestamps();
    }

    // 🎯 SCOPES
    public function scopeFrontend($query)
    {
        return $query->where('categorie', 'frontend');
    }

    public function scopeBackend($query)
    {
        return $query->where('categorie', 'backend');
    }

    public function scopeBaseDeDonnees($query)
    {
        return $query->where('categorie', 'base_de_donnees');
    }

    public function scopeDevops($query)
    {
        return $query->where('categorie', 'devops');
    }

    public function scopePourNiveau($query, string $niveau)
    {
        return $query->where('niveau', $niveau);
    }

    // ✨ ATTRIBUTS
    public function getNombreUtilisateursAttribute(): int
    {
        return $this->utilisateurs()->count();
    }
}
