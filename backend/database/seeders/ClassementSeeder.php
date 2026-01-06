<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classement;
use App\Models\Utilisateur;
use Illuminate\Support\Str;

class ClassementSeeder extends Seeder
{
    public function run(): void
    {
        $utilisateurs = Utilisateur::inRandomOrder()->take(10)->get();
        $periode = 'global';
        $date_reference = now()->toDateString();
        foreach ($utilisateurs as $index => $user) {
            Classement::create([
                'id' => Str::uuid(),
                'utilisateur_id' => $user->id,
                'position' => $index + 1,
                'points_totaux' => rand(100, 2000),
                'experience_totale' => rand(0, 5000),
                'badges_obtenus' => rand(0, 10),
                'projets_termines' => rand(0, 5),
                'defis_gagnes' => rand(0, 3),
                'periode' => $periode,
                'date_reference' => $date_reference,
            ]);
        }
    }
}
