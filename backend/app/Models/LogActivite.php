<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogActivite extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'logs_activite';
    
    protected $fillable = [
        'utilisateur_id',
        'action',
        'modele',
        'modele_id',
        'donnees_avant',
        'donnees_apres',
        'metadata',
        'adresse_ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'donnees_avant' => 'array',
            'donnees_apres' => 'array',
            'metadata' => 'array',
            'modele_id' => 'string',
        ];
    }

    // ⚡ RELATIONS
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    // 🎯 SCOPES
    public function scopePourUtilisateur($query, string $utilisateurId)
    {
        return $query->where('utilisateur_id', $utilisateurId);
    }

    public function scopePourAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopePourModele($query, string $modele, string $modeleId = null)
    {
        $query = $query->where('modele', $modele);
        
        if ($modeleId !== null) {
            $query->where('modele_id', $modeleId);
        }

        return $query;
    }

    public function scopeRecents($query, int $jours = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($jours));
    }

    public function scopeAujourdhui($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    // ✨ ATTRIBUTS
    public function getChangementsAttribute(): array
    {
        if (!$this->donnees_avant || !$this->donnees_apres) {
            return [];
        }

        $changements = [];
        
        foreach ($this->donnees_apres as $cle => $nouvelleValeur) {
            $ancienneValeur = $this->donnees_avant[$cle] ?? null;
            
            if ($ancienneValeur !== $nouvelleValeur) {
                $changements[$cle] = [
                    'avant' => $ancienneValeur,
                    'apres' => $nouvelleValeur,
                ];
            }
        }

        return $changements;
    }

    // 🎮 MÉTHODES STATIQUES
    public static function enregistrer(
        string $action,
        string $modele = null,
        string $modeleId = null,
        array $donneesAvant = null,
        array $donneesApres = null,
        array $metadata = null,
        string $utilisateurId = null
    ): self {
        return self::create([
            'utilisateur_id' => $utilisateurId ?? auth()->id(),
            'action' => $action,
            'modele' => $modele,
            'modele_id' => $modeleId,
            'donnees_avant' => $donneesAvant,
            'donnees_apres' => $donneesApres,
            'metadata' => $metadata,
            'adresse_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function creation(string $modele, string $modeleId, array $donnees, string $utilisateurId = null): self
    {
        return self::enregistrer('creation', $modele, $modeleId, null, $donnees, null, $utilisateurId);
    }

    public static function modification(string $modele, string $modeleId, array $donneesAvant, array $donneesApres, string $utilisateurId = null): self
    {
        return self::enregistrer('modification', $modele, $modeleId, $donneesAvant, $donneesApres, null, $utilisateurId);
    }

    public static function suppression(string $modele, string $modeleId, array $donnees, string $utilisateurId = null): self
    {
        return self::enregistrer('suppression', $modele, $modeleId, $donnees, null, null, $utilisateurId);
    }

    public static function connexion(string $utilisateurId): self
    {
        return self::enregistrer('connexion', 'Utilisateur', $utilisateurId, null, null, null, $utilisateurId);
    }

    public static function deconnexion(string $utilisateurId): self
    {
        return self::enregistrer('deconnexion', 'Utilisateur', $utilisateurId, null, null, null, $utilisateurId);
    }
}
