<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\Utilisateur;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $utilisateurs = Utilisateur::all();
        $types = ['nouveau_message', 'badge_obtenu', 'nouvelle_session', 'projet_invitation'];
        foreach ($utilisateurs as $user) {
            for ($i = 0; $i < rand(1, 3); $i++) {
                Notification::create([
                    'id' => Str::uuid(),
                    'utilisateur_id' => $user->id,
                    'titre' => 'Notification',
                    'type' => 'systeme',
                    'contenu' => 'Notification test pour ' . $user->prenom,
                    'est_lu' => rand(0, 1),
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
