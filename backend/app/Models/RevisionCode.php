<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionCode extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'revisions_code';

    protected $fillable = [
        'demande_id',
        'mentor_id',
        'statut',
        'commentaires',
        'points_positifs',
        'points_amelioration',
        'note_generale',
        'accepte_a',
        'refuse_a',
        'termine_a',
    ];

    protected $casts = [
        'commentaires' => 'array',
        'points_positifs' => 'array',
        'points_amelioration' => 'array',
        'note_generale' => 'integer',
        'accepte_a' => 'datetime',
        'refuse_a' => 'datetime',
        'termine_a' => 'datetime',
    ];

    /**
     * La demande de révision
     */
    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeRevisionCode::class, 'demande_id');
    }

    /**
     * Le mentor qui fait la révision
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'mentor_id');
    }
}
