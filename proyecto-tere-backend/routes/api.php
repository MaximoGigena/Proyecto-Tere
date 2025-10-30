<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MascotaController;
use App\Http\Controllers\Auth\RegistrarUsuarioController;
use App\Http\Controllers\UserLocationController;
use App\Http\Controllers\VeterinarioController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\CerrarSesionController;
use App\Http\Controllers\ControllersTiposProcedimiento\TipoRevisionController;
use App\Http\Controllers\ControllersTiposProcedimiento\TipoVacunaController;
use App\Http\Controllers\ControllersTiposProcedimiento\TipoAlergiaController;
use App\Http\Controllers\ControllersTiposProcedimiento\TipoDesparasitacionController;
use App\Http\Controllers\ControllersTiposProcedimiento\TipoTerapiaController;
use App\Http\Controllers\ControllersTiposProcedimiento\TipoDiagnosticoController;
use App\Http\Controllers\ControllersTiposProcedimiento\TipoCirugiaController;
use App\Http\Controllers\ControllersTiposProcedimiento\TipoPaliativoController;
use App\Http\Controllers\ControllersTiposProcedimiento\TipoFarmacoController;
use App\Http\Controllers\CentroVeterinarioController;
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
    Route::post('/usuarios/{id}', [RegistrarUsuarioController::class, 'update']);
    
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
    Route::get('/mascotas/buscar', [MascotaController::class, 'buscar']);
    Route::get('/mascotas/{id}', [MascotaController::class, 'show']); 
    Route::put('/mascotas/{id}', [MascotaController::class, 'update']);
    Route::post('/mascotas/{id}', [MascotaController::class, 'update']);
    Route::get('/mascotas/motivos/baja', [MascotaController::class, 'obtenerMotivosBaja']);
    Route::post('/mascotas/{id}/baja', [MascotaController::class, 'darDeBaja']);
    

    // Rutas para administradores
    Route::get('/solicitudes-pendientes', [VeterinarioController::class, 'obtenerSolicitudesPendientes']);
    Route::post('/solicitudes/{id}/aprobar', [VeterinarioController::class, 'aprobarSolicitud']);
    Route::post('/solicitudes/{id}/rechazar', [VeterinarioController::class, 'rechazarSolicitud']);

    // Rutas para la gestión de tipos de vacuna - SOLO VETERINARIOS
    
    Route::prefix('tipos-vacuna')->group(function () {
        Route::get('/', [TipoVacunaController::class, 'index']);
        Route::post('/', [TipoVacunaController::class, 'store']);
        Route::get('/{tipoVacuna}', [TipoVacunaController::class, 'show']);
        Route::put('/{tipoVacuna}', [TipoVacunaController::class, 'update']);
        Route::post('/{tipoVacuna}', [TipoVacunaController::class, 'update']);
        Route::delete('/{tipoVacuna}', [TipoVacunaController::class, 'destroy']);
    });

    
    // Rutas para la gestión de tipos de desparasitación - SOLO VETERINARIOS
    Route::prefix('tipos-desparasitacion')->group(function () {
        Route::get('/', [TipoDesparasitacionController::class, 'index']);
        Route::post('/', [TipoDesparasitacionController::class, 'store']);
        Route::get('/{tipoDesparasitacion}', [TipoDesparasitacionController::class, 'show']);
        Route::put('/{tipoDesparasitacion}', [TipoDesparasitacionController::class, 'update']);
        Route::post('/{tipoDesparasitacion}', [TipoDesparasitacionController::class, 'update']);
        Route::delete('/{tipoDesparasitacion}', [TipoDesparasitacionController::class, 'destroy']);
    });

    // Rutas para la gestión de tipos de revisión - SOLO VETERINARIOS
    Route::prefix('tipos-revision')->group(function () {
        Route::get('/', [TipoRevisionController::class, 'index']);
        Route::post('/', [TipoRevisionController::class, 'store']);
        Route::get('/areas-predefinidas', [TipoRevisionController::class, 'areasPredefinidas']);
        Route::get('/especie/{especie}', [TipoRevisionController::class, 'porEspecie']);
        Route::get('/{tipoRevision}', [TipoRevisionController::class, 'show']);
        Route::put('/{tipoRevision}', [TipoRevisionController::class, 'update']);
        Route::post('/{tipoRevision}', [TipoRevisionController::class, 'update']);
        Route::delete('/{tipoRevision}', [TipoRevisionController::class, 'destroy']);
        Route::post('/{id}/restore', [TipoRevisionController::class, 'restore']);
    });

    // Rutas para la gestión de tipos de alergia - SOLO VETERINARIOS
    Route::prefix('tipos-alergia')->group(function () {
        Route::get('/', [TipoAlergiaController::class, 'index']);
        Route::post('/', [TipoAlergiaController::class, 'store']);
        Route::get('/{tipoAlergia}', [TipoAlergiaController::class, 'show']);
        Route::put('/{tipoAlergia}', [TipoAlergiaController::class, 'update']);
        Route::post('/{tipoAlergia}', [TipoAlergiaController::class, 'update']);
        Route::delete('/{tipoAlergia}', [TipoAlergiaController::class, 'destroy']);
        Route::post('/{id}/restore', [TipoAlergiaController::class, 'restore']);
        Route::get('/categorias/predefinidas', [TipoAlergiaController::class, 'categoriasPredefinidas']);
        Route::get('/areas/predefinidas', [TipoAlergiaController::class, 'areasPredefinidas']);
        Route::get('/niveles-riesgo/predefinidos', [TipoAlergiaController::class, 'nivelesRiesgoPredefinidos']);
        Route::get('/especies/predefinidas', [TipoAlergiaController::class, 'especiesPredefinidas']);
        Route::get('/especie/{especie}', [TipoAlergiaController::class, 'porEspecie']);
    });

    // Rutas para la gestión de tipos de cirugía - SOLO VETERINARIOS
    Route::prefix('tipos-cirugia')->group(function () {
        Route::get('/', [TipoCirugiaController::class, 'index']);
        Route::post('/', [TipoCirugiaController::class, 'store']);
        Route::get('/{tipoCirugia}', [TipoCirugiaController::class, 'show']);
        Route::put('/{tipoCirugia}', [TipoCirugiaController::class, 'update']);
        Route::post('/{tipoCirugia}', [TipoCirugiaController::class, 'update']);
        Route::delete('/{tipoCirugia}', [TipoCirugiaController::class, 'destroy']);
        Route::post('/{id}/restore', [TipoCirugiaController::class, 'restore']);
        Route::get('/especies/predefinidas', [TipoCirugiaController::class, 'especiesPredefinidas']);
        Route::get('/frecuencias/predefinidas', [TipoCirugiaController::class, 'frecuenciasPredefinidas']);
        Route::get('/unidades-duracion/predefinidas', [TipoCirugiaController::class, 'unidadesDuracionPredefinidas']);
        Route::get('/especie/{especie}', [TipoCirugiaController::class, 'porEspecie']);
    });

    // Rutas para la gestión de tipos de terapia - SOLO VETERINARIOS
    Route::prefix('tipos-terapia')->group(function () {
        Route::get('/', [TipoTerapiaController::class, 'index']);
        Route::post('/', [TipoTerapiaController::class, 'store']);
        Route::get('/{tipoTerapia}', [TipoTerapiaController::class, 'show']);
        Route::put('/{tipoTerapia}', [TipoTerapiaController::class, 'update']);
        Route::post('/{tipoTerapia}', [TipoTerapiaController::class, 'update']);
        Route::delete('/{tipoTerapia}', [TipoTerapiaController::class, 'destroy']);
        Route::post('/{id}/restore', [TipoTerapiaController::class, 'restore']);
        Route::get('/especies/predefinidas', [TipoTerapiaController::class, 'especiesPredefinidas']);
        Route::get('/frecuencias/predefinidas', [TipoTerapiaController::class, 'frecuenciasPredefinidas']);
        Route::get('/unidades-duracion/predefinidas', [TipoTerapiaController::class, 'unidadesDuracionPredefinidas']);
        Route::get('/especie/{especie}', [TipoTerapiaController::class, 'porEspecie']);
    });

    // Rutas para la gestión de tipos de fármaco - SOLO VETERINARIOS
    Route::prefix('tipos-farmaco')->group(function () {
        Route::get('/', [TipoFarmacoController::class, 'index']);
        Route::post('/', [TipoFarmacoController::class, 'store']);
        Route::get('/opciones-predefinidas', [TipoFarmacoController::class, 'opcionesPredefinidas']);
        Route::get('/especie/{especie}', [TipoFarmacoController::class, 'porEspecie']);
        Route::get('/categoria/{categoria}', [TipoFarmacoController::class, 'porCategoria']);
        Route::get('/{tipoFarmaco}', [TipoFarmacoController::class, 'show']);
        Route::put('/{tipoFarmaco}', [TipoFarmacoController::class, 'update']);
        Route::post('/{tipoFarmaco}', [TipoFarmacoController::class, 'update']);
        Route::delete('/{tipoFarmaco}', [TipoFarmacoController::class, 'destroy']);
        Route::post('/{id}/restore', [TipoFarmacoController::class, 'restore']);
    });

    // Rutas para tipos de diagnóstico
    Route::prefix('tipos-diagnostico')->group(function () {
        Route::get('/', [TipoDiagnosticoController::class, 'index']);
        Route::post('/', [TipoDiagnosticoController::class, 'store']);
        Route::get('/estadisticas', [TipoDiagnosticoController::class, 'estadisticas']);
        Route::post('/filtrar', [TipoDiagnosticoController::class, 'filtrar']);
        Route::get('/{id}', [TipoDiagnosticoController::class, 'show']);
        Route::put('/{id}', [TipoDiagnosticoController::class, 'update']);
        Route::delete('/{id}', [TipoDiagnosticoController::class, 'destroy']);
    });

    // Rutas para la gestión de tipos de procedimiento paliativo - SOLO VETERINARIOS
    Route::prefix('tipos-procedimiento-paliativo')->group(function () {
        Route::get('/', [TipoPaliativoController::class, 'index']);
        Route::post('/', [TipoPaliativoController::class, 'store']);
        Route::get('/opciones-predefinidas', [TipoPaliativoController::class, 'opcionesPredefinidas']);
        Route::get('/especie/{especie}', [TipoPaliativoController::class, 'porEspecie']);
        Route::get('/{id}', [TipoPaliativoController::class, 'show']);
        Route::put('/{id}', [TipoPaliativoController::class, 'update']);
        Route::post('/{id}', [TipoPaliativoController::class, 'update']);
        Route::delete('/{id}', [TipoPaliativoController::class, 'destroy']);
        Route::post('/{id}/restore', [TipoPaliativoController::class, 'restore']);
        Route::post('/{id}/toggle-activo', [TipoPaliativoController::class, 'toggleActivo']);
    });

    Route::post('/registrar-centro', [CentroVeterinarioController::class, 'registrar']);
    Route::get('/centros-veterinarios', [CentroVeterinarioController::class, 'index']);
    Route::get('/centros-veterinarios/{id}', [CentroVeterinarioController::class, 'show']);
    Route::put('/centros-veterinarios/{id}', [CentroVeterinarioController::class, 'update']);
    Route::delete('/centros-veterinarios/{id}', [CentroVeterinarioController::class, 'destroy']);
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