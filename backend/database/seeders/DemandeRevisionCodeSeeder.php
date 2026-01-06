<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemandeRevisionCodeSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer un étudiant et un projet existant
        $etudiant = DB::table('utilisateurs')->where('role', 'etudiant')->first();
        $projet = DB::table('projets')->first();
        $tache = DB::table('taches')->first();
        $mentor = DB::table('utilisateurs')->where('email', 'mariama.gueye@devbridge.sn')->first();

        if ($etudiant && $projet && $mentor) {
            for ($i = 1; $i <= 3; $i++) {
                $demandeId = Str::uuid();
                DB::table('demandes_revision_code')->insert([
                    'id' => $demandeId,
                    'etudiant_id' => $etudiant->id,
                    'projet_id' => $projet->id,
                    'tache_id' => $tache ? $tache->id : null,
                    'titre' => "Demande de révision #$i",
                    'description' => "Merci de réviser mon code pour le projet {$projet->titre}.",
                    'statut' => 'en_attente',
                    'urgence' => ['normale', 'haute', 'critique'][($i-1)%3],
                    'technologies' => json_encode(['Laravel', 'React']),
                    'competences_ciblees' => json_encode(['frontend', 'backend']),
                    'created_at' => Carbon::now()->subDays($i*2),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
