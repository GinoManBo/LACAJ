<?php

use App\Http\Controllers\Director\DirectorRegionalController;
use Illuminate\Support\Facades\Route;

Route::prefix('director')->name('director.')->middleware('rol:director_regional,jefe_ucoe,administrador,secretaria')->group(function () {
    Route::get('/', [DirectorRegionalController::class, 'index'])->name('inicio');
    Route::get('/evaluaciones', [DirectorRegionalController::class, 'index'])->name('evaluaciones');
    Route::get('/evaluaciones/{id}', [DirectorRegionalController::class, 'ver'])->whereNumber('id')->name('ver');
    Route::post('/evaluaciones/{id}/validar', [DirectorRegionalController::class, 'validar'])->whereNumber('id')->name('validar');
    Route::post('/evaluaciones/validar-lote', [DirectorRegionalController::class, 'validarLote'])->name('validar_lote');

    Route::get('/seguimiento', [DirectorRegionalController::class, 'seguimiento'])->name('seguimiento');
    Route::get('/seguimiento/exportar', [DirectorRegionalController::class, 'exportarSeguimiento'])->name('seguimiento.exportar');

    Route::get('/consultorios', [DirectorRegionalController::class, 'consultorios'])->name('consultorios');

    // Submódulo exclusivo UCOE (Biobío)
    Route::get('/colegiada', [DirectorRegionalController::class, 'colegiada'])->name('colegiada');
    Route::post('/colegiada/{id}/asignar', [DirectorRegionalController::class, 'asignarRevisor'])->whereNumber('id')->name('colegiada.asignar');
    Route::post('/colegiada/asignar-lote', [DirectorRegionalController::class, 'asignarRevisoresLote'])->name('colegiada.lote');
});
