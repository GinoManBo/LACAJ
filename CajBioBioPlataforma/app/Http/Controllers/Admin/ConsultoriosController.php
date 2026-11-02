<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comuna;
use App\Models\Consultorio;
use App\Models\Region;
use App\Services\Auditoria;
use App\Services\Practicas;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConsultoriosController extends Controller
{
    public const TIPOS_UNIDAD = [
        'CJ' => 'Consultorio Jurídico (CJ)',
        'OJA' => 'Oficina Jurídico Asistencial (OJA)',
        'CAVI' => 'Centro de Atención a Víctimas (CAVI)',
        'CMED' => 'Centro de Mediación (CMED)',
        'PMA' => 'Programa Mi Abogado (PMA)',
        'NAD' => 'Niñez y Adolescencia Defendida (NAD)',
        'ODL' => 'Oficina de Defensa Laboral (ODL)',
        'ODAM' => 'Oficina de Defensa Adulto Mayor (ODAM)',
        '2DA INSTANCIA' => 'Oficina de Segunda Instancia (Corte)',
        'MOVIL' => 'Unidad Móvil',
        'DG/DR' => 'Dirección Regional / General',
    ];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $regionId = $request->query('region_id');
        $tipo = $request->query('tipo_unidad');
        $activo = $request->query('activo');

        $query = Consultorio::with(['region', 'comuna']);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'like', "%{$q}%")
                    ->orWhere('direccion', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($regionId && ctype_digit((string) $regionId)) {
            $query->where('region_id', (int) $regionId);
        }

        if ($tipo && $tipo !== 'todos') {
            $query->where('tipo_unidad', $tipo);
        }

        if ($activo !== null && $activo !== '') {
            $query->where('activo', (bool) $activo);
        }

        $consultorios = $query->orderBy('nombre')->get()->each(function ($c) {
            $c->setAttribute('ocupados_n', $c->ocupados());
            $c->setAttribute('libres_n', max(0, $c->capacidad - $c->ocupados_n));
            $c->setAttribute('porcentaje', $c->capacidad > 0 ? (int) round($c->ocupados_n / $c->capacidad * 100) : 0);
            $c->setAttribute('disponibilidad', Practicas::nivelDisponibilidad($c->capacidad, $c->libres_n));
        });

        $totales = [
            'sedes' => Consultorio::count(),
            'activas' => Consultorio::where('activo', true)->count(),
            'capacidad' => Consultorio::where('activo', true)->sum('capacidad'),
        ];

        return view('admin.consultorios.index', [
            'consultorios' => $consultorios,
            'regiones' => Region::operativas()->orderBy('orden')->get(),
            'tipos' => self::TIPOS_UNIDAD,
            'totales' => $totales,
            'filtros' => [
                'q' => $q,
                'region_id' => $regionId,
                'tipo_unidad' => $tipo,
                'activo' => $activo,
            ],
        ]);
    }

    public function crear()
    {
        $consultorio = new Consultorio([
            'capacidad' => 3,
            'activo' => true,
            'acepta_postulaciones' => true,
        ]);

        return view('admin.consultorios.form', [
            'consultorio' => $consultorio,
            'regiones' => Region::operativas()->orderBy('orden')->get(),
            'comunas' => Comuna::orderBy('nombre')->get(),
            'tipos' => self::TIPOS_UNIDAD,
            'esEdicion' => false,
        ]);
    }

    public function guardar(Request $request)
    {
        $datos = $this->validar($request);

        $consultorio = Consultorio::create($datos);

        Auditoria::registrar('consultorio.creado', 'consultorio', $consultorio->id, [
            'nombre' => $consultorio->nombre,
            'comuna_id' => $consultorio->comuna_id,
            'capacidad' => $consultorio->capacidad,
        ], $request->user()->id);

        return redirect()->route('admin.consultorios.ver', $consultorio)
            ->with('ok', "Sede/Consultorio «{$consultorio->nombre}» creado exitosamente.");
    }

    public function ver(Consultorio $consultorio)
    {
        $consultorio->load(['region', 'comuna', 'abogados.usuario']);

        $ocupados = $consultorio->ocupados();
        $libres = max(0, $consultorio->capacidad - $ocupados);
        $porcentaje = $consultorio->capacidad > 0 ? (int) round($ocupados / $consultorio->capacidad * 100) : 0;
        $disp = Practicas::nivelDisponibilidad($consultorio->capacidad, $libres);

        $asignados = $consultorio->practicas()
            ->whereIn('estado', ['por_iniciar', 'activa'])
            ->with(['practicante.postulante', 'abogado.usuario'])
            ->orderBy('fecha_termino')
            ->get()
            ->each(fn ($p) => $p->estado_visible = Practicas::estadoVisible($p));

        return view('admin.consultorios.ver', [
            'sede' => $consultorio,
            'ocupados' => $ocupados,
            'libres' => $libres,
            'porcentaje' => $porcentaje,
            'disponibilidad' => $disp,
            'asignados' => $asignados,
        ]);
    }

    public function editar(Consultorio $consultorio)
    {
        $consultorio->load(['region', 'comuna']);

        return view('admin.consultorios.form', [
            'consultorio' => $consultorio,
            'regiones' => Region::operativas()->orderBy('orden')->get(),
            'comunas' => Comuna::orderBy('nombre')->get(),
            'tipos' => self::TIPOS_UNIDAD,
            'esEdicion' => true,
        ]);
    }

    public function actualizar(Request $request, Consultorio $consultorio)
    {
        $datos = $this->validar($request, $consultorio);

        $ocupados = $consultorio->ocupados();
        if ($datos['capacidad'] < $ocupados) {
            throw ValidationException::withMessages([
                'capacidad' => "La capacidad no puede ser menor que los postulantes actualmente asignados ({$ocupados}). Traslade postulantes antes de reducir la capacidad a ese nivel.",
            ]);
        }

        $anterior = $consultorio->only(['nombre', 'tipo_unidad', 'direccion', 'telefono', 'horario_atencion', 'email', 'capacidad', 'activo', 'acepta_postulaciones']);
        $consultorio->update($datos);

        Auditoria::registrar('consultorio.actualizado', 'consultorio', $consultorio->id, [
            'anterior' => $anterior,
            'nuevo' => $consultorio->only(array_keys($anterior)),
        ], $request->user()->id);

        return redirect()->route('admin.consultorios.ver', $consultorio)
            ->with('ok', "Información del consultorio «{$consultorio->nombre}» actualizada exitosamente.");
    }

    public function ajustarCapacidad(Request $request, Consultorio $consultorio)
    {
        $d = $request->validate([
            'accion' => ['nullable', 'in:mas,menos'],
            'capacidad' => ['nullable', 'integer', 'min:0', 'max:500'],
        ], [
            'accion.in' => 'Acción no válida.',
            'capacidad.integer' => 'Ingrese un número entero.',
            'capacidad.max' => 'La capacidad máxima es 500.',
        ]);

        $nueva = match (true) {
            ($d['accion'] ?? null) === 'mas' => $consultorio->capacidad + 1,
            ($d['accion'] ?? null) === 'menos' => $consultorio->capacidad - 1,
            isset($d['capacidad']) => (int) $d['capacidad'],
            default => throw ValidationException::withMessages(['capacidad' => 'Indique la nueva capacidad.']),
        };

        $ocupados = $consultorio->ocupados();
        if ($nueva < $ocupados) {
            throw ValidationException::withMessages([
                'capacidad' => "La capacidad no puede ser menor que los postulantes asignados ({$ocupados}).",
            ]);
        }
        if ($nueva < 0 || $nueva > 500) {
            throw ValidationException::withMessages(['capacidad' => 'Capacidad fuera de rango.']);
        }

        $anterior = $consultorio->capacidad;
        $consultorio->update(['capacidad' => $nueva]);

        Auditoria::registrar('consultorio.capacidad', 'consultorio', $consultorio->id, [
            'de' => $anterior,
            'a' => $nueva,
        ], $request->user()->id);

        $dif = $nueva - $anterior;
        $msg = $dif === 0 ? 'Sin cambios en la capacidad.' : ($dif > 0 ? "Se agregaron {$dif} cupo(s)." : 'Se retiraron '.abs($dif).' cupo(s).');

        return back()->with('ok', $msg);
    }

    private function validar(Request $request, ?Consultorio $consultorio = null): array
    {
        $reglaNombre = ['required', 'string', 'max:140'];

        $datos = $request->validate([
            'nombre' => $reglaNombre,
            'tipo_unidad' => ['nullable', 'string', 'max:50'],
            'region_id' => ['required', 'integer', 'exists:regiones,id'],
            'comuna_id' => ['required', 'integer', 'exists:comunas,id'],
            'direccion' => ['required', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'horario_atencion' => ['nullable', 'string', 'max:2000'],
            'email' => ['nullable', 'string', 'max:150'],
            'capacidad' => ['required', 'integer', 'min:0', 'max:500'],
            'activo' => ['nullable', 'boolean'],
            'acepta_postulaciones' => ['nullable', 'boolean'],
        ], [
            'nombre.required' => 'El nombre de la unidad es obligatorio.',
            'region_id.required' => 'Seleccione una región.',
            'comuna_id.required' => 'Seleccione una comuna.',
            'direccion.required' => 'La dirección es obligatoria.',
            'capacidad.required' => 'La capacidad es obligatoria.',
            'capacidad.min' => 'La capacidad no puede ser negativa.',
        ]);

        $datos['activo'] = $request->boolean('activo');
        $datos['acepta_postulaciones'] = $request->boolean('acepta_postulaciones');

        return $datos;
    }
}
