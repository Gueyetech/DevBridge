<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'configurations';
    
    protected $fillable = [
        'cle',
        'valeur',
        'type',
        'description',
        'est_modifiable',
        'categorie',
    ];

    protected function casts(): array
    {
        return [
            'est_modifiable' => 'boolean',
        ];
    }

    // 🎯 SCOPES
    public function scopeModifiables($query)
    {
        return $query->where('est_modifiable', true);
    }

    public function scopePourCategorie($query, string $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    // ✨ ATTRIBUTS
    public function getValeurTypeeAttribute()
    {
        return match($this->type) {
            'integer' => (int) $this->valeur,
            'boolean' => filter_var($this->valeur, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->valeur, true),
            'float' => (float) $this->valeur,
            default => $this->valeur,
        };
    }

    // 🎮 MÉTHODES STATIQUES
    public static function obtenir(string $cle, $defaut = null)
    {
        $config = self::where('cle', $cle)->first();
        
        if (!$config) {
            return $defaut;
        }

        return $config->valeur_typee;
    }

    public static function definir(string $cle, $valeur, string $type = 'string', string $description = null, string $categorie = 'general'): self
    {
        $valeurString = match($type) {
            'json' => json_encode($valeur),
            'boolean' => $valeur ? 'true' : 'false',
            default => (string) $valeur,
        };

        return self::updateOrCreate(
            ['cle' => $cle],
            [
                'valeur' => $valeurString,
                'type' => $type,
                'description' => $description,
                'categorie' => $categorie,
            ]
        );
    }

    public static function parCategorie(string $categorie): array
    {
        return self::pourCategorie($categorie)
            ->get()
            ->mapWithKeys(fn($config) => [$config->cle => $config->valeur_typee])
            ->toArray();
    }
}
