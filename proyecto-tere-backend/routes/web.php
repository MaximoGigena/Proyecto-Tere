<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\FacebookAuthController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('auth/google')->group(function () {
    Route::get('/', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.login');
    Route::get('/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
    Route::post('/token', [GoogleAuthController::class, 'handleToken']);
});

// Rutas de Facebook (misma estructura que Google)
Route::prefix('auth/facebook')->group(function () {
    Route::get('/', [FacebookAuthController::class, 'redirectToFacebook'])->name('facebook.login');
    Route::get('/callback', [FacebookAuthController::class, 'handleFacebookCallback']);
    Route::post('/complete-registration', [FacebookAuthController::class, 'completeRegistration']);
    Route::get('/verificar-estado', [FacebookAuthController::class, 'verificarEstadoVeterinario']);
});

// Ruta para la selección de perfil
Route::get('/seleccionarRegistro', function (Request $request) {
    return view('auth.profile-selection', [
        'email' => $request->query('email'),
        'name' => $request->query('name'),
        'google_id' => $request->query('google_id'),
        'avatar' => $request->query('avatar')
    ]);
})->name('profile.selection');

// Ruta para la vista de cuenta suspendida (accesible incluso estando suspendido)
Route::get('/cuenta-suspendida', function () {
    return view('cuenta-suspendida'); // Tu vista Vue
})->name('cuenta-suspendida');

// ✅ Ruta de login
Route::get('/login', function () {
    return response()->json(['message' => 'Por favor inicia sesión'], 401);
})->name('login');

// ✅ Proteger las rutas web también
// NOTA: Si usas 'user.suspended' en API, usa el mismo en web
Route::middleware(['auth', \App\Http\Middleware\CheckUserSuspended::class])->group(function () {
    // Rutas web protegidas
    Route::get('/dashboard', function () {
        return view('dashboard');
    });
});