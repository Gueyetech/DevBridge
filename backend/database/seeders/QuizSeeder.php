<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Support\Str;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $quizzes = [
            [
                'titre' => 'Quiz JavaScript',
                'questions' => [
                    [
                        'texte' => 'Quelle méthode permet d’ajouter un élément à la fin d’un tableau ?',
                        'type' => 'choix_multiple',
                        'options' => ['push', 'pop', 'shift', 'unshift'],
                        'reponses_correctes' => [0], // index de 'push'
                    ],
                    [
                        'texte' => 'Comment déclare-t-on une variable constante ?',
                        'type' => 'choix_multiple',
                        'options' => ['let', 'var', 'const', 'static'],
                        'reponses_correctes' => [2], // index de 'const'
                    ],
                ]
            ],
            [
                'titre' => 'Quiz Laravel',
                'questions' => [
                    [
                        'texte' => 'Quelle commande crée un contrôleur ?',
                        'type' => 'choix_multiple',
                        'options' => ['php artisan make:controller', 'php artisan new:controller', 'php make:controller'],
                        'reponses_correctes' => [0],
                    ],
                    [
                        'texte' => 'Quel fichier contient la config de la base de données ?',
                        'type' => 'choix_multiple',
                        'options' => ['.env', 'config/database.php', 'database.php', 'config.php'],
                        'reponses_correctes' => [1],
                    ],
                ]
            ],
        ];
        foreach ($quizzes as $quizData) {
            $quiz = Quiz::create([
                'id' => Str::uuid(),
                'titre' => $quizData['titre'],
            ]);
            $ordre = 0;
            foreach ($quizData['questions'] as $q) {
                Question::create([
                    'id' => Str::uuid(),
                    'quiz_id' => $quiz->id,
                    'texte' => $q['texte'],
                    'type' => $q['type'],
                    'options' => json_encode($q['options']),
                    'reponses_correctes' => json_encode($q['reponses_correctes']),
                    'ordre' => $ordre++,
                ]);
            }
        }
    }
}
