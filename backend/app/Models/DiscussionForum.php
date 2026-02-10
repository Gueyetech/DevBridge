<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DiscussionForum extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'discussions_forum';

    protected $fillable = [
        'titre',
        'contenu',
        'categorie_id',
        'createur_id',
        'tags',
        'est_resolu',
        'est_epingle',
        'dernier_message_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'est_resolu' => 'boolean',
        'est_epingle' => 'boolean',
        'dernier_message_at' => 'datetime',
    ];

    /**
     * La catégorie de la discussion
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieForum::class, 'categorie_id');
    }

    /**
     * Le créateur de la discussion
     */
    public function createur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'createur_id');
    }

    /**
     * Les messages de la discussion
     */
    public function messages(): HasMany
    {
        return $this->hasMany(MessageForum::class, 'discussion_id');
    }

    /**
     * Le dernier message de la discussion
     */
    public function dernierMessage(): HasOne
    {
        return $this->hasOne(MessageForum::class, 'discussion_id')->latestOfMany();
    }

    /**
     * Les utilisateurs qui suivent cette discussion
     */
    public function suiveurs(): HasMany
    {
        return $this->hasMany(SuiviDiscussion::class, 'discussion_id');
    }

    /**
     * Les likes de la discussion
     */
    public function likes(): HasMany
    {
        return $this->hasMany(LikeForum::class, 'discussion_id');
    }
}
