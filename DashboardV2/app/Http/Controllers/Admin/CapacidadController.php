<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultorio;
use App\Services\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CapacidadController extends Controller
{
    public function index()
    {
        $sedes = Consultorio::with('region')->where('activo', true)->orderBy('nombre')->get()
            ->each(function ($c) {
                $c->setAttribute('ocupados_n', $c->ocupados());
                $c->setAttribute('libres_n', max(0, $c->capacidad - $c->ocupados_n));
            });

        return view('admin.capacidad.index', ['sedes' => $sedes]);
    }

    public function ver(Consultorio $consultorio)
    {
        $consultorio->load('region', 'comuna');
        $asignados = $consultorio->practicas()->whereIn('estado', ['por_iniciar', 'activa'])
            ->with(['practicante.postulante', 'abogado.usuario'])->orderBy('fecha_termino')->get();

        return view('admin.capacidad.detalle', [
            'sede' => $consultorio, 'ocupados' => $asignados->count(),
            'libres' => max(0, $consultorio->capacidad - $asignados->count()), 'asignados' => $asignados,
        ]);
    }

    public function ajustar(Request $request, Consultorio $consultorio)
    {
        $d = $request->validate([
            'accion' => ['nullable', 'in:mas,menos'],
            'capacidad' => ['nullable', 'integer', 'min:0', 'max:500'],
        ], ['accion.in' => 'Acción no válida.', 'capacidad.integer' => 'Ingrese un número entero.', 'capacidad.max' => 'La capacidad máxima es 500.']);

        $nueva = match (true) {
            ($d['accion'] ?? null) === 'mas' => $consultorio->capacidad + 1,
            ($d['accion'] ?? null) === 'menos' => $consultorio->capacidad - 1,
            isset($d['capacidad']) => (int) $d['capacidad'],
            default => throw ValidationException::withMessages(['capacidad' => 'Indique la nueva capacidad.']),
        };

        $ocupados = $consultorio->ocupados();
        if ($nueva < $ocupados) {
            throw ValidationException::withMessages([
                'capacidad' => "La capacidad no puede ser menor que los practicantes asignados ({$ocupados}). Traslade primero a los practicantes.",
            ]);
        }
        if ($nueva < 0 || $nueva > 500) {
            throw ValidationException::withMessages(['capacidad' => 'Capacidad fuera de rango.']);
        }

        $anterior = $consultorio->capacidad;
        $consultorio->update(['capacidad' => $nueva]);
        Auditoria::registrar('consultorio.capacidad', 'consultorio', $consultorio->id, ['de' => $anterior, 'a' => $nueva]);

        $dif = $nueva - $anterior;
        $msg = $dif === 0 ? 'Sin cambios en la capacidad.' : ($dif > 0 ? "Se agregaron {$dif} cupo(s)." : 'Se retiraron '.abs($dif).' cupo(s).');

        return back()->with('ok', $msg);
    }
}
