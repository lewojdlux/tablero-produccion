<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('/tablero-produccion/broadcasting/auth', function (Request $request) {
    $user = $request->user();

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    if (!in_array($user->perfil_usuario_id, [1, 2])) {
        return response()->json(['error' => 'Forbidden'], 403);
    }

    return [
        'auth' => base64_encode("{$user->id}:secret"),
    ];
})->middleware('auth:sanctum');
