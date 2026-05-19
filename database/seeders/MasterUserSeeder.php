<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterUserSeeder extends Seeder
{
    public function run()
    {
        // Obtener o crear rol Administrador
        $rolAdmin = Rol::firstOrCreate(
            ['nombre' => 'administrador'],
            ['descripcion' => 'Rol con todos los permisos']
        );

        // Sincronizar TODOS los permisos existentes con este rol
        $todosPermisos = Permiso::all();
        $rolAdmin->permisos()->sync($todosPermisos->pluck('id'));

        // Crear usuario master
        $masterPassword = config('app.master_password');
        User::updateOrCreate(
            ['email' => 'master@system.com'],
            [
                'name' => 'Master User',
                'password' => Hash::make($masterPassword),
                'rol_id' => $rolAdmin->id,
            ]
        );
    }
}