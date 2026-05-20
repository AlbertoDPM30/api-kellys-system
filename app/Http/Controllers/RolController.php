<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::with('permisos')->get();

        return response()->json([
            'message' => 'Listado de roles.',
            'data' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:roles,nombre',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $rol = Rol::create($request->only('nombre', 'descripcion'));

        return response()->json([
            'message' => 'Rol creado exitosamente.',
            'data' => $rol,
        ], 201);
    }

    public function show(Rol $rol)
    {
        return response()->json([
            'message' => 'Detalle del rol.',
            'data' => $rol->load('permisos'),
        ]);
    }

    public function update(Request $request, Rol $rol)
    {
        $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:roles,nombre,' . $rol->id,
            'descripcion' => 'nullable|string|max:500',
        ]);

        $rol->update($request->only('nombre', 'descripcion'));

        return response()->json([
            'message' => 'Rol actualizado exitosamente.',
            'data' => $rol,
        ]);
    }

    public function destroy(Rol $rol)
    {
        if ($rol->users()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el rol porque tiene usuarios asignados.',
            ], 409);
        }

        $rol->permisos()->detach();
        $rol->delete();

        return response()->noContent();
    }

    public function asignarPermisos(Request $request, Rol $rol)
    {
        $request->validate([
            'permisos_ids' => 'required|array|min:1',
            'permisos_ids.*' => 'integer|exists:permisos,id',
        ]);

        $rol->permisos()->syncWithoutDetaching($request->permisos_ids);

        return response()->json([
            'message' => 'Permisos asignados al rol exitosamente.',
            'data' => $rol->load('permisos'),
        ]);
    }

    public function removerPermiso(Rol $rol, Permiso $permiso)
    {
        if (!$rol->permisos()->where('permiso_id', $permiso->id)->exists()) {
            return response()->json([
                'message' => 'El permiso no está asignado a este rol.',
            ], 404);
        }

        $rol->permisos()->detach($permiso->id);

        return response()->json([
            'message' => 'Permiso removido del rol exitosamente.',
            'data' => $rol->load('permisos'),
        ]);
    }
}
