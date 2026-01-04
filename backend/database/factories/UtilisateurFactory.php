<?php

namespace Database\Factories;

use App\Models\Utilisateur;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Utilisateur>
 */
class UtilisateurFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Utilisateur::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prenom' => fake()->firstName(),
            'nom' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verifie_a' => now(),
            'mot_de_passe' => static::$password ??= Hash::make('password'),
            'role' => 'etudiant',
            'est_actif' => true,
            'points' => fake()->numberBetween(0, 500),
            'niveau' => fake()->numberBetween(1, 5),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verifie_a' => null,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function administrateur(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'administrateur',
        ]);
    }

    /**
     * Create a mentor user.
     */
    public function mentor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'mentor',
        ]);
    }

    /**
     * Create a student user.
     */
    public function etudiant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'etudiant',
        ]);
    }
}
