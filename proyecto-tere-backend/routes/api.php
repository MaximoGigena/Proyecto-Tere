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
use App\Http\Controllers\ControllersProcedimientos\DesparasitacionController;
use App\Http\Controllers\ControllersProcedimientos\RevisionController;
use App\Http\Controllers\ControllersProcedimientos\AlergiaController;
use App\Http\Controllers\ControllersProcedimientos\CirugiaController;
use App\Http\Controllers\ControllersProcedimientos\DiagnosticoController;
use App\Http\Controllers\ControllersProcedimientos\PaliativoController;
use App\Http\Controllers\ControllersProcedimientos\TerapiaController;
use App\Http\Controllers\ControllersProcedimientos\FarmacoController;
use App\Http\Controllers\ControllersProcedimientos\TipoProcedimientoController;
use App\Http\Controllers\CentroVeterinarioController;
use App\Http\Controllers\ControllersProcedimientos\VacunaController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\UsuarioContactoController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\OfertaAdopcionController;
use App\Http\Controllers\ManejarOfertasController;
use App\Http\Controllers\SolicitudAdopcionController;
use App\Http\Controllers\ProcesoAdopcionController;
use App\Http\Controllers\HistorialTransferenciaController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\DenunciaController;
use App\Http\Controllers\FiltrosMascotasController;
use App\Http\Controllers\SancionController;
use App\Http\Controllers\NotificacionController;
use App\Models\OfertaAdopcion;
use App\Models\ContactoUsuario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

// =============================================
// RUTAS DE TELEGRAM - EN API (SIN MIDDLEWARE WEB)
// =============================================
Route::prefix('telegram')->group(function () {
    // Webhook principal
    Route::post('/webhook', [TelegramWebhookController::class, 'handleWebhook']);
    
    // Configuración
    Route::post('/set-webhook', [TelegramWebhookController::class, 'setWebhook']);
    Route::post('/remove-webhook', [TelegramWebhookController::class, 'removeWebhook']);
    
    // ✅ CORREGIDO: Ruta para verificar por email
    Route::get('/verificar-por-email', [TelegramController::class, 'verificarChatIdPorEmail']);
});

// En api.php - agregar esto temporalmente
Route::get('/telegram/debug-test', function() {
    Log::info('✅ DEBUG: Ruta API de Telegram funcionando');
    return response()->json([
        'status' => 'success',
        'message' => 'API Telegram route working',
        'timestamp' => now()
    ]);
});

// Rutas temporales de prueba en API
Route::get('/test-simple', function () {
    return response()->json([
        'status' => 'success', 
        'message' => '✅ Simple test route working in API',
        'timestamp' => now()
    ]);
});

Route::post('/test-post', function () {
    return response()->json([
        'status' => 'success',
        'message' => '✅ Test POST route working in API',
        'timestamp' => now()
    ]);
});

// =============================================
// RUTAS PÚBLICAS - SIN AUTENTICACIÓN
// =============================================

// Registros públicos
Route::post('registrar-usuario', [RegistrarUsuarioController::class, 'register']);
Route::post('/registrar-veterinario', [VeterinarioController::class, 'store']);


// Rutas de autenticación de Google
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
Route::post('/auth/google/complete-registration', [GoogleAuthController::class, 'completeRegistration']);

