<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permiso)
    {
        $user = $request->user();

        if (!$user || !$user->hasPermiso($permiso)) {
            return response()->json([
                'message' => 'No autorizado. El usuario no tiene el permiso requerido: ' . $permiso,
            ], 403);
        }

        return $next($request);
    }
}
