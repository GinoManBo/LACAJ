<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CapacidadController;
use App\Http\Controllers\Admin\ConsultoriosController;
use App\Http\Controllers\Admin\EstadisticasController;
use App\Http\Controllers\Admin\InicioController;
use App\Http\Controllers\Admin\PracticantesController;
use App\Http\Controllers\Admin\UsuariosController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware('rol:administrador')->group(function () {
    Route::get('/', InicioController::class)->name('inicio');

    Route::prefix('practicantes')->name('practicantes.')->group(function () {
        Route::get('/', [PracticantesController::class, 'index'])->name('index');
        Route::get('/exportar', [PracticantesController::class, 'exportar'])->name('exportar');
        Route::get('/{practica}', [PracticantesController::class, 'ver'])->whereNumber('practica')->name('ver');
        Route::put('/{practica}', [PracticantesController::class, 'actualizar'])->whereNumber('practica')->name('actualizar');
        Route::post('/{practica}/trasladar', [PracticantesController::class, 'trasladar'])->whereNumber('practica')->name('trasladar');
        Route::post('/{practica}/cancelar', [PracticantesController::class, 'cancelar'])->whereNumber('practica')->name('cancelar');
    });

    // Alias /admin/postulantes
    Route::prefix('postulantes')->group(function () {
        Route::get('/', [PracticantesController::class, 'index']);
        Route::get('/{practica}', [PracticantesController::class, 'ver'])->whereNumber('practica');
        Route::put('/{practica}', [PracticantesController::class, 'actualizar'])->whereNumber('practica');
        Route::post('/{practica}/trasladar', [PracticantesController::class, 'trasladar'])->whereNumber('practica');
        Route::post('/{practica}/cancelar', [PracticantesController::class, 'cancelar'])->whereNumber('practica');
    });

    Route::prefix('capacidad')->name('capacidad.')->group(function () {
        Route::get('/', [CapacidadController::class, 'index'])->name('index');
        Route::get('/{consultorio}', [CapacidadController::class, 'ver'])->whereNumber('consultorio')->name('ver');
        Route::post('/{consultorio}', [CapacidadController::class, 'ajustar'])->whereNumber('consultorio')->name('ajustar');
    });

    Route::prefix('consultorios')->name('consultorios.')->group(function () {
        Route::get('/', [ConsultoriosController::class, 'index'])->name('index');
        Route::get('/nuevo', [ConsultoriosController::class, 'crear'])->name('crear');
        Route::post('/', [ConsultoriosController::class, 'guardar'])->name('guardar');
        Route::get('/{consultorio}', [ConsultoriosController::class, 'ver'])->whereNumber('consultorio')->name('ver');
        Route::get('/{consultorio}/editar', [ConsultoriosController::class, 'editar'])->whereNumber('consultorio')->name('editar');
        Route::put('/{consultorio}', [ConsultoriosController::class, 'actualizar'])->whereNumber('consultorio')->name('actualizar');
        Route::post('/{consultorio}/capacidad', [ConsultoriosController::class, 'ajustarCapacidad'])->whereNumber('consultorio')->name('capacidad');
    });

    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        Route::get('/', [UsuariosController::class, 'index'])->name('index');
        Route::get('/nuevo', [UsuariosController::class, 'crear'])->name('crear');
        Route::post('/', [UsuariosController::class, 'guardar'])->name('guardar');
        Route::get('/{usuario}/editar', [UsuariosController::class, 'editar'])->whereNumber('usuario')->name('editar');
        Route::put('/{usuario}', [UsuariosController::class, 'actualizar'])->whereNumber('usuario')->name('actualizar');
        Route::post('/{usuario}/activar', [UsuariosController::class, 'activar'])->whereNumber('usuario')->name('activar');
        Route::post('/{usuario}/desactivar', [UsuariosController::class, 'desactivar'])->whereNumber('usuario')->name('desactivar');
        Route::post('/{usuario}/resetear-password', [UsuariosController::class, 'resetearPassword'])->whereNumber('usuario')->name('resetear');
    });

    Route::prefix('estadisticas')->name('estadisticas.')->group(function () {
        Route::get('/', [EstadisticasController::class, 'index'])->name('index');
        Route::get('/exportar', [EstadisticasController::class, 'exportar'])->name('exportar');
    });

    Route::prefix('auditoria')->name('auditoria.')->group(function () {
        Route::get('/', [AuditoriaController::class, 'index'])->name('index');
        Route::get('/exportar', [AuditoriaController::class, 'exportar'])->name('exportar');
    });
});
