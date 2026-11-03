<?php

use App\Http\Controllers\Auth\ClaveUnicaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', \App\Http\Controllers\Publico\InicioController::class)->name('inicio');

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [LoginController::class, 'mostrar'])->name('login');
    Route::post('/ingresar', [LoginController::class, 'entrar'])->middleware('throttle:10,1');
});

Route::get('/claveunica/redirigir', [ClaveUnicaController::class, 'redirigir'])->name('claveunica.redirigir');
Route::get('/claveunica/callback', [ClaveUnicaController::class, 'callback'])->name('claveunica.callback');

Route::middleware('auth')->group(function () {
    Route::post('/salir', [LoginController::class, 'salir'])->name('logout');
    Route::get('/cuenta/password', [PasswordController::class, 'mostrar'])->name('password.cambiar');
    Route::post('/cuenta/password', [PasswordController::class, 'actualizar'])->name('password.actualizar');
});

Route::middleware(['auth', 'cambio.password'])->group(function () {
    foreach (['admin', 'secretaria', 'tutor', 'practicante', 'gobernador', 'postulante', 'director', 'director_general'] as $portal) {
        require __DIR__."/portales/{$portal}.php";
    }
});

require __DIR__.'/portales/postulacion.php';
