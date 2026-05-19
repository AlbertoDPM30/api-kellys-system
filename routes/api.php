<?php

use App\Http\Controllers\PermisoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Roles
    Route::apiResource('roles', RolController::class);
    Route::post('roles/{rol}/permisos', [RolController::class, 'asignarPermisos']);
    Route::delete('roles/{rol}/permisos/{permiso}', [RolController::class, 'removerPermiso']);

    // Permisos
    Route::get('permisos', [PermisoController::class, 'index']);
    Route::get('permisos/nuevos', [PermisoController::class, 'nuevosDetectados']);
    Route::post('permisos/sincronizar', [PermisoController::class, 'sincronizar']);
    Route::post('permisos/asignacion-masiva', [PermisoController::class, 'asignacionMasiva']);
});