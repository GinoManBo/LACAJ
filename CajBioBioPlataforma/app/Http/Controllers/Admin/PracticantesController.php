<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abogado;
use App\Models\Consultorio;
use App\Models\Practica;
use App\Services\PracticasAdmin;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PracticantesController extends Controller
{
    private const RELACIONES = ['practicante.postulante', 'practicante.usuario', 'consultorio.region', 'abogado.usuario'];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $practicas = Practica::with(self::RELACIONES)
            ->when($q !== '', function ($query) use ($q) {
                $like = "%{$q}%";
                $query->where(function ($w) use ($like) {
                    $w->whereHas('practicante.postulante', fn ($p) => $p->where('nombre', 'like', $like)->orWhere('apellido', 'like', $like)
                        ->orWhereRaw("concat(nombre, ' ', apellido) like ?", [$like])->orWhere('universidad', 'like', $like))
                        ->orWhereHas('consultorio', fn ($c) => $c->where('nombre', 'like', $like))
                        ->orWhereHas('abogado.usuario', fn ($u) => $u->where('nombre', 'like', $like)->orWhere('apellido', 'like', $like));
                });
            })
            ->orderByDesc('fecha_inicio')->paginate(20)->withQueryString();

        return view('admin.practicantes.index', ['practicas' => $practicas, 'q' => $q]);
    }

    public function ver(Practica $practica)
    {
        $practica->load([...self::RELACIONES, 'traslados.consultorioOrigen', 'traslados.consultorioDestino']);

        return view('admin.practicantes.ficha', [
            'practica' => $practica,
            'abogados' => Abogado::with('usuario')->where('activo', true)->get()->sortBy('usuario.apellido')->values(),
            'sedes' => Consultorio::with('region')->where('activo', true)->orderBy('nombre')->get()
                ->each(fn ($c) => $c->setAttribute('libres', $c->libres())),
            'estados' => PracticasAdmin::ESTADOS_EDITABLES,
            'motivosCancelacion' => PracticasAdmin::MOTIVOS_CANCELACION,
        ]);
    }

    public function actualizar(Request $request, Practica $practica)
    {
        $usuarioId = $practica->practicante->usuario_id;
        $d = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'apellido' => ['required', 'string', 'max:80'],
            'universidad' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160', Rule::unique('usuarios', 'email')->ignore($usuarioId)],
            'telefono' => ['required', 'regex:/^\+?[0-9 ()\-]{8,20}$/'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_termino' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'abogado_id' => ['nullable', Rule::exists('abogados', 'id')->where('activo', true)],
            'discapacidad' => ['required', 'boolean'],
            'discapacidad_descripcion' => ['nullable', 'required_if:discapacidad,1', 'string', 'max:2000'],
            'estado' => ['required', Rule::in(PracticasAdmin::ESTADOS_EDITABLES)],
        ], $this->mensajes());

        PracticasAdmin::actualizar($practica, $d, $request->user()->id);

        return redirect()->route('admin.practicantes.ver', $practica)->with('ok', 'Cambios guardados correctamente.');
    }

    public function trasladar(Request $request, Practica $practica)
    {
        $d = $request->validate([
            'consultorio_id' => ['required', Rule::exists('consultorios', 'id')->where('activo', true)],
            'abogado_id' => ['nullable', Rule::exists('abogados', 'id')->where('activo', true)],
            'motivo' => ['nullable', 'string', 'max:1000'],
        ], $this->mensajes());

        $destino = Consultorio::findOrFail($d['consultorio_id']);
        PracticasAdmin::trasladar($practica, $destino, $d['abogado_id'] ?? null, $d['motivo'] ?? null, $request->user()->id);

        return redirect()->route('admin.practicantes.ver', $practica)->with('ok', "Practicante trasladado a {$destino->nombre}.");
    }

    public function cancelar(Request $request, Practica $practica)
    {
        $d = $request->validate([
            'motivo' => ['required', Rule::in(PracticasAdmin::MOTIVOS_CANCELACION)],
            'detalle' => ['nullable', 'required_if:motivo,otro', 'string', 'max:2000'],
        ], $this->mensajes() + ['detalle.required_if' => 'Detalle el motivo de la cancelación.']);

        PracticasAdmin::cancelar($practica, $d['motivo'], $d['detalle'] ?? null, $request->user()->id);

        return redirect()->route('admin.practicantes.ver', $practica)->with('ok', 'Práctica cancelada.');
    }

    private function mensajes(): array
    {
        return [
            'required' => 'Este campo es obligatorio.', 'required_if' => 'Este campo es obligatorio.',
            'email' => 'Ingrese un correo válido.', 'unique' => 'Ya existe un usuario con ese correo.',
            'regex' => 'Ingrese un teléfono válido.', 'max' => 'Es demasiado largo.',
            'exists' => 'Seleccione una opción válida.', 'in' => 'Seleccione una opción válida.',
            'after_or_equal' => 'La fecha de término no puede ser anterior al inicio.',
            'date' => 'Ingrese una fecha válida.',
        ];
    }
}
