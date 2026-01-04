<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'modules';
    
    protected $fillable = [
        'parcours_id',
        'titre',
        'slug',
        'description',
        'ordre',
        'duree_estimee_minutes',
        'objectifs',
    ];

    protected function casts(): array
    {
        return [
            'objectifs' => 'array',
            'duree_estimee_minutes' => 'integer',
            'ordre' => 'integer',
        ];
    }

    // ⚡ RELATIONS
    public function parcours()
    {
        return $this->belongsTo(ParcoursApprentissage::class, 'parcours_id');
    }

    public function lecons()
    {
        return $this->hasMany(Lecon::class, 'module_id')->orderBy('ordre');
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class, 'module_id');
    }

    // ✨ ATTRIBUTS
    public function getDureeTotaleAttribute(): int
    {
        $dureeLecons = $this->lecons->sum('duree_estimee_minutes');
        $dureeQuiz = $this->quiz ? 15 : 0;
        
        return $dureeLecons + $dureeQuiz;
    }

    public function getNombreLeconsAttribute(): int
    {
        return $this->lecons()->count();
    }

    public function getProchainOrdreLeconAttribute(): int
    {
        $derniereLecon = $this->lecons()->orderBy('ordre', 'desc')->first();
        return $derniereLecon ? $derniereLecon->ordre + 1 : 1;
    }
}
