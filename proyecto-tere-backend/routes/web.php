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

Route::get('/login', function () {
    return response()->json(['message' => 'Por favor inicia sesión'], 401);
})->name('login');

