<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MentorSenegalSeeder extends Seeder
{
    // Nettoyage pour éviter les doublons
        public function run(): void
        {
            // S'assurer que les compétences frontend/backend existent
            $competencesFrontend = DB::table('competences')->where('categorie', 'frontend')->first();
            if (!$competencesFrontend) {
                $competencesFrontendId = Str::uuid();
                DB::table('competences')->insert([
                    'id' => $competencesFrontendId,
                    'nom' => 'Développement Frontend',
                    'slug' => 'frontend',
                    'categorie' => 'frontend',
                    'description' => 'Compétence en développement frontend',
                    'icone' => null,
                    'niveau' => 'debutant',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            } else {
                $competencesFrontendId = $competencesFrontend->id;
            }
            $competencesBackend = DB::table('competences')->where('categorie', 'backend')->first();
            if (!$competencesBackend) {
                $competencesBackendId = Str::uuid();
                DB::table('competences')->insert([
                    'id' => $competencesBackendId,
                    'nom' => 'Développement Backend',
                    'slug' => 'backend',
                    'categorie' => 'backend',
                    'description' => 'Compétence en développement backend',
                    'icone' => null,
                    'niveau' => 'debutant',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            } else {
                $competencesBackendId = $competencesBackend->id;
            }

            // Nettoyage pour éviter les doublons
            DB::table('sessions_mentorat')->delete();
            DB::table('mentorats')->delete();
            DB::table('utilisateurs')->whereIn('role', ['mentor', 'etudiant'])->delete();

            $nomsMentors = [
                ['prenom' => 'Mariama', 'nom' => 'Gueye'],
                ['prenom' => 'Mamadou', 'nom' => 'Ndiaye'],
                ['prenom' => 'Fatou', 'nom' => 'Diop'],
            ];
            $nomsEtudiants = [
                ['prenom' => 'Abdoulaye', 'nom' => 'Dieng'],
                ['prenom' => 'Seynabou', 'nom' => 'Kane'],
                ['prenom' => 'Modou', 'nom' => 'Diagne'],
                ['prenom' => 'Astou', 'nom' => 'Camara'],
                ['prenom' => 'Babacar', 'nom' => 'Cissé'],
                ['prenom' => 'Mortalla', 'nom' => 'Gueye'],
            ];

            $motDePasse = Hash::make('password123');
            $etudiantsIds = [];
            foreach ($nomsEtudiants as $etudiant) {
                $id = Str::uuid();
                $etudiantsIds[] = $id;
                $email = strtolower($etudiant['prenom'] . '.' . $etudiant['nom']) . '@devbridge.sn';
                DB::table('utilisateurs')->insert([
                    'id' => $id,
                    'prenom' => $etudiant['prenom'],
                    'nom' => $etudiant['nom'],
                    'email' => $email,
                    'role' => 'etudiant',
                    'niveau' => 'debutant',
                    'pays' => 'Sénégal',
                    'est_actif' => true,
                    'mot_de_passe' => $motDePasse,
                    'created_at' => Carbon::now()->subDays(rand(5, 60)),
                    'updated_at' => Carbon::now(),
                ]);
            }
            foreach ($nomsMentors as $i => $user) {
                $mentor_id = Str::uuid();
                $email = strtolower($user['prenom'] . '.' . $user['nom']) . '@devbridge.sn';
                DB::table('utilisateurs')->insert([
                    'id' => $mentor_id,
                    'prenom' => $user['prenom'],
                    'nom' => $user['nom'],
                    'email' => $email,
                    'role' => 'mentor',
                    'niveau' => 'avance',
                    'pays' => 'Sénégal',
                    'est_actif' => true,
                    'mot_de_passe' => $motDePasse,
                    'created_at' => Carbon::now()->subDays(rand(10, 100)),
                    'updated_at' => Carbon::now(),
                ]);
                // Associer 2 étudiants à chaque mentor
                for ($j = 0; $j < 2; $j++) {
                    $etudiant_id = $etudiantsIds[($i + $j) % count($etudiantsIds)];
                    $mentorat_id = Str::uuid();
                    DB::table('mentorats')->insert([
                        'id' => $mentorat_id,
                        'mentor_id' => $mentor_id,
                        'etudiant_id' => $etudiant_id,
                        'statut' => 'accepte',
                        'demande_a' => Carbon::now()->subDays(rand(5, 30)),
                        'accepte_a' => Carbon::now()->subDays(rand(1, 5)),
                        'created_at' => Carbon::now()->subDays(rand(1, 10)),
                        'updated_at' => Carbon::now(),
                    ]);
                    // Associer explicitement les compétences frontend et backend
                    foreach ([$competencesFrontendId, $competencesBackendId] as $competenceId) {
                        $exists = DB::table('competences_utilisateurs')
                            ->where('utilisateur_id', $etudiant_id)
                            ->where('competence_id', $competenceId)
                            ->exists();
                        if (!$exists) {
                            DB::table('competences_utilisateurs')->insert([
                                'id' => Str::uuid(),
                                'utilisateur_id' => $etudiant_id,
                                'competence_id' => $competenceId,
                                'niveau_maitrise' => rand(2, 5),
                                'valide_par' => $mentor_id,
                                'valide_a' => Carbon::now()->subDays(rand(1, 30)),
                                'methode_validation' => 'mentor',
                                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                                'updated_at' => Carbon::now(),
                            ]);
                        }
                    }
                    // Sessions de mentorat fictives
                    for ($s = 0; $s < rand(1, 2); $s++) {
                        $session_id = Str::uuid();
                        $date_debut = Carbon::now()->subDays(rand(1, 20))->setTime(rand(8, 16), rand(0, 59));
                        $date_fin = (clone $date_debut)->addMinutes(rand(30, 90));
                        DB::table('sessions_mentorat')->insert([
                            'id' => $session_id,
                            'mentorat_id' => $mentorat_id,
                            'titre' => 'Session mentorat',
                            'description' => 'Session de suivi et d’accompagnement',
                            'date_debut' => $date_debut,
                            'date_fin' => $date_fin,
                            'statut' => 'termine',
                            'created_at' => $date_debut,
                            'updated_at' => $date_fin,
                        ]);
                    }
                }
            }
        }
    }
       
