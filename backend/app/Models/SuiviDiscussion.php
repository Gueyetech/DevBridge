<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuiviDiscussion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'suivis_discussions';

    protected $fillable = [
        'discussion_id',
        'utilisateur_id',
        'notifications_actives',
    ];

    protected $casts = [
        'notifications_actives' => 'boolean',
    ];

    /**
     * La discussion suivie
     */
    public function discussion(): BelongsTo
    {
        return $this->belongsTo(DiscussionForum::class, 'discussion_id');
    }

    /**
     * L'utilisateur qui suit
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }
}
