<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';
    protected $fillable = ['nombre', 'descripcion'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos');
    }

    public function hasPermiso(string $nombreTecnico): bool
    {
        return $this->permisos()->where('nombre_tecnico', $nombreTecnico)->exists();
    }
}
