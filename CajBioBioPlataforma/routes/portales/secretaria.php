<?php

use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\Secretaria\ConsultoriosController;
use App\Http\Controllers\Secretaria\HistorialController;
use App\Http\Controllers\Secretaria\JuridicaBiobioController;
use App\Http\Controllers\Secretaria\PanelController;
use App\Http\Controllers\Secretaria\PostulacionesController;
use App\Http\Controllers\Secretaria\PracticasController;
use Illuminate\Support\Facades\Route;

Route::get('/documentos/{id}', [DocumentoController::class, 'ver'])->whereNumber('id')->name('documentos.ver');
Route::get('/documentos/{id}/descargar', [DocumentoController::class, 'descargar'])->whereNumber('id')->name('documentos.descargar');

Route::middleware('rol:secretaria,juridica_central')->prefix('secretaria')->group(function () {
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
    Route::get('/practicas/{id}/carta-presentacion.pdf', [PracticasController::class, 'descargarCartaPresentacion'])->whereNumber('id')->name('secretaria.practicas.carta_presentacion');
    Route::post('/practicas/{id}/folio-carta', [PracticasController::class, 'guardarFolioCarta'])->whereNumber('id')->name('secretaria.practicas.guardar_folio_carta');
    Route::post('/practicas/{id}/carta-presentacion', [PracticasController::class, 'subirCartaPresentacion'])->whereNumber('id')->name('secretaria.practicas.subir_carta_firmada');
    Route::get('/practicas/{id}/carta-firmada.pdf', [PracticasController::class, 'verCartaPresentacionFirmada'])->whereNumber('id')->name('secretaria.practicas.ver_carta_firmada');
    Route::post('/practicas/{id}/segunda-instancia', [PracticasController::class, 'calificarSegundaInstancia'])->whereNumber('id')->name('secretaria.practicas.segunda_instancia');
    Route::post('/practicas/{id}/cancelar', [PracticasController::class, 'cancelar'])->whereNumber('id')->name('secretaria.practicas.cancelar');
    Route::get('/practicas/{id}/cancelacion.pdf', [PracticasController::class, 'documentoCancelacion'])->whereNumber('id')->name('secretaria.cancelacion.documento');

    Route::get('/historial', [HistorialController::class, 'index'])->name('secretaria.historial');
    Route::get('/historial/{id}', [HistorialController::class, 'ver'])->whereNumber('id')->name('secretaria.historial.ver');
    Route::get('/historial/{id}/certificado.pdf', [HistorialController::class, 'descargarCertificado'])->whereNumber('id')->name('secretaria.historial.certificado');
    Route::get('/historial/{id}/resolucion.pdf', [HistorialController::class, 'descargarResolucion'])->whereNumber('id')->name('secretaria.historial.resolucion');
    Route::get('/historial/{id}/expediente.zip', [HistorialController::class, 'descargarExpediente'])->whereNumber('id')->name('secretaria.historial.expediente');
    Route::post('/historial/{id}/actualizar-folios', [HistorialController::class, 'guardarFolios'])->whereNumber('id')->name('secretaria.historial.guardar_folios');
    Route::post('/historial/{id}/remitir-biobio', [HistorialController::class, 'remitirBiobio'])->whereNumber('id')->name('secretaria.historial.remitir_biobio');

    Route::get('/consultorios', [ConsultoriosController::class, 'index'])->name('secretaria.consultorios');
    Route::get('/consultorios/{id}', [ConsultoriosController::class, 'ver'])->whereNumber('id')->name('secretaria.consultorios.ver');

    // =========================================================================
    // SECRETARÍA JURÍDICA CENTRAL (Biobío - Concepción):
    // Recepción nacional, nivelación de notas de oficio y despacho final.
    // Pantallas separadas para Antes (Postulaciones) y Después (Expedientes).
    // =========================================================================
    Route::prefix('juridica')->name('secretaria.juridica.')->group(function () {
        Route::get('/postulaciones', [JuridicaBiobioController::class, 'postulaciones'])->name('postulaciones');
        Route::get('/postulaciones/exportar', [JuridicaBiobioController::class, 'exportarPostulaciones'])->name('postulaciones.exportar');
        Route::get('/exportar', [JuridicaBiobioController::class, 'exportarExpedientes'])->name('exportar');
        Route::get('/', [JuridicaBiobioController::class, 'index'])->name('index');
        Route::get('/{id}', [JuridicaBiobioController::class, 'ver'])->whereNumber('id')->name('ver');
        Route::get('/{id}/certificado.pdf', [JuridicaBiobioController::class, 'descargarCertificado'])->whereNumber('id')->name('certificado');
        Route::get('/{id}/resolucion.pdf', [JuridicaBiobioController::class, 'descargarResolucion'])->whereNumber('id')->name('resolucion');
        Route::get('/{id}/expediente.zip', [JuridicaBiobioController::class, 'descargarExpediente'])->whereNumber('id')->name('expediente');
        Route::post('/{id}/nivelar', [JuridicaBiobioController::class, 'nivelar'])->whereNumber('id')->name('nivelar');
        Route::post('/{id}/segunda-instancia', [JuridicaBiobioController::class, 'calificarSegundaInstancia'])->whereNumber('id')->name('segunda_instancia');
        Route::post('/{id}/rechazar-sancion', [JuridicaBiobioController::class, 'rechazarSancion'])->whereNumber('id')->name('rechazar_sancion');
        Route::post('/{id}/actualizar-folios', [JuridicaBiobioController::class, 'guardarFolios'])->whereNumber('id')->name('guardar_folios');
        Route::post('/{id}/despachar', [JuridicaBiobioController::class, 'despachar'])->whereNumber('id')->name('despachar');
    });
});
