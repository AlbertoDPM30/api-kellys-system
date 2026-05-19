<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'permisos';
    protected $fillable = ['nombre_tecnico', 'modulo', 'accion', 'descripcion'];

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'roles_permisos');
    }
}
