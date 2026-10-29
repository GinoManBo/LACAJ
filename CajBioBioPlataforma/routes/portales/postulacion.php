<?php

use App\Http\Controllers\Postulacion\PostulacionController;
use Illuminate\Support\Facades\Route;

Route::prefix('postular')->name('postulacion.')->group(function () {
    Route::get('/', [PostulacionController::class, 'inicio'])->name('inicio');
    Route::get('/paso/{n}', [PostulacionController::class, 'paso'])->whereNumber('n')->name('paso');
    Route::post('/paso/{n}', [PostulacionController::class, 'guardar'])->whereNumber('n')->middleware('throttle:60,1')->name('guardar');
    Route::post('/enviar', [PostulacionController::class, 'enviar'])->middleware('throttle:10,1')->name('enviar');
    Route::get('/enviada', [PostulacionController::class, 'enviada'])->name('enviada');
});
