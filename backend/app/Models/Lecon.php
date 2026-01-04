<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecon extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'lecons';
    
    protected $fillable = [
        'module_id',
        'titre',
        'slug',
        'type_contenu',
        'contenu',
        'url_video',
        'ressources',
        'ordre',
        'duree_estimee_minutes',
        'est_gratuit',
    ];

    protected function casts(): array
    {
        return [
            'ressources' => 'array',
            'duree_estimee_minutes' => 'integer',
            'ordre' => 'integer',
            'est_gratuit' => 'boolean',
        ];
    }

    // ⚡ RELATIONS
    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class, 'lecon_id');
    }

    public function progressionsUtilisateurs()
    {
        return $this->hasMany(ProgressionLecon::class, 'lecon_id');
    }

    public function utilisateursAyantTermine()
    {
        return $this->belongsToMany(Utilisateur::class, 'progressions_lecons', 'lecon_id', 'utilisateur_id')
                    ->wherePivot('est_termine', true)
                    ->withPivot(['temps_passe_secondes', 'commence_a', 'termine_a'])
                    ->withTimestamps();
    }

    // 🎯 SCOPES
    public function scopeGratuites($query)
    {
        return $query->where('est_gratuit', true);
    }

    public function scopeVideos($query)
    {
        return $query->where('type_contenu', 'video');
    }

    public function scopeArticles($query)
    {
        return $query->where('type_contenu', 'article');
    }

    // ✨ ATTRIBUTS
    public function getParcoursAttribute()
    {
        return $this->module->parcours;
    }

    public function getEstVideoAttribute(): bool
    {
        return $this->type_contenu === 'video';
    }

    public function getEstExerciceAttribute(): bool
    {
        return $this->type_contenu === 'exercice';
    }

    public function getDureeFormateeAttribute(): string
    {
        $heures = floor($this->duree_estimee_minutes / 60);
        $minutes = $this->duree_estimee_minutes % 60;
        
        if ($heures > 0) {
            return $heures . 'h' . ($minutes > 0 ? ' ' . $minutes . 'min' : '');
        }
        
        return $minutes . 'min';
    }

    // 🎮 MÉTHODES
    public function marquerTermineeParUtilisateur(string $utilisateurId)
    {
        return ProgressionLecon::updateOrCreate(
            ['utilisateur_id' => $utilisateurId, 'lecon_id' => $this->id],
            ['est_termine' => true, 'termine_a' => now()]
        );
    }
}
