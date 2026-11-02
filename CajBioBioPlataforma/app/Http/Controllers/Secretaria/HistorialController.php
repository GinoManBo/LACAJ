<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Practica;
use App\Models\Usuario;
use App\Services\Auditoria;
use App\Services\Notificaciones;
use App\Services\Practicas;
use App\Support\Notas;
use App\Support\Scope;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
    public const ESTADOS_HISTORIAL = [
        'finalizada', 'cancelada', 'remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial',
    ];

    public function index(Request $request)
    {
        $region = Scope::regionId($request->user());
        $q = trim((string) $request->query('q'));
        $anio = $request->query('anio');

        $base = fn () => Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $region))->whereIn('estado', self::ESTADOS_HISTORIAL);

        $practicas = $base()->with('practicante.postulante', 'consultorio', 'abogado.usuario')
            ->when($q !== '', fn ($query) => $query->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")->orWhere('apellido', 'like', "%$q%")))
            ->when(ctype_digit((string) $anio), fn ($query) => $query->whereYear('fecha_inicio', $anio))
            ->orderByDesc('fecha_termino')->get();

        return view('secretaria.historial.index', [
            'practicas' => $practicas,
            'q' => $q,
            'anio' => $anio,
            'anios' => $base()->selectRaw('YEAR(fecha_inicio) as anio')->distinct()->orderByDesc('anio')->pluck('anio'),
        ]);
    }

    public function ver(Request $request, int $id)
    {
        $p = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', Scope::regionId($request->user())))
            ->whereIn('estado', self::ESTADOS_HISTORIAL)
            ->with('practicante.postulante.comuna', 'consultorio.region', 'abogado.usuario', 'evaluacion', 'calificaciones', 'postulacion.documentos', 'canceladaPor', 'remitidaPor', 'niveladaPor')
            ->findOrFail($id);

        $evaluacion = $p->evaluacion;
        $criterios = [];
        if ($evaluacion) {
            foreach (Notas::CRITERIOS as $campo => $etiqueta) {
                $criterios[] = ['etiqueta' => $etiqueta, 'nota' => (float) $evaluacion->{$campo}, 'concepto' => Notas::concepto((float) $evaluacion->{$campo})];
            }
        }
        $docs = $p->postulacion->documentos;

        return view('secretaria.historial.ver', [
            'practica' => $p,
            'postulante' => $p->practicante->postulante,
            'estadistica' => Practicas::estadistica($p),
            'motivoCancelacion' => Practicas::MOTIVOS_CANCELACION[$p->cancelacion_motivo] ?? null,
            'evaluacion' => $evaluacion,
            'criterios' => $criterios,
            'conceptoFinal' => $p->nota_practica !== null ? Notas::concepto($p->nota_practica) : null,
            'documentosCedula' => $docs->whereIn('tipo', ['cedula_frontal', 'cedula_reverso'])->values(),
            'documentosUniversitarios' => $docs->whereNotIn('tipo', ['cedula_frontal', 'cedula_reverso'])->values(),
            'esSoloLectura' => in_array($p->estado, ['remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial'], true),
            'puedeRemitir' => $p->estado === 'finalizada' && $p->evaluacion !== null,
        ]);
    }

    /**
     * Cierra la competencia territorial regional y remite el paquete a Jurídica Central (Biobío - Concepción).
     */
    public function remitirBiobio(Request $request, int $id)
    {
        $region = Scope::regionId($request->user());
        $p = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $region))
            ->with('practicante.postulante', 'consultorio.region', 'evaluacion')
            ->findOrFail($id);

        if (! $p->evaluacion) {
            return back()->withErrors(['remitir' => 'No se puede remitir el expediente: la práctica aún no cuenta con evaluación del abogado tutor.']);
        }

        if (in_array($p->estado, ['remitida_biobio', 'nivelada_biobio', 'cerrada_historial'], true)) {
            return back()->with('aviso', 'El expediente ya fue remitido a Jurídica Central de Biobío.');
        }

        if ($p->estado === 'cancelada') {
            return back()->withErrors(['remitir' => 'No se puede remitir una práctica cancelada.']);
        }

        $p->update([
            'estado' => 'remitida_biobio',
            'remitida_at' => now(),
            'remitida_por' => $request->user()->id,
        ]);

        Auditoria::registrar('practica.remitir_biobio', 'practica', $p->id, [
            'region_id' => $region,
            'remitida_por' => $request->user()->id,
            'nota_practica' => $p->nota_practica,
            'concepto' => Notas::concepto($p->nota_practica),
        ], $request->user()->id);

        // Notificar a Jurídica Central Biobío
        Usuario::whereIn('rol', ['juridica_central'])->get()->each(function ($u) use ($p) {
            Notificaciones::crear(
                $u->id,
                'aviso',
                'Expediente remitido desde región',
                "La Región {$p->consultorio->region->nombre} remitió el expediente de {$p->practicante->postulante->nombre_completo} para revisión y cierre.",
                'practica',
                $p->id
            );
        });

        return back()->with('ok', 'Competencia regional cerrada y expediente remitido a Jurídica Central Biobío (Concepción). El expediente queda en modo solo lectura para la región de origen.');
    }
}
