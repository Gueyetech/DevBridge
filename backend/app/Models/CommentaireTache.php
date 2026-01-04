<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentaireTache extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'commentaires_taches';
    
    protected $fillable = [
        'tache_id',
        'utilisateur_id',
        'contenu',
        'parent_id',
        'est_resolu',
        'pieces_jointes',
    ];

    protected function casts(): array
    {
        return [
            'est_resolu' => 'boolean',
            'pieces_jointes' => 'array',
        ];
    }

    // ⚡ RELATIONS
    public function tache()
    {
        return $this->belongsTo(Tache::class, 'tache_id');
    }

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function parent()
    {
        return $this->belongsTo(CommentaireTache::class, 'parent_id');
    }

    public function reponses()
    {
        return $this->hasMany(CommentaireTache::class, 'parent_id');
    }

    // 🎯 SCOPES
    public function scopeResolus($query)
    {
        return $query->where('est_resolu', true);
    }

    public function scopeNonResolus($query)
    {
        return $query->where('est_resolu', false);
    }

    public function scopeRacines($query)
    {
        return $query->whereNull('parent_id');
    }

    // ✨ ATTRIBUTS
    public function getNombreReponsesAttribute(): int
    {
        return $this->reponses()->count();
    }

    public function getEstReponseAttribute(): bool
    {
        return $this->parent_id !== null;
    }
}
