<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageForum extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'messages_forum';

    protected $fillable = [
        'discussion_id',
        'utilisateur_id',
        'contenu',
        'est_premier_message',
        'est_solution',
    ];

    protected $casts = [
        'est_premier_message' => 'boolean',
        'est_solution' => 'boolean',
    ];

    /**
     * La discussion du message
     */
    public function discussion(): BelongsTo
    {
        return $this->belongsTo(DiscussionForum::class, 'discussion_id');
    }

    /**
     * L'auteur du message
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    /**
     * Les likes du message
     */
    public function likes(): HasMany
    {
        return $this->hasMany(LikeForum::class, 'message_id');
    }
}
