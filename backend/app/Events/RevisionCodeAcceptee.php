<?php

namespace App\Events;

use App\Models\DemandeRevisionCode;
use App\Models\Utilisateur;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RevisionCodeAcceptee
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $demande;
    public $mentor;

    public function __construct(DemandeRevisionCode $demande, Utilisateur $mentor)
    {
        $this->demande = $demande;
        $this->mentor = $mentor;
    }
}
