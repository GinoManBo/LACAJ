<?php

use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\Secretaria\ConsultoriosController;
use App\Http\Controllers\Secretaria\HistorialController;
use App\Http\Controllers\Secretaria\PanelController;
use App\Http\Controllers\Secretaria\PostulacionesController;
use App\Http\Controllers\Secretaria\PracticasController;
use Illuminate\Support\Facades\Route;

Route::get('/documentos/{id}', [DocumentoController::class, 'ver'])->whereNumber('id')->name('documentos.ver');
Route::get('/documentos/{id}/descargar', [DocumentoController::class, 'descargar'])->whereNumber('id')->name('documentos.descargar');

Route::middleware('rol:secretaria')->prefix('secretaria')->group(function () {
    Route::get('/', PanelController::class)->name('secretaria.panel');

    Route::get('/postulaciones', [PostulacionesController::class, 'index'])->name('secretaria.postulaciones');
    Route::post('/postulaciones/importar', [PostulacionesController::class, 'importar'])->name('secretaria.postulaciones.importar');
    Route::get('/postulaciones/{id}', [PostulacionesController::class, 'ver'])->whereNumber('id')->name('secretaria.postulaciones.ver');
    Route::post('/postulaciones/{id}/aceptar', [PostulacionesController::class, 'aceptar'])->whereNumber('id')->name('secretaria.postulaciones.aceptar');
    Route::post('/postulaciones/{id}/rechazar', [PostulacionesController::class, 'rechazar'])->whereNumber('id')->name('secretaria.postulaciones.rechazar');
    Route::post('/postulaciones/{id}/notas', [PostulacionesController::class, 'notaCrear'])->whereNumber('id')->name('secretaria.postulaciones.nota.crear');
    Route::delete('/postulaciones/{id}/notas/{nota}', [PostulacionesController::class, 'notaEliminar'])->whereNumber(['id', 'nota'])->name('secretaria.postulaciones.nota.eliminar');

    Route::get('/practicas', [PracticasController::class, 'index'])->name('secretaria.practicas');
    Route::get('/practicas/exportar', [PracticasController::class, 'exportar'])->name('secretaria.practicas.exportar');
    Route::get('/practicas/{id}', [PracticasController::class, 'ver'])->whereNumber('id')->name('secretaria.practicas.ver');
    Route::get('/practicas/{id}/estadistica', [PracticasController::class, 'estadistica'])->whereNumber('id')->name('secretaria.practicas.estadistica');
    Route::post('/practicas/{id}/cancelar', [PracticasController::class, 'cancelar'])->whereNumber('id')->name('secretaria.practicas.cancelar');
    Route::get('/practicas/{id}/cancelacion.pdf', [PracticasController::class, 'documentoCancelacion'])->whereNumber('id')->name('secretaria.cancelacion.documento');

    Route::get('/historial', [HistorialController::class, 'index'])->name('secretaria.historial');
    Route::get('/historial/{id}', [HistorialController::class, 'ver'])->whereNumber('id')->name('secretaria.historial.ver');

    Route::get('/consultorios', [ConsultoriosController::class, 'index'])->name('secretaria.consultorios');
    Route::get('/consultorios/{id}', [ConsultoriosController::class, 'ver'])->whereNumber('id')->name('secretaria.consultorios.ver');
});
