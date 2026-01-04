<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'messages';

    protected $fillable = [
        'conversation_id',
        'expediteur_id',
        'contenu',
        'type',
    ];

    /**
     * La conversation du message
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * L'expéditeur du message
     */
    public function expediteur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'expediteur_id');
    }

    /**
     * Les fichiers attachés au message
     */
    public function fichiers(): HasMany
    {
        return $this->hasMany(FichierMessage::class, 'message_id');
    }

    /**
     * Les lectures du message
     */
    public function lectures(): HasMany
    {
        return $this->hasMany(LectureMessage::class, 'message_id');
    }
}
