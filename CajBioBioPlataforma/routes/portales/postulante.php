<?php

use App\Http\Controllers\Postulante\PerfilController;
use Illuminate\Support\Facades\Route;

// Portal de postulación accesible con rol 'postulante' o histórico 'practicante'
Route::prefix('mi-postulacion')->name('postulante.')->middleware('rol:postulante,practicante')->group(function () {
    Route::get('/', [PerfilController::class, 'inicio'])->name('inicio');
    Route::get('/datos', [PerfilController::class, 'datos'])->name('datos');
    Route::post('/datos', [PerfilController::class, 'guardarDatos'])->middleware('throttle:30,1')->name('datos.guardar');
});
