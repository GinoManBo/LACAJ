<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abogado;
use App\Models\Consultorio;
use App\Models\Practica;
use App\Models\Region;
use App\Services\Practicas;
use App\Services\PracticasAdmin;
use App\Support\Notas;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class PracticantesController extends Controller
{
    private const RELACIONES = ['practicante.postulante', 'practicante.usuario', 'consultorio.region', 'abogado.usuario', 'evaluacion'];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $regionId = $request->query('region_id');
        $consultorioId = $request->query('consultorio_id');
        $estado = $request->query('estado');

        $base = Practica::with(self::RELACIONES);

        if ($q !== '') {
            $like = "%{$q}%";
            $base->where(function ($w) use ($like) {
                $w->whereHas('practicante.postulante', fn ($p) => $p->where('nombre', 'like', $like)->orWhere('apellido', 'like', $like)
                    ->orWhere('rut', 'like', $like)
                    ->orWhereRaw("concat(nombre, ' ', apellido) like ?", [$like])->orWhere('universidad', 'like', $like))
                    ->orWhereHas('consultorio', fn ($c) => $c->where('nombre', 'like', $like))
                    ->orWhereHas('abogado.usuario', fn ($u) => $u->where('nombre', 'like', $like)->orWhere('apellido', 'like', $like));
            });
        }

        if ($regionId && ctype_digit((string) $regionId)) {
            $base->whereHas('consultorio', fn ($c) => $c->where('region_id', (int) $regionId));
        }

        if ($consultorioId && ctype_digit((string) $consultorioId)) {
            $base->where('consultorio_id', (int) $consultorioId);
        }

        if ($estado && in_array($estado, ['por_iniciar', 'activa', 'finalizada', 'remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'cancelada'], true)) {
            $base->where('estado', $estado);
        }

        $practicas = $base->orderByDesc('fecha_inicio')->paginate(20)->withQueryString();

        return view('admin.practicantes.index', [
            'practicas' => $practicas,
            'q' => $q,
            'regiones' => Region::operativas()->orderBy('orden')->get(),
            'consultorios' => Consultorio::where('activo', true)->orderBy('nombre')->get(),
            'regionId' => $regionId,
            'consultorioId' => $consultorioId,
            'estado' => $estado,
        ]);
    }

    /**
     * Exportación de prácticas y postulantes a archivo Excel (.xlsx).
     */
    public function exportar(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $regionId = $request->query('region_id');
        $consultorioId = $request->query('consultorio_id');
        $estado = $request->query('estado');

        $base = Practica::with(self::RELACIONES);

        if ($q !== '') {
            $like = "%{$q}%";
            $base->where(function ($w) use ($like) {
                $w->whereHas('practicante.postulante', fn ($p) => $p->where('nombre', 'like', $like)->orWhere('apellido', 'like', $like)
                    ->orWhere('rut', 'like', $like)
                    ->orWhereRaw("concat(nombre, ' ', apellido) like ?", [$like])->orWhere('universidad', 'like', $like))
                    ->orWhereHas('consultorio', fn ($c) => $c->where('nombre', 'like', $like))
                    ->orWhereHas('abogado.usuario', fn ($u) => $u->where('nombre', 'like', $like)->orWhere('apellido', 'like', $like));
            });
        }

        if ($regionId && ctype_digit((string) $regionId)) {
            $base->whereHas('consultorio', fn ($c) => $c->where('region_id', (int) $regionId));
        }

        if ($consultorioId && ctype_digit((string) $consultorioId)) {
            $base->where('consultorio_id', (int) $consultorioId);
        }

        if ($estado && in_array($estado, ['por_iniciar', 'activa', 'finalizada', 'remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'cancelada'], true)) {
            $base->where('estado', $estado);
        }

        $practicas = $base->orderByDesc('fecha_inicio')->get();

        $ruta = tempnam(sys_get_temp_dir(), 'admin_prac_').'.xlsx';
        $w = new Writer();
        $w->openToFile($ruta);

        $w->addRow(Row::fromValues([
            'ID Práctica',
            'Región',
            'Consultorio / Sede',
            'Postulante',
            'RUT',
            'Correo',
            'Teléfono',
            'Universidad',
            'Abogado Tutor',
            'Fecha Inicio',
            'Fecha Término',
            'Estado',
            'Calificación (Palabras)',
            'Visada por Dirección',
            'Cerrada con FirmaGob',
            'Motivo Cancelación',
        ]));

        foreach ($practicas as $p) {
            $po = $p->practicante?->postulante;
            $reg = $p->consultorio?->region;
            $concepto = $p->nota_practica !== null ? Notas::concepto($p->nota_practica) : 'Sin calificar';

            $w->addRow(Row::fromValues([
                $p->id,
                $reg?->nombre ?? 'Sin región',
                $p->consultorio?->nombre ?? '—',
                $po ? $po->nombre.' '.$po->apellido : '—',
                $po?->rut ?? '—',
                $po?->email ?? '—',
                $po?->telefono ?? '—',
                $po?->universidad ?? '—',
                $p->abogado?->usuario?->nombre_completo ?? 'Sin asignar',
                $p->fecha_inicio ? $p->fecha_inicio->format('d-m-Y') : '—',
                $p->fecha_termino ? $p->fecha_termino->format('d-m-Y') : '—',
                Practicas::estadoVisible($p),
                $concepto,
                $p->validada_director_at ? 'Sí' : 'No',
                $p->firmado_director_general_at ? 'Sí' : 'No',
                $p->cancelacion_motivo ?? '—',
            ]));
        }

        $w->close();

        return response()->download($ruta, 'practicas_nacional_caj_'.now()->format('Ymd_His').'.xlsx')->deleteFileAfterSend();
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

        return redirect()->route('admin.practicantes.ver', $practica)->with('ok', "Postulante trasladado a {$destino->nombre}.");
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
