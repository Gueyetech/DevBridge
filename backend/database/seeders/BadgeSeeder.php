<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Badge;
use Illuminate\Support\Str;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'nom' => 'Débutant',
                'slug' => 'debutant',
                'description' => 'Attribué à la première connexion.',
                'icone' => 'debutant.png',
                'rarete' => 'commun',
                'conditions_obtention' => json_encode(['connexion']),
                'points_recompense' => 10,
                'experience_recompense' => 20,
            ],
            [
                'nom' => 'Explorateur',
                'slug' => 'explorateur',
                'description' => 'A terminé son premier parcours.',
                'icone' => 'explorateur.png',
                'rarete' => 'peu_commun',
                'conditions_obtention' => json_encode(['parcours_termine']),
                'points_recompense' => 30,
                'experience_recompense' => 50,
            ],
            [
                'nom' => 'Mentor engagé',
                'slug' => 'mentor-engage',
                'description' => 'A animé 5 sessions de mentorat.',
                'icone' => 'mentor.png',
                'rarete' => 'rare',
                'conditions_obtention' => json_encode(['5_sessions_mentorat']),
                'points_recompense' => 100,
                'experience_recompense' => 200,
            ],
            [
                'nom' => 'Champion du code',
                'slug' => 'champion-code',
                'description' => 'A réussi 10 exercices de code.',
                'icone' => 'code.png',
                'rarete' => 'epique',
                'conditions_obtention' => json_encode(['10_exercices_code']),
                'points_recompense' => 200,
                'experience_recompense' => 400,
            ],
            [
                'nom' => 'Légende',
                'slug' => 'legende',
                'description' => 'A atteint le niveau 10.',
                'icone' => 'legende.png',
                'rarete' => 'legendaire',
                'conditions_obtention' => json_encode(['niveau_10']),
                'points_recompense' => 500,
                'experience_recompense' => 1000,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate([
                'slug' => $badge['slug'],
            ], $badge);
        }
    }
}
