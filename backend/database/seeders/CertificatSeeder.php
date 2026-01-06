<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Certificat;
use App\Models\Utilisateur;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CertificatSeeder extends Seeder
{
    public function run(): void
    {
        $utilisateurs = Utilisateur::inRandomOrder()->take(5)->get();
        foreach ($utilisateurs as $user) {
            Certificat::create([
                'id' => Str::uuid(),
                'utilisateur_id' => $user->id,
                'type' => 'competence',
                'code_verification' => strtoupper(Str::random(10)),
                'date_emission' => Carbon::now()->subDays(rand(1, 100)),
                'date_expiration' => null,
                'nombre_telechargements' => rand(0, 10),
                'created_at' => Carbon::now()->subDays(rand(1, 100)),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
