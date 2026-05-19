<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        return response()->json(Rol::with('permisos')->get());
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|unique:roles']);
        $rol = Rol::create($request->only('nombre', 'descripcion'));
        return response()->json($rol, 201);
    }

    public function show(Rol $rol)
    {
        return response()->json($rol->load('permisos'));
    }

    public function update(Request $request, Rol $rol)
    {
        $rol->update($request->only('nombre', 'descripcion'));
        return response()->json($rol);
    }

    public function destroy(Rol $rol)
    {
        $rol->delete();
        return response()->json(null, 204);
    }

    public function asignarPermisos(Request $request, Rol $rol)
    {
        $request->validate(['permisos_ids' => 'required|array|exists:permisos,id']);
        $rol->permisos()->syncWithoutDetaching($request->permisos_ids);
        return response()->json($rol->load('permisos'));
    }

    public function removerPermiso(Rol $rol, Permiso $permiso)
    {
        $rol->permisos()->detach($permiso->id);
        return response()->json(null, 204);
    }
}