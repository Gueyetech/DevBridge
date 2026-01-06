<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LogActivite;
use App\Models\Utilisateur;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LogActiviteSeeder extends Seeder
{
    public function run(): void
    {
        $utilisateurs = Utilisateur::all();
        $actions = ['connexion', 'parcours_termine', 'quiz_reussi', 'session_mentorat', 'projet_rejoint'];
        foreach ($utilisateurs as $user) {
            for ($i = 0; $i < rand(2, 5); $i++) {
                LogActivite::create([
                    'id' => Str::uuid(),
                    'utilisateur_id' => $user->id,
                    'action' => $actions[array_rand($actions)],
                    'created_at' => Carbon::now()->subDays(rand(1, 60)),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
