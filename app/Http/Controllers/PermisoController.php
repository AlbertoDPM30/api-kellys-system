<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Rol;
use App\Services\PermisoDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermisoController extends Controller
{
    // Listar todos los permisos existentes en BD
    public function index()
    {
        return response()->json(Permiso::all());
    }

    // Nuevos permisos detectados (en código pero no en BD)
    public function nuevosDetectados()
    {
        $nuevos = PermisoDetector::getNewPermisosDetected();
        return response()->json($nuevos);
    }

    // Sincronizar: insertar los nuevos permisos en BD (sin asignar a roles)
    public function sincronizar()
    {
        $nuevos = PermisoDetector::syncPermisos();
        return response()->json([
            'message' => 'Permisos sincronizados',
            'nuevos' => $nuevos
        ]);
    }

    /**
     * Asignación masiva sobre los nuevos permisos detectados.
     * Body JSON:
     * {
     *   "accion": "asignar_a_rol", "rol_id": 1
     *   "accion": "clonar_desde_modulo", "modulo_origen": "usuarios", "modulo_destino": "incidencias"
     *   "accion": "asignar_por_reglas", "reglas": [{"rol_id":1, "acciones":["ver","crear"]}, ...]
     * }
     */
    public function asignacionMasiva(Request $request)
    {
        $request->validate([
            'accion' => 'required|in:asignar_a_rol,clonar_desde_modulo,asignar_por_reglas',
        ]);

        $nuevosPermisos = PermisoDetector::getNewPermisosDetected();
        if (empty($nuevosPermisos)) {
            return response()->json(['message' => 'No hay nuevos permisos para asignar'], 200);
        }

        // Primero sincronizamos para que existan en BD
        $insertados = PermisoDetector::syncPermisos(); // array de modelos Permiso

        $accion = $request->accion;

        DB::beginTransaction();
        try {
            switch ($accion) {
                case 'asignar_a_rol':
                    $rolId = $request->rol_id;
                    $rol = Rol::findOrFail($rolId);
                    $permisosIds = collect($insertados)->pluck('id');
                    $rol->permisos()->syncWithoutDetaching($permisosIds);
                    break;

                case 'clonar_desde_modulo':
                    $moduloOrigen = $request->modulo_origen;
                    $moduloDestino = $request->modulo_destino;
                    // Obtener los roles que tienen permisos del módulo origen
                    $permisosOrigen = Permiso::where('modulo', $moduloOrigen)->pluck('id');
                    $rolesConPermisos = DB::table('roles_permisos')
                        ->whereIn('permiso_id', $permisosOrigen)
                        ->select('rol_id')
                        ->distinct()
                        ->pluck('rol_id');
                    // Para cada rol, asignar los nuevos permisos del módulo destino
                    $permisosDestino = Permiso::where('modulo', $moduloDestino)
                        ->whereIn('id', collect($insertados)->pluck('id'))
                        ->pluck('id');
                    foreach ($rolesConPermisos as $rolId) {
                        $rol = Rol::find($rolId);
                        if ($rol) {
                            $rol->permisos()->syncWithoutDetaching($permisosDestino);
                        }
                    }
                    break;

                case 'asignar_por_reglas':
                    $reglas = $request->reglas; // array de {rol_id, acciones[]}
                    foreach ($reglas as $regla) {
                        $rol = Rol::find($regla['rol_id']);
                        if (!$rol) continue;
                        $accionesPermitidas = $regla['acciones'];
                        // Filtrar los nuevos permisos cuya acción esté en $accionesPermitidas
                        $idsAsignar = collect($insertados)
                            ->filter(fn($p) => in_array($p->accion, $accionesPermitidas))
                            ->pluck('id');
                        $rol->permisos()->syncWithoutDetaching($idsAsignar);
                    }
                    break;
            }
            DB::commit();
            return response()->json(['message' => 'Asignación masiva completada']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}