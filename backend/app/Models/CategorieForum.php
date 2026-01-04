<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieForum extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'categories_forum';

    protected $fillable = [
        'nom',
        'description',
        'slug',
        'icone',
        'couleur',
        'ordre',
        'est_actif',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'est_actif' => 'boolean',
    ];

    /**
     * Les discussions de cette catégorie
     */
    public function discussions(): HasMany
    {
        return $this->hasMany(DiscussionForum::class, 'categorie_id');
    }
}
