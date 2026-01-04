<?php

namespace Database\Seeders;

use App\Models\Utilisateur;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer un administrateur par défaut
        Utilisateur::factory()->create([
            'prenom' => 'Admin',
            'nom' => 'DevBridge',
            'email' => 'admin@devbridge.com',
            'role' => 'administrateur',
        ]);

        // Créer un mentor de test
        Utilisateur::factory()->create([
            'prenom' => 'Mentor',
            'nom' => 'Test',
            'email' => 'mentor@devbridge.com',
            'role' => 'mentor',
        ]);

        // Créer un étudiant de test
        Utilisateur::factory()->create([
            'prenom' => 'Etudiant',
            'nom' => 'Test',
            'email' => 'etudiant@devbridge.com',
            'role' => 'etudiant',
        ]);
    }
}
