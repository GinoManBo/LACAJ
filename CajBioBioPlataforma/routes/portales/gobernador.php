<?php

use App\Http\Controllers\Gobernador\ColaController;
use App\Http\Controllers\Gobernador\FirmaController;
use Illuminate\Support\Facades\Route;

Route::prefix('gobernador')->name('gobernador.')->middleware('rol:gobernador')->group(function () {
    Route::get('/', [ColaController::class, 'index'])->name('inicio');
    Route::post('/autorizar/seleccion', [ColaController::class, 'autorizarSeleccion'])->name('autorizar.seleccion');
    Route::post('/autorizar/lote', [ColaController::class, 'lote'])->name('autorizar.lote');
    Route::post('/autorizar/todo', [ColaController::class, 'todo'])->name('autorizar.todo');
    Route::post('/autorizar/{practica}', [ColaController::class, 'autorizar'])->whereNumber('practica')->name('autorizar');

    Route::get('/firma', [FirmaController::class, 'ver'])->name('firma');
    Route::post('/firma', [FirmaController::class, 'subir'])->name('firma.subir');
    Route::get('/firma/imagen', [FirmaController::class, 'imagen'])->name('firma.imagen');
});
