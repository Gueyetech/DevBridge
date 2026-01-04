<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemandeRevisionCode extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'demandes_revision_code';

    protected $fillable = [
        'etudiant_id',
        'projet_id',
        'tache_id',
        'titre',
        'description',
        'statut',
        'urgence',
        'technologies',
        'competences_ciblees',
        'mentor_assignee',
    ];

    protected $casts = [
        'technologies' => 'array',
        'competences_ciblees' => 'array',
    ];

    /**
     * L'étudiant qui a fait la demande
     */
    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'etudiant_id');
    }

    /**
     * Le projet concerné
     */
    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class, 'projet_id');
    }

    /**
     * La tâche concernée
     */
    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class, 'tache_id');
    }

    /**
     * Les révisions de cette demande
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(RevisionCode::class, 'demande_id');
    }

    /**
     * Les fichiers de la demande
     */
    public function fichiers(): HasMany
    {
        return $this->hasMany(FichierRevisionCode::class, 'demande_id');
    }
}
