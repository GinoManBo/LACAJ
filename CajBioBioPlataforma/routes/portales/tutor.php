<?php

use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\Tutor\TutorController;
use Illuminate\Support\Facades\Route;

Route::prefix('notificaciones')->name('notificaciones.')->group(function () {
    Route::get('/', [NotificacionController::class, 'index'])->name('index');
    Route::post('/leer-todas', [NotificacionController::class, 'leerTodas'])->name('leer-todas');
    Route::post('/{id}/leer', [NotificacionController::class, 'leer'])->whereNumber('id')->name('leer');
});

Route::prefix('tutor')->name('tutor.')->middleware('rol:abogado')->group(function () {
    Route::get('/', [TutorController::class, 'inicio'])->name('inicio');
    Route::get('/practicantes/{practica}', [TutorController::class, 'ver'])->whereNumber('practica')->name('practicantes.ver');
    Route::post('/practicantes/{practica}/evaluacion', [TutorController::class, 'evaluar'])->whereNumber('practica')->name('evaluacion.guardar');
    Route::get('/practicantes/{practica}/informe', [TutorController::class, 'descargarInforme'])->whereNumber('practica')->name('informe.descargar');
});
