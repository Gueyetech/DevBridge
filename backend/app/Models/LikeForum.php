<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LikeForum extends Model
{
    protected $table = 'likes_messages';

    public $incrementing = false;

    protected $fillable = [
        'message_id',
        'discussion_id',
        'utilisateur_id',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(MessageForum::class, 'message_id');
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(DiscussionForum::class, 'discussion_id');
    }
}
