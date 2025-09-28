<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MascotaController;
use App\Http\Controllers\Auth\RegistrarUsuarioController;
use App\Http\Controllers\UserLocationController;
use App\Http\Controllers\VeterinarioController;
use App\Http\Controllers\GoogleAuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

//registros publicos y sin autentificación
Route::post('registrar-usuario', [RegistrarUsuarioController::class, 'register']);
Route::post('/registrar-veterinario', [VeterinarioController::class, 'store']);


// Rutas protegidas para administradores
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/solicitudes-pendientes', [VeterinarioController::class, 'obtenerSolicitudesPendientes']);
    Route::post('/solicitudes/{id}/aprobar', [VeterinarioController::class, 'aprobarSolicitud']);
    Route::post('/solicitudes/{id}/rechazar', [VeterinarioController::class, 'rechazarSolicitud']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/guardar-ubicacion', [UserLocationController::class, 'store']);

    // Rutas para la gestión de mascotas
    Route::post('/mascotas', [MascotaController::class, 'store']);
    Route::get('/mascotas', [MascotaController::class, 'index']);
    Route::get('/mascotas/{id}', [MascotaController::class, 'show']); 
    Route::put('/mascotas/{id}', [MascotaController::class, 'update']); // PUT directo
    Route::post('/mascotas/{id}', [MascotaController::class, 'update']);
    Route::get('/mascotas/motivos/baja', [MascotaController::class, 'obtenerMotivosBaja']);
    Route::post('/mascotas/{id}/baja', [MascotaController::class, 'darDeBaja']);
    


    // Ruta para verificar si el usuario está autenticado
    Route::get('/check-auth', function (Request $request) {
            Log::info('Check auth hit', [
            'user' => $request->user(),
            'token' => $request->bearerToken()
        ]);
        return $request->user();
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// routes/api.php
Route::get('/users/{id}', function ($id) {
    try {
        $user = User::with(['userable'])->findOrFail($id);
        
        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'nombre' => $user->nombre, // Asegúrate de que este campo existe
            'userable_type' => $user->userable_type,
            'userable' => $user->userable,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Usuario no encontrado'], 404);
    }
})->middleware('auth:sanctum');


Route::post('/registro-ubicacion', function (Request $request) {
    Log::info('📍 Registro de ubicación alcanzado', [
        'payload' => $request->all(),
        'ip' => $request->ip(),
    ]);
    return response()->json(['ok' => true]);
});