// =============================================
// RUTAS PROTEGIDAS - CON AUTENTICACIÓN
// =============================================
Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckUserSuspended::class])->group(function () {
    
    // Rutas para la gestión de usuarios
    Route::post('/actualizar-datos-opcionales', [RegistrarUsuarioController::class, 'actualizarDatosOpcionales']);
    Route::post('/actualizar-datos-contacto', [RegistrarUsuarioController::class, 'actualizarDatosContacto']);
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

    Route::prefix('notificaciones')->group(function () {
        Route::get('/', [NotificacionController::class, 'index']);
        Route::get('/estadisticas', [NotificacionController::class, 'estadisticas']);
        Route::put('/{id}/leer', [NotificacionController::class, 'marcarComoLeida']);
        Route::put('/leer-todas', [NotificacionController::class, 'marcarTodasComoLeidas']);
        Route::delete('/{id}', [NotificacionController::class, 'destroy']);
    });

    // NUEVAS RUTAS PARA ADOPCIONES
    // Obtener todas las mascotas del usuario autenticado
    Route::get('/mis-mascotas', [MascotaController::class, 'misMascotas']);
    
    // Obtener mascotas del usuario autenticado que NO están en adopción
    Route::get('/mis-mascotas/disponibles', [MascotaController::class, 'misMascotasDisponibles']);
    
    // Obtener mascotas del usuario autenticado que SÍ están en adopción
    Route::get('/mis-mascotas/en-adopcion', [MascotaController::class, 'misMascotasEnAdopcion']);
    
    Route::get('/usuarios/{id}/medios', [UsuarioContactoController::class, 'obtenerMedios']);
    
    // =============================================
    // RUTAS PARA SOLICITUDES DE ADOPCIÓN (FUERA DEL GRUPO ADOPCIONES)
    // =============================================
    
    // Rutas para la gestión de solicitudes de adopción
    Route::get('/solicitudes/recibidas', [SolicitudAdopcionController::class, 'solicitudesRecibidas']);
    Route::get('/solicitudes/todas-recibidas', [SolicitudAdopcionController::class, 'todasSolicitudesRecibidas']);
    Route::get('/solicitudes/pendientes/conteo', [SolicitudAdopcionController::class, 'conteoSolicitudesPendientes']);
    
    // Ruta para obtener una solicitud específica por ID
    Route::get('/solicitudes/{id}', [SolicitudAdopcionController::class, 'show']);
    
    // Ruta para aprobar/rechazar solicitud
    Route::put('/solicitudes/{id}/aprobar', [SolicitudAdopcionController::class, 'aprobar']);
    Route::put('/solicitudes/{id}/rechazar', [SolicitudAdopcionController::class, 'rechazar']);
    
    // Ruta para guardar notas en una solicitud
    Route::put('/solicitudes/{id}/notas', [SolicitudAdopcionController::class, 'guardarNotas']);

    // Obtener opciones de filtro
    Route::get('/filtros/opciones', [FiltrosMascotasController::class, 'obtenerOpcionesFiltro']);
    
    // Obtener especies disponibles
    Route::get('/filtros/especies-disponibles', [FiltrosMascotasController::class, 'obtenerEspeciesDisponibles']);
    
    // =============================================
    // RUTAS PARA ADOPCIONES (GRUPO SEPARADO)
    // =============================================
    Route::prefix('adopciones')->group(function () {

        // Rutas para el sistema de swipe
        Route::get('/ofertas-para-swipe', [ManejarOfertasController::class, 'obtenerOfertasParaSwipe']);
        Route::post('/registrar-interaccion', [ManejarOfertasController::class, 'registrarInteraccion']);
        // Ofertas de adopción
        Route::get('/ofertas-disponibles', [OfertaAdopcionController::class, 'getOfertasDisponibles']);
        Route::get('/', [OfertaAdopcionController::class, 'index']);
        Route::post('/', [OfertaAdopcionController::class, 'store']);
        Route::get('/{id}', [OfertaAdopcionController::class, 'show']);
        Route::put('/{id}', [OfertaAdopcionController::class, 'update']);
        Route::delete('/{id}', [OfertaAdopcionController::class, 'cancelar']);
        
        Route::get('/mis-mascotas/disponibles', [OfertaAdopcionController::class, 'getMascotasDisponibles']);
        Route::get('/mis-mascotas/en-adopcion', [OfertaAdopcionController::class, 'getOfertasUsuario']);
        
        // Ruta para cancelar por mascotaId
        Route::post('/{mascotaId}/cancelar', function($mascotaId) {
            $controller = new OfertaAdopcionController();
            return $controller->cancelarPorMascota($mascotaId);
        });
       
        
        // Ruta para obtener oferta por mascota ID (debe estar dentro de adopciones)
        Route::get('/ofertas/mascota/{mascotaId}', [OfertaAdopcionController::class, 'getOfertaPorMascota']);
        
        // Rutas para manejar ofertas (mantener compatibilidad)
        Route::get('/ofertas/{idOferta}', [ManejarOfertasController::class, 'obtenerOferta']);

        // ✅ Ruta de prueba
        Route::get('/test-ofertas-simple', function() {
            Log::info('Test ofertas simple llamado');
            
            try {
                $user = Auth::user();
                
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No autenticado'
                    ], 401);
                }
                
                $ofertas = OfertaAdopcion::where('estado_oferta', 'publicada')
                    ->where('id_usuario_responsable', '!=', $user->id)
                    ->take(5)
                    ->get();
                
                return response()->json([
                    'success' => true,
                    'user_id' => $user->id,
                    'total_ofertas' => $ofertas->count(),
                    'data' => $ofertas->map(function($oferta) {
                        return [
                            'id' => $oferta->id_oferta,
                            'estado' => $oferta->estado_oferta,
                            'mascota_id' => $oferta->id_mascota,
                        ];
                    })
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
        });

        // Rutas para la gestión de solicitudes de adopción (DENTRO de adopciones, para otras funcionalidades)
        Route::prefix('solicitudes-adopcion')->group(function () {
            Route::post('/', [SolicitudAdopcionController::class, 'store']);
            Route::get('/', [SolicitudAdopcionController::class, 'index']);
            Route::get('/estadisticas', [SolicitudAdopcionController::class, 'estadisticas']);
            Route::get('/verificar/{idMascota}', [SolicitudAdopcionController::class, 'verificarSolicitud']);
            Route::post('/cancelar/{id}', [SolicitudAdopcionController::class, 'cancelar']);
        });
        
        // ✅ Rutas específicas para ofertas (opcional, si necesitas mantener acceso directo)
        Route::prefix('ofertas')->group(function () {
            Route::get('/{id}', [OfertaAdopcionController::class, 'show']);
        });
    });

    Route::prefix('procesos-adopcion')->group(function () {
        Route::get('/', [ProcesoAdopcionController::class, 'index']);
        Route::get('/{id}', [ProcesoAdopcionController::class, 'show']);
        Route::post('/{id}/actualizar-estado', [ProcesoAdopcionController::class, 'actualizarEstado']);
        Route::post('/{id}/confirmar-entrega', [ProcesoAdopcionController::class, 'confirmarEntrega']);
        Route::post('/{id}/agregar-seguimiento', [ProcesoAdopcionController::class, 'agregarSeguimiento']);
        Route::post('/{id}/finalizar', [ProcesoAdopcionController::class, 'finalizarConEvaluacion']);
    });

    // Historial de transferencias
    Route::prefix('historial-transferencias')->group(function () {
        Route::get('/mascota/{mascotaId}', [HistorialTransferenciaController::class, 'porMascota']);
        Route::get('/usuario', [HistorialTransferenciaController::class, 'porUsuario']);
        Route::get('/usuario/estadisticas', [HistorialTransferenciaController::class, 'estadisticasUsuario']);
    });

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
    Route::post('/centros-veterinarios/registrar', [CentroVeterinarioController::class, 'registrar']);
    Route::get('/centros-veterinarios', [CentroVeterinarioController::class, 'index']);
    Route::get('/centros-veterinarios/{id}', [CentroVeterinarioController::class, 'show']);
    Route::put('/centros-veterinarios/{id}', [CentroVeterinarioController::class, 'update']);
    Route::delete('/centros-veterinarios/{id}', [CentroVeterinarioController::class, 'destroy']);

    Route::prefix('mascotas/{mascota}')->group(function () {
        // Vacunas
        Route::get('/vacunas/crear', [VacunaController::class, 'create'])->name('vacunas.create');
        Route::post('/vacunas', [VacunaController::class, 'store'])->name('vacunas.store');
        Route::get('/vacunas/{vacuna}', [VacunaController::class, 'show'])->name('vacunas.show');
        Route::get('/vacunas/{vacuna}/editar', [VacunaController::class, 'edit'])->name('vacunas.edit');
        Route::put('/vacunas/{vacuna}', [VacunaController::class, 'update'])->name('vacunas.update');
        Route::get('/vacunas/{vacuna}', [VacunaController::class, 'show'])->name('api.vacunas.show');
        Route::put('/vacunas/{vacuna}', [VacunaController::class, 'updateApi'])->name('api.vacunas.update');


        Route::get('/desparasitaciones', [DesparasitacionController::class, 'index']);
        Route::post('/desparasitaciones', [DesparasitacionController::class, 'store']);

        // Revisiones médicas
        Route::get('/revisiones', [RevisionController::class, 'index']);
        Route::post('/revisiones', [RevisionController::class, 'store']);
        Route::get('/revisiones/{revision}', [RevisionController::class, 'show']);
        Route::put('/revisiones/{revision}', [RevisionController::class, 'update']);
        Route::delete('/revisiones/{revision}', [RevisionController::class, 'destroy']);

        Route::prefix('alergias')->group(function () {
            Route::get('/', [AlergiaController::class, 'index']);
            Route::post('/', [AlergiaController::class, 'store']);
            Route::get('/tipos', [AlergiaController::class, 'getTiposAlergia']);
            Route::get('/{alergiaId}', [AlergiaController::class, 'show']);
            Route::put('/{alergiaId}', [AlergiaController::class, 'update']);
            Route::delete('/{alergiaId}', [AlergiaController::class, 'destroy']);
        });

        Route::get('/cirugias', [CirugiaController::class, 'index'])->name('cirugias.index');
        Route::post('/cirugias', [CirugiaController::class, 'store'])->name('cirugias.store');
        Route::get('/cirugias/{cirugia}', [CirugiaController::class, 'show'])->name('cirugias.show');
        Route::put('/cirugias/{cirugia}', [CirugiaController::class, 'update'])->name('cirugias.update');
        Route::delete('/cirugias/{cirugia}', [CirugiaController::class, 'destroy'])->name('cirugias.destroy');

        Route::get('/diagnosticos', [DiagnosticoController::class, 'index']);
        Route::post('/diagnosticos', [DiagnosticoController::class, 'store']);
        Route::get('/diagnosticos/estadisticas', [DiagnosticoController::class, 'estadisticas']);
        
        Route::prefix('diagnosticos/{diagnostico}')->group(function () {
            Route::get('/', [DiagnosticoController::class, 'show']);
            Route::put('/', [DiagnosticoController::class, 'update']);
            Route::delete('/', [DiagnosticoController::class, 'destroy']);
            Route::post('/marcar-resuelto', [DiagnosticoController::class, 'marcarComoResuelto']);
        });

        Route::prefix('terapias')->group(function () {
            Route::get('/', [TerapiaController::class, 'index']);
            Route::post('/', [TerapiaController::class, 'store']);
            Route::get('/activas', [TerapiaController::class, 'activas']);
        });

        Route::prefix('farmacos')->group(function () {
            Route::get('/', [FarmacoController::class, 'index']);
            Route::post('/', [FarmacoController::class, 'store']);
            Route::get('/{id}', [FarmacoController::class, 'show']);
            Route::put('/{farmaco}', [FarmacoController::class, 'update']);
            Route::delete('/{farmaco}', [FarmacoController::class, 'destroy']);
            Route::get('/{farmacoId}/archivos', [FarmacoController::class, 'obtenerArchivos']);
        });

         Route::prefix('cirugias')->group(function () {
            Route::get('/', [CirugiaController::class, 'index']);
            Route::post('/', [CirugiaController::class, 'store']);
            Route::get('/estadisticas', [CirugiaController::class, 'estadisticas']);
            
            Route::prefix('{cirugia}')->group(function () {
                Route::get('/', [CirugiaController::class, 'show']);
                Route::put('/', [CirugiaController::class, 'update']);
                Route::delete('/', [CirugiaController::class, 'destroy']);
                Route::get('/archivos/{archivo}/descargar', [CirugiaController::class, 'descargarArchivo']);
            });
        });
        // Agregar estas nuevas rutas para procedimientos paliativos
        Route::prefix('paliativos')->group(function () {
            Route::get('/', [PaliativoController::class, 'index']);
            Route::post('/', [PaliativoController::class, 'store']);
            Route::get('/estadisticas', [PaliativoController::class, 'estadisticas']);
            
            Route::prefix('{paliativo}')->group(function () {
                Route::get('/', [PaliativoController::class, 'show']);
                Route::put('/', [PaliativoController::class, 'update']);
                Route::delete('/', [PaliativoController::class, 'destroy']);
                Route::get('/archivos/{archivo}/descargar', [PaliativoController::class, 'descargarArchivo']);
            });
        });
    });
    
    Route::get('/cirugias/archivos/{archivo}/descargar', [CirugiaController::class, 'descargarArchivo'])
    ->name('cirugias.archivo.download');

    // Listar todas las vacunas de una mascota
    Route::get('/mascotas/{mascotaId}/vacunas', [VacunaController::class, 'index']);

    // Rutas para obtener medios de contacto de un usuario
    Route::get('/usuarios/{usuarioId}/medios', [UsuarioContactoController::class, 'obtenerMedios']);

    // Rutas para la integración con Telegram (estas SÍ necesitan autenticación)
    Route::post('/send-document', [TelegramController::class, 'sendDocument']);
    Route::get('/send-stored-document/{filename}', [TelegramController::class, 'sendStoredDocument']);
    
    // Rutas protegidas de Telegram
    Route::post('/telegram/guardar-chat-id', [TelegramController::class, 'guardarChatId']);
    Route::get('/usuarios/{usuarioId}/telegram-chat-id', [TelegramController::class, 'obtenerChatId']);

    // Chats
    Route::prefix('chats')->group(function () {
        Route::get('/', [ChatController::class, 'index']); // Listar chats
        Route::post('/crear', [ChatController::class, 'crearObtenerChat']); // Crear/obtener chat
        Route::get('/{chatId}', [ChatController::class, 'show']); // Ver chat específico
        Route::delete('/{chatId}', [ChatController::class, 'destroy']); // Eliminar chat
        Route::post('/{chatId}/leido', [ChatController::class, 'marcarLeido']); // Marcar como leído
        
        // Mensajes
        Route::get('/{chatId}/mensajes', [MensajeController::class, 'index']); // Listar mensajes
        Route::post('/{chatId}/mensajes', [MensajeController::class, 'store']); // Enviar mensaje
        Route::delete('/{chatId}/mensajes/{mensajeId}', [MensajeController::class, 'destroy']); // Eliminar mensaje
        Route::post('/{chatId}/mensajes/leidos', [MensajeController::class, 'marcarLeidos']); // Marcar mensajes como leídos
        Route::get('/{chatId}/buscar', [MensajeController::class, 'buscar']); // Buscar en mensajes
    });

    // =============================================
    // RUTAS PARA TODOS LOS USUARIOS AUTENTICADOS
    // =============================================
    
    // Denuncias para todos los usuarios (crear y verificar)
    Route::prefix('denuncias')->group(function () {
        Route::post('/', [DenunciaController::class, 'store']);
        Route::get('/categorias', [DenunciaController::class, 'categorias']);
        Route::get('/verificar', [DenunciaController::class, 'verificarDenuncia']);
    });

    // Sanciones - rutas para usuarios normales
    Route::get('/usuario/{usuario}/sanciones', [SancionController::class, 'obtenerSancionesUsuario']);
    Route::get('/usuario/{usuario}/verificar-restricciones', [SancionController::class, 'verificarRestriccionesUsuario']);
    Route::get('/usuario/sancion-activa', [SancionController::class, 'obtenerSancionActivaUsuario']);
    
    // =============================================
    // RUTAS PARA ADMINISTRADORES (SÓLO ACCESO ADMIN)
    // =============================================
   Route::middleware(['auth:sanctum', \App\Http\Middleware\AdminMiddleware::class])->group(function () {
        
        // Denuncias - administración (solo para admin)
        Route::prefix('denuncias')->group(function () {
            Route::get('/', [DenunciaController::class, 'index']);
            Route::get('/{id}', [DenunciaController::class, 'show']);
            Route::put('/{id}/estado', [DenunciaController::class, 'actualizarEstado']);
            Route::put('/{id}/notas', [DenunciaController::class, 'actualizarNotas']);
            Route::delete('/{id}', [DenunciaController::class, 'destroy']);
            Route::get('/filtros/opciones', [DenunciaController::class, 'getFiltrosOpciones']);
        });

        // Sanciones - administración (solo para admin)
        Route::prefix('sanciones')->group(function () {
            Route::get('/', [SancionController::class, 'index']);
            Route::post('/denuncia/{denuncia}/aplicar', [SancionController::class, 'aplicarSancion']);
            Route::post('/{sancion}/revocar', [SancionController::class, 'revocarSancion']);
            Route::get('/denuncia/{denuncia}/recomendacion', [SancionController::class, 'recomendarSancion']);
            Route::get('/historial', [SancionController::class, 'obtenerHistorialSancionesUsuario']);
            Route::get('/activa', [SancionController::class, 'obtenerSancionActivaUsuario']);
        });

        // Administración de veterinarios (solo para admin)
        Route::get('/solicitudes-pendientes', [VeterinarioController::class, 'obtenerSolicitudesPendientes']);
        Route::post('/solicitudes/{id}/aprobar', [VeterinarioController::class, 'aprobarSolicitud']);
        Route::post('/solicitudes/{id}/rechazar', [VeterinarioController::class, 'rechazarSolicitud']);
    });
});

// Rutas que pueden acceder usuarios autenticados, incluso si están suspendidos

// Rutas que pueden acceder usuarios autenticados, incluso si están suspendidos
Route::middleware(['auth:sanctum'])->group(function () {
    // Rutas para sanciones que pueden acceder usuarios suspendidos
    Route::get('/usuario/sancion-activa-detallada', [SancionController::class, 'obtenerSancionActivaDetallada']);
    Route::get('/usuario/historial-sanciones', [SancionController::class, 'obtenerHistorialSancionesUsuario']);
    Route::get('/usuario/sancion-activa', [SancionController::class, 'obtenerSancionActivaUsuario']);
    Route::post('/logout', [CerrarSesionController::class, 'logout']);
});

// =============================================
// RUTAS PÚBLICAS ADICIONALES
// =============================================

// Ruta pública para testing de ubicación
Route::post('/registro-ubicacion', function (Request $request) {
    Log::info('📍 Registro de ubicación alcanzado', [
        'payload' => $request->all(),
        'ip' => $request->ip(),
    ]);
    return response()->json(['ok' => true]);
});
