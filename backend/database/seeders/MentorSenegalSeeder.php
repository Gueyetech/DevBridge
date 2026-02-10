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
                // Utiliser l'ID fourni pour le mentor de test
                $mentor_id = ($i === 0) ? 'fb77de64-a426-4028-a60b-13d407d809eb' : Str::uuid();
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
                    // Sessions de mentorat fictives pour tous les statuts autorisés (planifie, en_cours, annule, termine)
                    $date_debut = Carbon::now()->subDays(2)->setTime(10, 0);
                    $date_fin = (clone $date_debut)->addMinutes(60);
                    // 1. Session planifiée
                    DB::table('sessions_mentorat')->insert([
                        'id' => Str::uuid(),
                        'mentorat_id' => $mentorat_id,
                        'titre' => 'Session planifiée',
                        'description' => 'Session à venir',
                        'date_debut' => Carbon::now()->addDays(2)->setTime(14, 0),
                        'date_fin' => Carbon::now()->addDays(2)->setTime(15, 0),
                        'statut' => 'planifie',
                        'created_at' => Carbon::now()->addDays(2)->setTime(14, 0),
                        'updated_at' => Carbon::now()->addDays(2)->setTime(14, 0),
                    ]);
                    // 2. Session en cours (date_fin requis, donc on met la même valeur que date_debut)
                    $date_en_cours = Carbon::now()->setTime(9, 0);
                    DB::table('sessions_mentorat')->insert([
                        'id' => Str::uuid(),
                        'mentorat_id' => $mentorat_id,
                        'titre' => 'Session en cours',
                        'description' => 'Session en cours',
                        'date_debut' => $date_en_cours,
                        'date_fin' => $date_en_cours, // valeur factice pour respecter la contrainte NOT NULL
                        'statut' => 'en_cours',
                        'created_at' => $date_en_cours,
                        'updated_at' => $date_en_cours,
                    ]);
                    // 3. Session annulée
                    DB::table('sessions_mentorat')->insert([
                        'id' => Str::uuid(),
                        'mentorat_id' => $mentorat_id,
                        'titre' => 'Session annulée',
                        'description' => 'Session annulée par le mentor',
                        'date_debut' => Carbon::now()->subDays(1)->setTime(11, 0),
                        'date_fin' => Carbon::now()->subDays(1)->setTime(12, 0),
                        'statut' => 'annule',
                        'created_at' => Carbon::now()->subDays(1)->setTime(11, 0),
                        'updated_at' => Carbon::now()->subDays(1)->setTime(11, 0),
                    ]);
                    // 4. Session terminée
                    DB::table('sessions_mentorat')->insert([
                        'id' => Str::uuid(),
                        'mentorat_id' => $mentorat_id,
                        'titre' => 'Session terminée',
                        'description' => 'Session de suivi et d’accompagnement',
                        'date_debut' => $date_debut,
                        'date_fin' => $date_fin,
                        'statut' => 'termine',
                        'created_at' => $date_debut,
                        'updated_at' => $date_fin,
                    ]);
                                       // Sessions supplémentaires pour chaque mentorat
                    // Session planifiée future
                    DB::table('sessions_mentorat')->insert([
                        'id' => Str::uuid(),
                        'mentorat_id' => $mentorat_id,
                        'titre' => 'Session planifiée avancée',
                        'description' => 'Session planifiée dans 1 semaine',
                        'date_debut' => Carbon::now()->addDays(7)->setTime(15, 0),
                        'date_fin' => Carbon::now()->addDays(7)->setTime(16, 0),
                        'statut' => 'planifie',
                        'created_at' => Carbon::now()->addDays(7)->setTime(15, 0),
                        'updated_at' => Carbon::now()->addDays(7)->setTime(15, 0),
                    ]);
                    // Session en cours supplémentaire
                    $date_en_cours2 = Carbon::now()->addDays(1)->setTime(10, 30);
                    DB::table('sessions_mentorat')->insert([
                        'id' => Str::uuid(),
                        'mentorat_id' => $mentorat_id,
                        'titre' => 'Session en cours bis',
                        'description' => 'Session en cours supplémentaire',
                        'date_debut' => $date_en_cours2,
                        'date_fin' => $date_en_cours2,
                        'statut' => 'en_cours',
                        'created_at' => $date_en_cours2,
                        'updated_at' => $date_en_cours2,
                    ]);
                    // Session annulée supplémentaire
                    DB::table('sessions_mentorat')->insert([
                        'id' => Str::uuid(),
                        'mentorat_id' => $mentorat_id,
                        'titre' => 'Session annulée bis',
                        'description' => 'Session annulée pour indisponibilité',
                        'date_debut' => Carbon::now()->subDays(3)->setTime(13, 0),
                        'date_fin' => Carbon::now()->subDays(3)->setTime(14, 0),
                        'statut' => 'annule',
                        'created_at' => Carbon::now()->subDays(3)->setTime(13, 0),
                        'updated_at' => Carbon::now()->subDays(3)->setTime(13, 0),
                    ]);
                    // Session terminée supplémentaire
                    $date_terminee2 = Carbon::now()->subDays(5)->setTime(17, 0);
                    $date_terminee2_fin = (clone $date_terminee2)->addMinutes(90);
                    DB::table('sessions_mentorat')->insert([
                        'id' => Str::uuid(),
                        'mentorat_id' => $mentorat_id,
                        'titre' => 'Session terminée bis',
                        'description' => 'Session terminée supplémentaire',
                        'date_debut' => $date_terminee2,
                        'date_fin' => $date_terminee2_fin,
                        'statut' => 'termine',
                        'created_at' => $date_terminee2,
                        'updated_at' => $date_terminee2_fin,
                    ]);
                }
            }
        }
    }
       
