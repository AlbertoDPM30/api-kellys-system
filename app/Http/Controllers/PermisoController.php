<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Rol;
use App\Services\PermisoDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermisoController extends Controller
{
    public function index()
    {
        $permisos = Permiso::orderBy('modulo')->orderBy('accion')->get();

        return response()->json([
            'message' => 'Listado de permisos.',
            'data' => $permisos,
        ]);
    }

    public function nuevosDetectados()
    {
        $nuevos = PermisoDetector::getNewPermisosDetected();

        return response()->json([
            'message' => count($nuevos) > 0
                ? 'Se detectaron ' . count($nuevos) . ' permisos nuevos en el código.'
                : 'No hay permisos nuevos pendientes de sincronizar.',
            'data' => array_values($nuevos),
        ]);
    }

    public function sincronizar()
    {
        $nuevos = PermisoDetector::syncPermisos();

        $cantidad = count($nuevos);

        return response()->json([
            'message' => $cantidad > 0
                ? "{$cantidad} permiso(s) sincronizado(s) exitosamente."
                : 'No hay permisos nuevos para sincronizar.',
            'data' => $nuevos,
        ]);
    }

    public function asignacionMasiva(Request $request)
    {
        $request->validate([
            'accion' => 'required|in:asignar_a_rol,clonar_desde_modulo,asignar_por_reglas',
        ]);

        $nuevosPermisos = PermisoDetector::getNewPermisosDetected();

        if (empty($nuevosPermisos)) {
            return response()->json([
                'message' => 'No hay nuevos permisos pendientes para asignar.',
            ]);
        }

        $accion = $request->accion;

        $validations = [
            'asignar_a_rol' => [
                'rol_id' => 'required|integer|exists:roles,id',
            ],
            'clonar_desde_modulo' => [
                'modulo_origen' => 'required|string',
                'modulo_destino' => 'required|string',
            ],
            'asignar_por_reglas' => [
                'reglas' => 'required|array|min:1',
                'reglas.*.rol_id' => 'required|integer|exists:roles,id',
                'reglas.*.acciones' => 'required|array|min:1',
            ],
        ];

        if (isset($validations[$accion])) {
            $request->validate($validations[$accion]);
        }

        $insertados = PermisoDetector::syncPermisos();

        DB::beginTransaction();
        try {
            switch ($accion) {
                case 'asignar_a_rol':
                    $rol = Rol::findOrFail($request->rol_id);
                    $permisosIds = collect($insertados)->pluck('id');
                    $rol->permisos()->syncWithoutDetaching($permisosIds);
                    break;

                case 'clonar_desde_modulo':
                    $moduloOrigen = $request->modulo_origen;
                    $moduloDestino = $request->modulo_destino;

                    $permisosOrigen = Permiso::where('modulo', $moduloOrigen)->pluck('id');
                    $rolesConPermisos = DB::table('roles_permisos')
                        ->whereIn('permiso_id', $permisosOrigen)
                        ->select('rol_id')->distinct()->pluck('rol_id');

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
                    foreach ($request->reglas as $regla) {
                        $rol = Rol::find($regla['rol_id']);
                        if (!$rol) continue;

                        $accionesPermitidas = $regla['acciones'];
                        $idsAsignar = collect($insertados)
                            ->filter(fn($p) => in_array($p->accion, $accionesPermitidas))
                            ->pluck('id');

                        $rol->permisos()->syncWithoutDetaching($idsAsignar);
                    }
                    break;
            }

            DB::commit();

            return response()->json([
                'message' => 'Asignación masiva completada exitosamente.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al realizar la asignación masiva.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
