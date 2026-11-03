<?php

namespace App\Services;

use App\Models\Practica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class GobernadorAutorizaciones
{
    public static function pendientes(): Builder
    {
        return Practica::query()
            ->where('documentos_autorizados', false)
            ->whereIn('estado', ['remitida_biobio', 'nivelada_biobio', 'cerrada_historial'])
            ->whereNotNull('validada_director_at')
            ->whereNotNull('firmado_director_general_at')
            ->whereHas('evaluacion', fn ($q) => $q->where('aprobado', true));
    }

    public static function listar()
    {
        return self::pendientes()
            ->with(['practicante.postulante', 'practicante.usuario', 'consultorio.region', 'abogado.usuario', 'evaluacion'])
            ->orderBy('created_at')->get();
    }

    public static function autorizar(int $practicaId, int $gobernadorId): Practica
    {
        return DB::transaction(function () use ($practicaId, $gobernadorId) {
            $p = Practica::with(['evaluacion', 'practicante'])->lockForUpdate()->find($practicaId);
            if (! $p) {
                throw ValidationException::withMessages(['practica' => 'Práctica no encontrada.']);
            }
            if (! $p->evaluacion?->aprobado) {
                throw ValidationException::withMessages(['practica' => 'Esta práctica aún no tiene una evaluación aprobada por el abogado tutor.']);
            }
            if (! $p->validada_director_at) {
                throw ValidationException::withMessages(['practica' => 'Esta práctica no cuenta con la validación de la Dirección Regional.']);
            }
            if (! in_array($p->estado, ['remitida_biobio', 'nivelada_biobio', 'cerrada_historial'], true)) {
                throw ValidationException::withMessages(['practica' => 'Esta práctica aún no ha sido revisada por la Secretaría Jurídica Central.']);
            }
            if (! $p->firmado_director_general_at) {
                throw ValidationException::withMessages(['practica' => 'Esta práctica debe estar firmada previamente con FirmaGob por el Director General.']);
            }
            if ($p->documentos_autorizados) {
                throw ValidationException::withMessages(['practica' => 'Los documentos de esta práctica ya fueron autorizados.']);
            }

            $p->update([
                'documentos_autorizados' => true, 'documentos_autorizados_por' => $gobernadorId,
                'documentos_autorizados_at' => now(),
            ]);
            Auditoria::registrar('gobernador.autorizar_documentos', 'practica', $p->id, null, $gobernadorId);

            if ($p->practicante->usuario_id) {
                Notificaciones::crear($p->practicante->usuario_id, 'aviso', 'Documentos autorizados',
                    'El Gobernador Regional autorizó tu certificado y resolución. Ya están disponibles para descargar.',
                    'practica', $p->id);
            }

            return $p;
        });
    }

    /** @param int[] $ids @return array{autorizadas:int, solicitadas:int} */
    public static function autorizarVarias(array $ids, int $gobernadorId): array
    {
        $ok = 0;
        foreach ($ids as $id) {
            try {
                self::autorizar($id, $gobernadorId);
                $ok++;
            } catch (Throwable) {
                // una práctica que falla no detiene el lote
            }
        }

        return ['autorizadas' => $ok, 'solicitadas' => count($ids)];
    }

    public static function autorizarLote(int $cantidad, int $gobernadorId): array
    {
        return self::autorizarVarias(self::pendientes()->orderBy('created_at')->limit($cantidad)->pluck('id')->all(), $gobernadorId);
    }

    public static function autorizarTodo(int $gobernadorId): array
    {
        return self::autorizarVarias(self::pendientes()->pluck('id')->all(), $gobernadorId);
    }
}
