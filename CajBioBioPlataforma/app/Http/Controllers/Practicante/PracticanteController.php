<?php

namespace App\Http\Controllers\Practicante;

use App\Http\Controllers\Controller;
use App\Models\Practica;
use App\Services\Almacenamiento;
use App\Services\Auditoria;
use App\Services\CertificadosPractica;
use App\Services\InformesPractica;
use App\Services\PracticaVista;
use App\Support\Notas;
use App\Support\Scope;
use Illuminate\Http\Request;

class PracticanteController extends Controller
{
    private function practica(Request $request): ?Practica
    {
        return Practica::where('practicante_id', Scope::practicanteId($request->user()))
            ->with(['consultorio.comuna', 'abogado.usuario', 'evaluacion', 'calificaciones', 'practicante.postulante'])
            ->latest('id')->first();
    }

    public function inicio(Request $request)
    {
        $p = $this->practica($request);
        if (! $p) {
            return view('practicante.inicio', ['practica' => null]);
        }

        $porInstancia = $p->calificaciones->keyBy('instancia');
        $notas = $p->calificaciones->pluck('nota')->map(fn ($n) => (float) $n)->all();
        $bloqueo = CertificadosPractica::bloqueo($p);

        return view('practicante.inicio', [
            'practica' => $p,
            'postulante' => $p->practicante->postulante,
            'tutor' => $p->abogado?->usuario,
            'estadoMostrado' => PracticaVista::estadoMostrado($p),
            'calificaciones' => collect([1, 2, 3])->map(fn ($i) => [
                'instancia' => $i, 'obligatoria' => $i === 1, 'nota' => isset($porInstancia[$i]) ? (float) $porInstancia[$i]->nota : null,
            ]),
            'notaPractica' => $p->nota_practica,
            'notaPromedio' => Notas::promedioInstancias($notas),
            'conceptos' => $p->evaluacion
                ? collect(Notas::CRITERIOS)->map(fn ($etq, $k) => ['criterio' => $etq, 'concepto' => Notas::concepto((float) $p->evaluacion->$k)])->values()
                : null,
            'informe' => InformesPractica::vigente($p),
            'puedeSubirInforme' => in_array($p->estado, ['por_iniciar', 'activa'], true) && ! $p->evaluacion,
            'documentos' => [
                ['clave' => 'certificado', 'titulo' => 'Certificado Corte Suprema', 'detalle' => 'Certificado de aprobación para la Corte Suprema', 'ruta' => route('practicante.certificado'), 'bloqueo' => $bloqueo],
                ['clave' => 'resolucion', 'titulo' => 'Resolución', 'detalle' => 'Resolución que aprueba la práctica, con los conceptos de calificación', 'ruta' => route('practicante.resolucion'), 'bloqueo' => $bloqueo],
            ],
        ]);
    }

    public function subirInforme(Request $request)
    {
        $p = $this->practica($request);
        abort_unless($p, 404);

        $request->validate(
            ['informe' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:'.(config('caj.max_upload_mb') * 1024)]],
            ['informe.required' => 'Seleccione el archivo del informe.', 'informe.mimes' => 'El informe debe ser PDF o Word (.doc, .docx).', 'informe.max' => 'El archivo supera el tamaño máximo.'],
        );
        InformesPractica::subir($p, $request->file('informe'), $request->user());

        return back()->with('ok', 'Informe de práctica enviado. Tu abogado tutor ya puede evaluarte.');
    }

    public function descargarInforme(Request $request)
    {
        $p = $this->practica($request);
        $doc = $p ? InformesPractica::vigente($p) : null;
        abort_unless($doc && Almacenamiento::existe($doc->nombre_almacenado), 404);

        return response()->download(Almacenamiento::ruta($doc->nombre_almacenado), $doc->nombre_original, ['Content-Type' => $doc->mime]);
    }

    public function certificado(Request $request)
    {
        $p = $this->documentable($request);
        $pdf = CertificadosPractica::pdfCertificado($p, $request->user());
        Auditoria::registrar('practica.descarga_certificado', 'practica', $p->id);

        return $this->pdf($pdf, 'Certificado Corte Suprema-'.$this->nombreArchivo($p));
    }

    public function resolucion(Request $request)
    {
        $p = $this->documentable($request);
        $pdf = CertificadosPractica::pdfResolucion($p, $request->user());
        $c = $p->certificados()->where('tipo', CertificadosPractica::RESOLUCION)->first();
        Auditoria::registrar('practica.descarga_resolucion', 'practica', $p->id);

        return $this->pdf($pdf, sprintf('Resolución N°%04d-%d-%s', $c->numero_resolucion, $c->anio, $this->nombreArchivo($p)));
    }

    private function documentable(Request $request): Practica
    {
        $p = $this->practica($request);
        abort_unless($p, 404);
        if ($motivo = CertificadosPractica::bloqueo($p)) {
            abort(403, $motivo);
        }

        return $p;
    }

    private function nombreArchivo(Practica $p): string
    {
        $nombre = implode(' ', array_slice(explode(' ', $p->practicante->postulante->nombre_completo), 0, 2));

        return $nombre.'-'.now()->format('d-m-Y');
    }

    private function pdf(string $contenido, string $nombre)
    {
        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename*=UTF-8\'\''.rawurlencode($nombre.'.pdf'),
        ]);
    }
}
