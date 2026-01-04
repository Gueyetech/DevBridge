<?php

namespace Database\Seeders;

use App\Models\ParcoursApprentissage;
use App\Models\Module;
use App\Models\Lecon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ParcoursSeeder extends Seeder
{
    // Mapping des types vers les valeurs valides de l'enum
    private function mapTypeContenu(string $type): string
    {
        return match($type) {
            'texte', 'article' => 'article',
            'video' => 'video',
            'exercice' => 'exercice',
            'code', 'projet' => 'projet',
            default => 'article',
        };
    }

    public function run(): void
    {
        // Supprimer les parcours existants pour éviter les doublons
        ParcoursApprentissage::query()->delete();
        
        $parcours = [
            [
                'titre' => 'Introduction à JavaScript',
                'description' => 'Apprenez les bases de JavaScript, le langage de programmation le plus populaire du web. Ce parcours vous guidera des fondamentaux jusqu\'à la création de vos premières applications interactives.',
                'technologie' => 'JavaScript',
                'difficulte' => 'debutant',
                'duree_estimee_heures' => 20,
                'prerequis' => ['Connaissances de base en HTML/CSS', 'Un éditeur de code installé'],
                'competences_acquises' => ['Variables et types de données', 'Fonctions et portée', 'Manipulation du DOM', 'Gestion des événements'],
                'modules' => [
                    [
                        'titre' => 'Les fondamentaux',
                        'description' => 'Découvrez les bases de JavaScript',
                        'lecons' => [
                            ['titre' => 'Introduction à JavaScript', 'type' => 'video', 'duree' => 15, 'contenu' => 'JavaScript est un langage de programmation dynamique qui permet de créer du contenu interactif sur les sites web.'],
                            ['titre' => 'Variables et constantes', 'type' => 'texte', 'duree' => 20, 'contenu' => 'Apprenez à déclarer des variables avec let, const et var.'],
                            ['titre' => 'Types de données', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Découvrez les types primitifs: string, number, boolean, null, undefined.'],
                            ['titre' => 'Exercice pratique', 'type' => 'exercice', 'duree' => 30, 'contenu' => 'Créez un programme qui calcule la moyenne de trois notes.'],
                        ]
                    ],
                    [
                        'titre' => 'Structures de contrôle',
                        'description' => 'Maîtrisez les conditions et les boucles',
                        'lecons' => [
                            ['titre' => 'Les conditions if/else', 'type' => 'texte', 'duree' => 20, 'contenu' => 'Apprenez à créer des conditions pour contrôler le flux de votre programme.'],
                            ['titre' => 'Switch case', 'type' => 'texte', 'duree' => 15, 'contenu' => 'Alternative aux conditions multiples avec switch.'],
                            ['titre' => 'Boucles for et while', 'type' => 'video', 'duree' => 25, 'contenu' => 'Répétez des actions avec les boucles.'],
                            ['titre' => 'Exercice: FizzBuzz', 'type' => 'exercice', 'duree' => 30, 'contenu' => 'Implémentez le célèbre exercice FizzBuzz.'],
                        ]
                    ],
                    [
                        'titre' => 'Fonctions',
                        'description' => 'Créez des fonctions réutilisables',
                        'lecons' => [
                            ['titre' => 'Déclaration de fonctions', 'type' => 'texte', 'duree' => 20, 'contenu' => 'Créez vos premières fonctions en JavaScript.'],
                            ['titre' => 'Paramètres et retours', 'type' => 'texte', 'duree' => 20, 'contenu' => 'Passez des arguments et retournez des valeurs.'],
                            ['titre' => 'Fonctions fléchées', 'type' => 'video', 'duree' => 15, 'contenu' => 'Syntaxe moderne avec les arrow functions.'],
                            ['titre' => 'Projet: Calculatrice', 'type' => 'code', 'duree' => 45, 'contenu' => 'Créez une calculatrice simple en JavaScript.'],
                        ]
                    ],
                ]
            ],
            [
                'titre' => 'React.js de A à Z',
                'description' => 'Maîtrisez React, la bibliothèque JavaScript la plus demandée. De la création de composants aux hooks avancés, devenez un développeur React compétent.',
                'technologie' => 'React',
                'difficulte' => 'intermediaire',
                'duree_estimee_heures' => 35,
                'prerequis' => ['Bases solides en JavaScript', 'ES6+ (destructuring, spread, arrow functions)', 'Notions de npm/yarn'],
                'competences_acquises' => ['Composants fonctionnels', 'Hooks (useState, useEffect, useContext)', 'Gestion d\'état', 'Routing avec React Router'],
                'modules' => [
                    [
                        'titre' => 'Introduction à React',
                        'description' => 'Comprendre les concepts fondamentaux',
                        'lecons' => [
                            ['titre' => 'Qu\'est-ce que React ?', 'type' => 'video', 'duree' => 20, 'contenu' => 'React est une bibliothèque JavaScript pour construire des interfaces utilisateur.'],
                            ['titre' => 'Créer une app React', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Utilisez Vite ou Create React App pour démarrer.'],
                            ['titre' => 'JSX en détail', 'type' => 'texte', 'duree' => 30, 'contenu' => 'JSX permet d\'écrire du HTML dans JavaScript.'],
                            ['titre' => 'Premier composant', 'type' => 'exercice', 'duree' => 30, 'contenu' => 'Créez votre premier composant React.'],
                        ]
                    ],
                    [
                        'titre' => 'Les Hooks essentiels',
                        'description' => 'Maîtrisez useState et useEffect',
                        'lecons' => [
                            ['titre' => 'useState en profondeur', 'type' => 'video', 'duree' => 25, 'contenu' => 'Gérez l\'état local de vos composants.'],
                            ['titre' => 'useEffect et le cycle de vie', 'type' => 'texte', 'duree' => 30, 'contenu' => 'Effectuez des effets de bord dans vos composants.'],
                            ['titre' => 'useContext pour l\'état global', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Partagez des données entre composants.'],
                            ['titre' => 'Exercice: Todo App', 'type' => 'code', 'duree' => 60, 'contenu' => 'Créez une application de gestion de tâches.'],
                        ]
                    ],
                    [
                        'titre' => 'Hooks avancés',
                        'description' => 'Optimisez vos composants',
                        'lecons' => [
                            ['titre' => 'useMemo et useCallback', 'type' => 'video', 'duree' => 30, 'contenu' => 'Optimisez les performances de vos composants.'],
                            ['titre' => 'useReducer', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Gérez des états complexes.'],
                            ['titre' => 'Custom Hooks', 'type' => 'texte', 'duree' => 30, 'contenu' => 'Créez vos propres hooks réutilisables.'],
                            ['titre' => 'Projet: Dashboard', 'type' => 'code', 'duree' => 90, 'contenu' => 'Construisez un dashboard interactif.'],
                        ]
                    ],
                ]
            ],
            [
                'titre' => 'Laravel pour les débutants',
                'description' => 'Découvrez Laravel, le framework PHP le plus élégant. Créez des applications web robustes avec une architecture MVC moderne.',
                'technologie' => 'Laravel',
                'difficulte' => 'debutant',
                'duree_estimee_heures' => 30,
                'prerequis' => ['Bases en PHP', 'Notions de SQL', 'Composer installé'],
                'competences_acquises' => ['Architecture MVC', 'Eloquent ORM', 'Routing et Controllers', 'Blade templating'],
                'modules' => [
                    [
                        'titre' => 'Premiers pas avec Laravel',
                        'description' => 'Installation et configuration',
                        'lecons' => [
                            ['titre' => 'Installation de Laravel', 'type' => 'video', 'duree' => 20, 'contenu' => 'Installez Laravel via Composer.'],
                            ['titre' => 'Structure du projet', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Comprenez l\'organisation des dossiers.'],
                            ['titre' => 'Configuration de base', 'type' => 'texte', 'duree' => 20, 'contenu' => 'Configurez votre environnement .env.'],
                            ['titre' => 'Artisan CLI', 'type' => 'exercice', 'duree' => 20, 'contenu' => 'Maîtrisez les commandes Artisan.'],
                        ]
                    ],
                    [
                        'titre' => 'Routes et Controllers',
                        'description' => 'Gérez les requêtes HTTP',
                        'lecons' => [
                            ['titre' => 'Le système de routing', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Définissez les routes de votre application.'],
                            ['titre' => 'Créer des controllers', 'type' => 'video', 'duree' => 30, 'contenu' => 'Organisez votre logique métier.'],
                            ['titre' => 'Resource Controllers', 'type' => 'texte', 'duree' => 25, 'contenu' => 'CRUD simplifié avec les resource controllers.'],
                            ['titre' => 'Exercice: API REST', 'type' => 'code', 'duree' => 45, 'contenu' => 'Créez une API REST basique.'],
                        ]
                    ],
                    [
                        'titre' => 'Eloquent ORM',
                        'description' => 'Manipulez votre base de données',
                        'lecons' => [
                            ['titre' => 'Migrations', 'type' => 'texte', 'duree' => 20, 'contenu' => 'Versionnez votre schéma de base de données.'],
                            ['titre' => 'Modèles Eloquent', 'type' => 'video', 'duree' => 30, 'contenu' => 'Créez et utilisez des modèles.'],
                            ['titre' => 'Relations', 'type' => 'texte', 'duree' => 35, 'contenu' => 'HasMany, BelongsTo, ManyToMany...'],
                            ['titre' => 'Projet: Blog', 'type' => 'code', 'duree' => 60, 'contenu' => 'Créez un blog complet avec Laravel.'],
                        ]
                    ],
                ]
            ],
            [
                'titre' => 'Python pour la Data Science',
                'description' => 'Plongez dans la data science avec Python. Apprenez à manipuler, analyser et visualiser des données avec les bibliothèques les plus populaires.',
                'technologie' => 'Python',
                'difficulte' => 'intermediaire',
                'duree_estimee_heures' => 40,
                'prerequis' => ['Bases en Python', 'Notions de mathématiques', 'Jupyter Notebook installé'],
                'competences_acquises' => ['NumPy et Pandas', 'Visualisation avec Matplotlib', 'Analyse statistique', 'Introduction au Machine Learning'],
                'modules' => [
                    [
                        'titre' => 'NumPy fondamentaux',
                        'description' => 'Calculs numériques avec NumPy',
                        'lecons' => [
                            ['titre' => 'Introduction à NumPy', 'type' => 'video', 'duree' => 20, 'contenu' => 'NumPy est la base du calcul scientifique en Python.'],
                            ['titre' => 'Arrays et opérations', 'type' => 'texte', 'duree' => 30, 'contenu' => 'Créez et manipulez des tableaux multidimensionnels.'],
                            ['titre' => 'Indexing et slicing', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Accédez aux éléments de vos arrays.'],
                            ['titre' => 'Exercice: Calculs matriciels', 'type' => 'exercice', 'duree' => 30, 'contenu' => 'Réalisez des opérations sur des matrices.'],
                        ]
                    ],
                    [
                        'titre' => 'Pandas pour l\'analyse',
                        'description' => 'Manipulez des données tabulaires',
                        'lecons' => [
                            ['titre' => 'DataFrames et Series', 'type' => 'video', 'duree' => 25, 'contenu' => 'Les structures de données de Pandas.'],
                            ['titre' => 'Lecture de fichiers', 'type' => 'texte', 'duree' => 20, 'contenu' => 'Importez des CSV, Excel, JSON...'],
                            ['titre' => 'Nettoyage de données', 'type' => 'texte', 'duree' => 35, 'contenu' => 'Gérez les valeurs manquantes et les doublons.'],
                            ['titre' => 'Exercice: Analyse de dataset', 'type' => 'code', 'duree' => 45, 'contenu' => 'Analysez un jeu de données réel.'],
                        ]
                    ],
                    [
                        'titre' => 'Visualisation',
                        'description' => 'Créez des graphiques parlants',
                        'lecons' => [
                            ['titre' => 'Matplotlib basics', 'type' => 'video', 'duree' => 25, 'contenu' => 'Créez vos premiers graphiques.'],
                            ['titre' => 'Seaborn pour le style', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Des visualisations statistiques élégantes.'],
                            ['titre' => 'Graphiques interactifs', 'type' => 'texte', 'duree' => 30, 'contenu' => 'Plotly pour l\'interactivité.'],
                            ['titre' => 'Projet: Dashboard Data', 'type' => 'code', 'duree' => 60, 'contenu' => 'Créez un dashboard d\'analyse de données.'],
                        ]
                    ],
                ]
            ],
            [
                'titre' => 'Node.js et Express',
                'description' => 'Construisez des backends performants avec Node.js et Express. API REST, authentification, et bonnes pratiques du développement serveur.',
                'technologie' => 'Node.js',
                'difficulte' => 'intermediaire',
                'duree_estimee_heures' => 25,
                'prerequis' => ['JavaScript ES6+', 'Bases en HTTP', 'npm/yarn'],
                'competences_acquises' => ['Serveur Express', 'API REST', 'Middleware', 'Authentification JWT'],
                'modules' => [
                    [
                        'titre' => 'Node.js fondamentaux',
                        'description' => 'Comprendre Node.js',
                        'lecons' => [
                            ['titre' => 'Qu\'est-ce que Node.js ?', 'type' => 'video', 'duree' => 15, 'contenu' => 'Node.js permet d\'exécuter JavaScript côté serveur.'],
                            ['titre' => 'Modules et npm', 'type' => 'texte', 'duree' => 20, 'contenu' => 'Gérez vos dépendances avec npm.'],
                            ['titre' => 'File System', 'type' => 'texte', 'duree' => 20, 'contenu' => 'Lisez et écrivez des fichiers.'],
                            ['titre' => 'Exercice: CLI Tool', 'type' => 'exercice', 'duree' => 30, 'contenu' => 'Créez un outil en ligne de commande.'],
                        ]
                    ],
                    [
                        'titre' => 'Express.js',
                        'description' => 'Le framework web de référence',
                        'lecons' => [
                            ['titre' => 'Premier serveur Express', 'type' => 'video', 'duree' => 20, 'contenu' => 'Créez votre premier serveur web.'],
                            ['titre' => 'Routing avancé', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Organisez vos routes efficacement.'],
                            ['titre' => 'Middleware', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Interceptez et traitez les requêtes.'],
                            ['titre' => 'Exercice: API CRUD', 'type' => 'code', 'duree' => 45, 'contenu' => 'Construisez une API REST complète.'],
                        ]
                    ],
                ]
            ],
            [
                'titre' => 'TypeScript Masterclass',
                'description' => 'Ajoutez le typage statique à JavaScript. Écrivez du code plus robuste et maintenable avec TypeScript.',
                'technologie' => 'TypeScript',
                'difficulte' => 'avance',
                'duree_estimee_heures' => 30,
                'prerequis' => ['JavaScript avancé', 'ES6+ maîtrisé', 'Expérience avec npm'],
                'competences_acquises' => ['Types et interfaces', 'Generics', 'Types utilitaires', 'Configuration TSConfig'],
                'modules' => [
                    [
                        'titre' => 'Introduction au typage',
                        'description' => 'Les bases de TypeScript',
                        'lecons' => [
                            ['titre' => 'Pourquoi TypeScript ?', 'type' => 'video', 'duree' => 15, 'contenu' => 'Les avantages du typage statique.'],
                            ['titre' => 'Types primitifs', 'type' => 'texte', 'duree' => 20, 'contenu' => 'string, number, boolean, any, unknown...'],
                            ['titre' => 'Interfaces et Types', 'type' => 'texte', 'duree' => 30, 'contenu' => 'Définissez la structure de vos objets.'],
                            ['titre' => 'Exercice: Typer une app', 'type' => 'exercice', 'duree' => 30, 'contenu' => 'Convertissez du JS en TS.'],
                        ]
                    ],
                    [
                        'titre' => 'Types avancés',
                        'description' => 'Maîtrisez les types complexes',
                        'lecons' => [
                            ['titre' => 'Generics', 'type' => 'video', 'duree' => 30, 'contenu' => 'Créez des composants réutilisables et typés.'],
                            ['titre' => 'Types utilitaires', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Partial, Required, Pick, Omit...'],
                            ['titre' => 'Types conditionnels', 'type' => 'texte', 'duree' => 30, 'contenu' => 'Types dynamiques selon les conditions.'],
                            ['titre' => 'Projet: Library typée', 'type' => 'code', 'duree' => 60, 'contenu' => 'Créez une bibliothèque TypeScript.'],
                        ]
                    ],
                ]
            ],
            [
                'titre' => 'Docker pour développeurs',
                'description' => 'Containerisez vos applications avec Docker. De l\'installation à l\'orchestration, maîtrisez les conteneurs.',
                'technologie' => 'Docker',
                'difficulte' => 'avance',
                'duree_estimee_heures' => 20,
                'prerequis' => ['Linux basics', 'Ligne de commande', 'Une application à containeriser'],
                'competences_acquises' => ['Dockerfile', 'Docker Compose', 'Volumes et réseaux', 'Multi-stage builds'],
                'modules' => [
                    [
                        'titre' => 'Docker basics',
                        'description' => 'Comprendre les conteneurs',
                        'lecons' => [
                            ['titre' => 'Qu\'est-ce que Docker ?', 'type' => 'video', 'duree' => 15, 'contenu' => 'Les conteneurs vs les machines virtuelles.'],
                            ['titre' => 'Installation', 'type' => 'texte', 'duree' => 15, 'contenu' => 'Installez Docker sur votre système.'],
                            ['titre' => 'Images et conteneurs', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Comprenez la différence entre images et conteneurs.'],
                            ['titre' => 'Exercice: Premier conteneur', 'type' => 'exercice', 'duree' => 20, 'contenu' => 'Lancez votre premier conteneur.'],
                        ]
                    ],
                    [
                        'titre' => 'Docker Compose',
                        'description' => 'Multi-conteneurs simplifiés',
                        'lecons' => [
                            ['titre' => 'Introduction à Compose', 'type' => 'video', 'duree' => 20, 'contenu' => 'Gérez plusieurs conteneurs facilement.'],
                            ['titre' => 'docker-compose.yml', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Définissez vos services.'],
                            ['titre' => 'Réseaux et volumes', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Communication entre conteneurs et persistance.'],
                            ['titre' => 'Projet: Stack LAMP', 'type' => 'code', 'duree' => 45, 'contenu' => 'Déployez une stack complète.'],
                        ]
                    ],
                ]
            ],
            [
                'titre' => 'Git & GitHub Pro',
                'description' => 'Maîtrisez le contrôle de version avec Git. Workflows collaboratifs, branches, et bonnes pratiques.',
                'technologie' => 'Git',
                'difficulte' => 'debutant',
                'duree_estimee_heures' => 15,
                'prerequis' => ['Ligne de commande basique', 'Un compte GitHub'],
                'competences_acquises' => ['Commits et branches', 'Merge et rebase', 'Pull requests', 'Git Flow'],
                'modules' => [
                    [
                        'titre' => 'Git fondamentaux',
                        'description' => 'Les bases du versioning',
                        'lecons' => [
                            ['titre' => 'Pourquoi Git ?', 'type' => 'video', 'duree' => 10, 'contenu' => 'L\'importance du contrôle de version.'],
                            ['titre' => 'Configuration initiale', 'type' => 'texte', 'duree' => 15, 'contenu' => 'Configurez Git sur votre machine.'],
                            ['titre' => 'Commits et historique', 'type' => 'texte', 'duree' => 20, 'contenu' => 'Créez et gérez vos commits.'],
                            ['titre' => 'Exercice: Premier repo', 'type' => 'exercice', 'duree' => 20, 'contenu' => 'Initialisez votre premier repository.'],
                        ]
                    ],
                    [
                        'titre' => 'Branches et collaboration',
                        'description' => 'Travaillez en équipe',
                        'lecons' => [
                            ['titre' => 'Branches', 'type' => 'video', 'duree' => 20, 'contenu' => 'Travaillez sur des fonctionnalités en parallèle.'],
                            ['titre' => 'Merge vs Rebase', 'type' => 'texte', 'duree' => 25, 'contenu' => 'Deux stratégies pour intégrer du code.'],
                            ['titre' => 'Pull Requests', 'type' => 'texte', 'duree' => 20, 'contenu' => 'Collaborez via GitHub.'],
                            ['titre' => 'Exercice: Workflow équipe', 'type' => 'code', 'duree' => 30, 'contenu' => 'Simulez un workflow d\'équipe.'],
                        ]
                    ],
                ]
            ],
        ];

        foreach ($parcours as $parcoursData) {
            $modules = $parcoursData['modules'];
            unset($parcoursData['modules']);

            $parcoursData['slug'] = Str::slug($parcoursData['titre']);
            $parcoursData['est_publie'] = true;
            $parcoursData['ordre'] = 0;

            $parcours_model = ParcoursApprentissage::create($parcoursData);

            foreach ($modules as $index => $moduleData) {
                $lecons = $moduleData['lecons'];
                unset($moduleData['lecons']);

                $module = $parcours_model->modules()->create([
                    'titre' => $moduleData['titre'],
                    'slug' => Str::slug($moduleData['titre']),
                    'description' => $moduleData['description'],
                    'ordre' => $index + 1,
                    'duree_estimee_minutes' => collect($lecons)->sum('duree'),
                ]);

                foreach ($lecons as $leconIndex => $leconData) {
                    $module->lecons()->create([
                        'titre' => $leconData['titre'],
                        'slug' => Str::slug($leconData['titre']),
                        'type_contenu' => $this->mapTypeContenu($leconData['type']),
                        'duree_estimee_minutes' => $leconData['duree'],
                        'contenu' => $leconData['contenu'],
                        'ordre' => $leconIndex + 1,
                        'est_gratuit' => $leconIndex === 0, // Première leçon gratuite
                    ]);
                }
            }
        }

        $this->command->info('✅ ' . count($parcours) . ' parcours créés avec modules et leçons !');
    }
}
