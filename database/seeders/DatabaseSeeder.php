<?php

namespace Database\Seeders;

use App\Models\Administrateur;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Factories\AdministrateurFactory;
use Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call(AdministrateurFactory::class);
        $this->call(RolesAndPermissionsSeeder::class);

        if (! Administrateur::query()->where('telephone', '0700000000')->exists()) {
            Administrateur::factory()->create([
                'id' => uuid_create(),
                'nom' => 'Admin',
                'prenom' => 'User',
                'telephone' => '0700000000',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'), // Mot de passe par défaut pour les tests
                'statut_compte' =>'ACTIF', // Statut du compte par défaut pour les tests
            ]);
        }
    }
}
