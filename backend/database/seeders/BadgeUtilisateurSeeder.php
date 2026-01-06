<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Badge;
use App\Models\Utilisateur;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BadgeUtilisateurSeeder extends Seeder
{
    public function run(): void
    {
        $utilisateurs = Utilisateur::all();
        $badges = Badge::all();
        if ($utilisateurs->isEmpty() || $badges->isEmpty()) return;

        foreach ($utilisateurs as $utilisateur) {
            // Chaque utilisateur reçoit 1 à 3 badges aléatoires
            $badgesAttribues = $badges->random(rand(1, min(3, $badges->count())));
            foreach ($badgesAttribues as $badge) {
                DB::table('badges_utilisateurs')->updateOrInsert([
                    'utilisateur_id' => $utilisateur->id,
                    'badge_id' => $badge->id,
                ], [
                    'id' => Str::uuid(),
                    'obtenu_a' => Carbon::now()->subDays(rand(1, 100)),
                    'raison_obtention' => 'Attribué automatiquement pour test',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
