<?php

namespace Database\Factories;

use App\Models\Administrateur;
use Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Administrateur>
 */
class AdministrateurFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
           'nom' => 'Test',
            'prenom' => 'User',
            'telephone' => '0700000000',
            'email' => 'test@example.com',
            'password' => Hash::make('password'), // Mot de passe par défaut pour les tests
        ];
    }
}
