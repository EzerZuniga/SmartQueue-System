<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario Administrador
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'], // Buscamos por email
            [
                'name' => 'Administrador',
                'password' => Hash::make('12345678'), // Contraseña segura
                'status' => true, // Activo
                'email_verified_at' => now(), // Verificado automáticamente
                'image_path' => null, // Sin foto por defecto
            ]
        );

        $this->call(CallStatuseSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(RoleSeeder::class);
    }
}
