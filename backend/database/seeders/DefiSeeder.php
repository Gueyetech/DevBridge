<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Defi;
use App\Models\Utilisateur;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DefiSeeder extends Seeder
{
    public function run(): void
    {
        $defis = [
            [
                'titre' => 'Semaine du code',
                'description' => 'Participer à un challenge de programmation.',
                'points_recompense' => 100,
                'experience_recompense' => 200,
            ],
            [
                'titre' => 'Marathon mentorat',
                'description' => 'Animer 3 sessions de mentorat en 7 jours.',
                'points_recompense' => 200,
                'experience_recompense' => 400,
            ],
        ];
        foreach ($defis as $defiData) {
            Defi::create([
                'id' => Str::uuid(),
                'titre' => $defiData['titre'],
                'slug' => Str::slug($defiData['titre']) . '-' . Str::random(5),
                'description' => $defiData['description'],
                'points_recompense' => $defiData['points_recompense'],
                'experience_recompense' => $defiData['experience_recompense'],
                // Valeurs par défaut pour les champs obligatoires
                'type' => 'special',
                'difficulte' => 'debutant',
                'date_debut' => Carbon::now()->subDays(rand(10, 20)),
                'date_fin' => Carbon::now()->addDays(rand(1, 10)),
                'est_actif' => true,
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
