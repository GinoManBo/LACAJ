<?php

use App\Http\Controllers\Practicante\PracticanteController;
use Illuminate\Support\Facades\Route;

Route::prefix('practicante')->name('practicante.')->middleware('rol:practicante')->group(function () {
    Route::get('/', [PracticanteController::class, 'inicio'])->name('inicio');
    Route::post('/informe', [PracticanteController::class, 'subirInforme'])->name('informe.subir');
    Route::get('/informe', [PracticanteController::class, 'descargarInforme'])->name('informe.descargar');
    Route::get('/documentos/certificado', [PracticanteController::class, 'certificado'])->name('certificado');
    Route::get('/documentos/resolucion', [PracticanteController::class, 'resolucion'])->name('resolucion');
});
