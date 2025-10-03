<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MascotaController;
use App\Http\Controllers\Auth\RegistrarUsuarioController;
use App\Http\Controllers\UserLocationController;
use App\Http\Controllers\VeterinarioController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\CerrarSesionController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Registros públicos y sin autentificación
Route::post('registrar-usuario', [RegistrarUsuarioController::class, 'register']);
Route::post('/registrar-veterinario', [VeterinarioController::class, 'store']);

// Rutas de autenticación de Google
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
Route::post('/auth/google/complete-registration', [GoogleAuthController::class, 'completeRegistration']);

// Ruta temporal para debugging
Route::get('/user-debug', function (Request $request) {
    $user = $request->user();
    
    // Cargar todas las relaciones posibles
    $user->load(['userable']);
    
    return response()->json([
        'user' => $user,
        'userable' => $user->userable,
        'userable_id' => $user->userable_id,
        'userable_type' => $user->userable_type,
        'relations_loaded' => $user->getRelations(),
    ]);
})->middleware('auth:sanctum');

// Todas las rutas protegidas en un solo grupo
Route::middleware('auth:sanctum')->group(function () {
    // Rutas para la gestión de usuarios - SOLO UNA VEZ
    Route::get('/usuarios/{id}', [RegistrarUsuarioController::class, 'show']);
    Route::put('/usuarios/{id}', [RegistrarUsuarioController::class, 'update']);
    
    // Ruta para cerrar sesión
    Route::post('/logout', [CerrarSesionController::class, 'logout']);
    
    // Ruta para verificar autenticación
    Route::get('/check-auth', function (Request $request) {
        return $request->user();
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Ubicación
    Route::post('/guardar-ubicacion', [UserLocationController::class, 'store']);

    // Rutas para la gestión de mascotas
    Route::post('/mascotas', [MascotaController::class, 'store']);
    Route::get('/mascotas', [MascotaController::class, 'index']);
    Route::get('/mascotas/{id}', [MascotaController::class, 'show']); 
    Route::put('/mascotas/{id}', [MascotaController::class, 'update']);
    Route::post('/mascotas/{id}', [MascotaController::class, 'update']);
    Route::get('/mascotas/motivos/baja', [MascotaController::class, 'obtenerMotivosBaja']);
    Route::post('/mascotas/{id}/baja', [MascotaController::class, 'darDeBaja']);

    // Rutas para administradores
    Route::get('/solicitudes-pendientes', [VeterinarioController::class, 'obtenerSolicitudesPendientes']);
    Route::post('/solicitudes/{id}/aprobar', [VeterinarioController::class, 'aprobarSolicitud']);
    Route::post('/solicitudes/{id}/rechazar', [VeterinarioController::class, 'rechazarSolicitud']);
});

// Ruta pública para testing de ubicación
Route::post('/registro-ubicacion', function (Request $request) {
    return response()->json(['ok' => true]);
});

// Ruta pública para testing de ubicación
Route::post('/registro-ubicacion', function (Request $request) {
    Log::info('📍 Registro de ubicación alcanzado', [
        'payload' => $request->all(),
        'ip' => $request->ip(),
    ]);
    return response()->json(['ok' => true]);
});