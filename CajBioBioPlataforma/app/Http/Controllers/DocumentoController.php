<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Usuario;
use App\Services\Almacenamiento;
use Illuminate\Http\Request;

class DocumentoController extends Controller
{
    public function ver(Request $request, int $id)
    {
        return $this->servir($request, $id, 'inline');
    }

    public function descargar(Request $request, int $id)
    {
        return $this->servir($request, $id, 'attachment');
    }

    private function servir(Request $request, int $id, string $disposicion)
    {
        $doc = Documento::with('postulacion.postulante.practicante', 'postulacion.practica', 'practica.consultorio', 'practica.practicante')->findOrFail($id);
        abort_unless($this->puedeVer($request->user(), $doc), 404);
        abort_unless(Almacenamiento::existe($doc->nombre_almacenado), 404);

        $ext = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$doc->mime] ?? null;
        $nombre = $doc->nombre_original ?: ($doc->tipo.($ext ? ".$ext" : ''));

        return response()->file(Almacenamiento::ruta($doc->nombre_almacenado), [
            'Content-Type' => $doc->mime,
            'Content-Disposition' => $disposicion.'; filename="'.addcslashes($nombre, '"\\').'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function puedeVer(Usuario $u, Documento $d): bool
    {
        $practica = $d->practica ?? $d->postulacion?->practica;
        $regionDoc = $d->postulacion?->region_id ?? $practica?->consultorio?->region_id;

        return match ($u->rol) {
            'administrador', 'director_general', 'juridica_central' => true,
            'secretaria', 'director_regional', 'jefe_ucoe' => $u->region_id !== null && $regionDoc === $u->region_id,
            'abogado' => $u->abogado && $practica && $practica->abogado_id === $u->abogado->id,
            'practicante', 'postulante' => $this->esDelPracticanteOPostulante($u, $d, $practica),
            default => false,
        };
    }

    private function esDelPracticanteOPostulante(Usuario $u, Documento $d, $practica): bool
    {
        if ($this->esDelPracticante($u, $d, $practica)) {
            return true;
        }

        // Si es un postulante en etapa de revisión, puede ver sus propios documentos adjuntos
        return $d->postulacion && $d->postulacion->postulante?->rut === $u->rut;
    }

    private function esDelPracticante(Usuario $u, Documento $d, $practica): bool
    {
        $practicanteId = $u->practicante?->id;
        if (! $practicanteId) {
            return false;
        }
        $suyo = $practica
            ? $practica->practicante_id === $practicanteId
            : $d->postulacion?->postulante?->practicante?->id === $practicanteId;
        if (! $suyo) {
            return false;
        }

        if (in_array($d->tipo, ['certificado_corte_suprema', 'resolucion'], true)) {
            return $practica && $practica->estado === 'finalizada' && $practica->documentos_autorizados;
        }

        return true;
    }
}
