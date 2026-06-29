<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $persona = Persona::create([
            'nombre' => 'Erick',
            'apellidos' => 'Huallpa Vargas',
            'telefono' => '0',
            'direccion' => 'Sin dirección'
        ]);

        User::create([
            'persona_id' => $persona->id,
            'email' => 'erick@gmail.com',
            'password' => Hash::make('123123123'),
            'role' => 'admin',
            'loyalty_points' => 0,
            'delivery_route_day' => 1
        ]);
    }
}
