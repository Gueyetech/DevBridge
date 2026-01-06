<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Projet;
use App\Models\Utilisateur;
use App\Models\MembreProjet;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjetSeeder extends Seeder
{
    public function run(): void
    {
        // Supprimer les projets existants pour éviter les doublons de slug
        \App\Models\MembreProjet::query()->delete();
        \App\Models\Projet::query()->delete();

        $utilisateurs = Utilisateur::inRandomOrder()->take(10)->get();
        $projets = [
            [
                'titre' => 'Plateforme de mentorat',
                'description' => 'Développer une plateforme de mise en relation mentor/étudiant.',
                'technologies' => ['Laravel', 'React'],
                'difficulte' => 'intermediaire',
                'statut' => 'ouvert',
            ],
            [
                'titre' => 'Application mobile quiz',
                'description' => 'Créer une app mobile pour réviser avec des quiz.',
                'technologies' => ['Flutter'],
                'difficulte' => 'debutant',
                'statut' => 'en_cours',
            ],
            [
                'titre' => 'Dashboard analytique',
                'description' => 'Visualiser les statistiques d’apprentissage.',
                'technologies' => ['Next.js'],
                'difficulte' => 'avance',
                'statut' => 'brouillon',
            ],
        ];
        $createur = $utilisateurs->first();
        foreach ($projets as $projetData) {
            $id = Str::uuid();
            $slug = Str::slug($projetData['titre']) . '-' . substr($id, 0, 8);
            $projet = Projet::create([
                'id' => $id,
                'titre' => $projetData['titre'],
                'slug' => $slug,
                'description' => $projetData['description'],
                'technologies' => json_encode($projetData['technologies']),
                'difficulte' => $projetData['difficulte'],
                'statut' => $projetData['statut'],
                'createur_id' => $createur ? $createur->id : $utilisateurs->random()->id,
                'created_at' => Carbon::now()->subDays(rand(1, 60)),
                'updated_at' => Carbon::now(),
            ]);
            // Ajouter 2 à 4 membres par projet
            $membres = $utilisateurs->random(rand(2, 4));
            foreach ($membres as $membre) {
                $roles = ['mainteneur', 'contributeur', 'relecteur'];
                $role = $membre->id === $projet->createur_id ? 'createur' : $roles[array_rand($roles)];
                MembreProjet::create([
                    'id' => Str::uuid(),
                    'projet_id' => $projet->id,
                    'utilisateur_id' => $membre->id,
                    'role' => $role,
                    'rejoint_a' => Carbon::now()->subDays(rand(1, 60)),
                    'score_contribution' => rand(0, 100),
                    'est_actif' => true,
                    'created_at' => Carbon::now()->subDays(rand(1, 60)),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
