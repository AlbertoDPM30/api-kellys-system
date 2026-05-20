<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Las credenciales proporcionadas no son correctas.',
            ], 401);
        }

        if (!$user->rol_id) {
            return response()->json([
                'message' => 'El usuario no tiene un rol asignado. Contacte al administrador.',
            ], 403);
        }

        $expiresAt = now()->addHours(12);
        $token = $user->createTokenWithExpiration('auth_token', ['*'], $expiresAt);

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'data' => [
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt->toDateTimeString(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'rol_id' => $user->rol_id,
                ],
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user()->load('rol');

        return response()->json([
            'message' => 'Datos del usuario autenticado.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol_id' => $user->rol_id,
                'rol' => $user->rol ? [
                    'id' => $user->rol->id,
                    'nombre' => $user->rol->nombre,
                ] : null,
            ],
        ]);
    }
}
