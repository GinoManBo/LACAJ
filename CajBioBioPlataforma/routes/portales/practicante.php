<?php

use App\Http\Controllers\Practicante\PracticanteController;
use Illuminate\Support\Facades\Route;

// Portales de práctica y seguimiento, accesibles con el rol unificado 'postulante' o histórico 'practicante'
Route::prefix('practicante')->name('practicante.')->middleware('rol:practicante,postulante')->group(function () {
    Route::get('/', [PracticanteController::class, 'inicio'])->name('inicio');
    Route::post('/informe', [PracticanteController::class, 'subirInforme'])->name('informe.subir');
    Route::get('/informe', [PracticanteController::class, 'descargarInforme'])->name('informe.descargar');
    Route::get('/documentos/certificado', [PracticanteController::class, 'certificado'])->name('certificado');
    Route::get('/documentos/resolucion', [PracticanteController::class, 'resolucion'])->name('resolucion');
});

// Alias de ruta accesible como /postulante
Route::prefix('postulante')->middleware('rol:practicante,postulante')->group(function () {
    Route::get('/', [PracticanteController::class, 'inicio']);
    Route::post('/informe', [PracticanteController::class, 'subirInforme']);
    Route::get('/informe', [PracticanteController::class, 'descargarInforme']);
    Route::get('/documentos/certificado', [PracticanteController::class, 'certificado']);
    Route::get('/documentos/resolucion', [PracticanteController::class, 'resolucion']);
});
