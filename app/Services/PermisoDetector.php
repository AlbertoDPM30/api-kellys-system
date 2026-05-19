<?php

namespace App\Services;

use App\Models\Permiso;
use Illuminate\Support\Facades\DB;

class PermisoDetector
{
    /**
     * Retorna todos los permisos definidos en código (config/permisos.php)
     * en formato ['nombre_tecnico' => 'modulo:accion', 'modulo' => ..., 'accion' => ...]
     */
    public static function getPermisosFromCode(): array
    {
        $config = config('permisos.modulos', []);
        $permisos = [];
        foreach ($config as $modulo => $data) {
            $acciones = $data['acciones'] ?? [];
            foreach ($acciones as $accion) {
                $permisos[] = [
                    'nombre_tecnico' => $modulo . ':' . $accion,
                    'modulo' => $modulo,
                    'accion' => $accion,
                    'descripcion' => $data['descripcion'] ?? ucfirst($modulo) . ' - ' . $accion,
                ];
            }
        }
        return $permisos;
    }

    /**
     * Sincroniza los permisos de código con la BD:
     * - Inserta los nuevos permisos que no existen.
     * - (Opcional) No elimina los antiguos, porque podrían estar asignados a roles.
     * Retorna los permisos recién insertados.
     */
    public static function syncPermisos(): array
    {
        $codigoPermisos = self::getPermisosFromCode();
        $existentes = Permiso::pluck('nombre_tecnico')->toArray();

        $nuevos = [];
        foreach ($codigoPermisos as $p) {
            if (!in_array($p['nombre_tecnico'], $existentes)) {
                $permiso = Permiso::create($p);
                $nuevos[] = $permiso;
            }
        }
        return $nuevos;
    }

    /**
     * Obtiene los permisos que están en código pero aún no en BD (detectados)
     */
    public static function getNewPermisosDetected(): array
    {
        $codigoPermisos = self::getPermisosFromCode();
        $existentes = Permiso::pluck('nombre_tecnico')->toArray();
        return array_filter($codigoPermisos, function ($p) use ($existentes) {
            return !in_array($p['nombre_tecnico'], $existentes);
        });
    }
}