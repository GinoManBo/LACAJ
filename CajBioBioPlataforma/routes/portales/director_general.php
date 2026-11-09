<?php

use App\Http\Controllers\Director\DirectorGeneralController;
use Illuminate\Support\Facades\Route;

Route::prefix('director-general')->name('director_general.')->middleware('rol:director_general,administrador')->group(function () {
    Route::get('/', [DirectorGeneralController::class, 'index'])->name('inicio');
    Route::get('/expedientes', [DirectorGeneralController::class, 'index'])->name('index');
    Route::get('/expedientes/{id}', [DirectorGeneralController::class, 'ver'])->whereNumber('id')->name('ver');

    // Descarga de documentos para firma
    Route::get('/expedientes/{id}/certificado', [DirectorGeneralController::class, 'descargarCertificado'])->whereNumber('id')->name('certificado');
    Route::get('/expedientes/{id}/resolucion', [DirectorGeneralController::class, 'descargarResolucion'])->whereNumber('id')->name('resolucion');
    Route::post('/expedientes/{id}/actualizar-folios', [DirectorGeneralController::class, 'guardarFolios'])->whereNumber('id')->name('guardar_folios');
    Route::post('/expedientes/descargar-lote', [DirectorGeneralController::class, 'descargarLote'])->name('descargar_lote');

    // Módulo de integración con FirmaGob (Gatillador de Cierre)
    Route::post('/expedientes/{id}/firmagob', [DirectorGeneralController::class, 'subirFirmaGob'])->whereNumber('id')->name('firmagob');

    // Supervisión Nacional
    Route::get('/seguimiento', [DirectorGeneralController::class, 'seguimiento'])->name('seguimiento');
    Route::get('/seguimiento/exportar', [DirectorGeneralController::class, 'exportarSeguimiento'])->name('seguimiento.exportar');

    // Catálogo Nacional de Sedes
    Route::get('/consultorios', [DirectorGeneralController::class, 'consultorios'])->name('consultorios');
});
