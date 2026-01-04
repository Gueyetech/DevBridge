<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificat extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'certificats';

    protected $fillable = [
        'utilisateur_id',
        'competence_id',
        'parcours_id',
        'type',
        'code_verification',
        'date_emission',
        'chemin_fichier',
        'nombre_telechargements',
        'valide_par',
    ];

    protected $casts = [
        'date_emission' => 'datetime',
        'nombre_telechargements' => 'integer',
    ];

    /**
     * L'utilisateur propriétaire du certificat
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    /**
     * La compétence certifiée
     */
    public function competence(): BelongsTo
    {
        return $this->belongsTo(Competence::class, 'competence_id');
    }

    /**
     * Le parcours certifié
     */
    public function parcours(): BelongsTo
    {
        return $this->belongsTo(ParcoursApprentissage::class, 'parcours_id');
    }

    /**
     * Le validateur du certificat
     */
    public function validePar(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'valide_par');
    }
}
