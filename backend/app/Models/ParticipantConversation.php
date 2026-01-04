<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantConversation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'participants_conversations';

    protected $fillable = [
        'conversation_id',
        'utilisateur_id',
        'rejoint_a',
        'quitte_a',
    ];

    protected $casts = [
        'rejoint_a' => 'datetime',
        'quitte_a' => 'datetime',
    ];

    /**
     * La conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * L'utilisateur participant
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }
}
