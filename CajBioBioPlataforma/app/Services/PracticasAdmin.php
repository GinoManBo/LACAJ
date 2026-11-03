<?php

namespace App\Services;

use App\Models\Consultorio;
use App\Models\Practica;
use App\Models\PracticaTraslado;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PracticasAdmin
{
    public const MOTIVOS_CANCELACION = ['conducta', 'decision', 'error_registro', 'otro'];
    public const ESTADOS_EDITABLES = [
        'por_iniciar',
        'activa',
        'finalizada',
        'remitida_biobio',
        'nivelada_biobio',
        'rechazada_sancion',
        'cerrada_historial',
        'cancelada',
    ];

    public static function actualizar(Practica $p, array $d, int $actorId): Practica
    {
        $estadoNuevo = $d['estado'];
        if ($estadoNuevo === 'cancelada' && $p->estado !== 'cancelada') {
            throw ValidationException::withMessages(['estado' => 'Para cancelar use la acción "Cancelar práctica" e indique el motivo.']);
        }
        $reactiva = $p->estado === 'cancelada' && $estadoNuevo !== 'cancelada';
        if ($reactiva && $p->consultorio->libres() < 1) {
            throw ValidationException::withMessages(['estado' => 'No se puede reactivar: la sede no tiene cupos libres.']);
        }

        DB::transaction(function () use ($p, $d, $reactiva, $estadoNuevo, $actorId) {
            $pt = $p->practicante->postulante;
            $pt->update([
                'nombre' => $d['nombre'], 'apellido' => $d['apellido'], 'universidad' => $d['universidad'],
                'email' => mb_strtolower($d['email']), 'telefono' => $d['telefono'],
                'discapacidad' => (bool) $d['discapacidad'],
                'discapacidad_descripcion' => $d['discapacidad'] ? ($d['discapacidad_descripcion'] ?? null) : null,
            ]);
            if ($usuario = $p->practicante->usuario) {
                $usuario->update(['nombre' => $d['nombre'], 'apellido' => $d['apellido'], 'email' => mb_strtolower($d['email'])]);
            }

            $cambios = [
                'fecha_inicio' => $d['fecha_inicio'], 'fecha_termino' => $d['fecha_termino'],
                'abogado_id' => $d['abogado_id'] ?? null, 'estado' => $estadoNuevo,
            ];
            if ($reactiva) {
                $cambios += [
                    'cancelacion_motivo' => null, 'cancelacion_detalle' => null, 'cancelacion_fecha' => null,
                    'cancelacion_antes_de_iniciar' => null, 'cancelada_por' => null,
                ];
            }
            $p->update($cambios);
            Auditoria::registrar($reactiva ? 'practica.reactivar' : 'practica.editar', 'practica', $p->id, $d, $actorId);
        });

        return $p->refresh();
    }

    public static function trasladar(Practica $p, Consultorio $destino, ?int $abogadoDestinoId, ?string $motivo, int $actorId): Practica
    {
        if (! in_array($p->estado, ['por_iniciar', 'activa'], true)) {
            throw ValidationException::withMessages(['consultorio_id' => 'Solo se pueden trasladar prácticas por iniciar o activas.']);
        }
        if ($destino->id === $p->consultorio_id) {
            throw ValidationException::withMessages(['consultorio_id' => 'La práctica ya está en esa sede.']);
        }
        if (! $destino->activo || $destino->libres() < 1) {
            throw ValidationException::withMessages(['consultorio_id' => 'La sede de destino no tiene cupos libres.']);
        }

        DB::transaction(function () use ($p, $destino, $abogadoDestinoId, $motivo, $actorId) {
            PracticaTraslado::create([
                'practica_id' => $p->id, 'consultorio_origen_id' => $p->consultorio_id, 'consultorio_destino_id' => $destino->id,
                'abogado_origen_id' => $p->abogado_id, 'abogado_destino_id' => $abogadoDestinoId,
                'fecha' => now()->toDateString(), 'motivo' => $motivo, 'usuario_id' => $actorId,
            ]);
            $p->update(['consultorio_id' => $destino->id, 'abogado_id' => $abogadoDestinoId]);
            Auditoria::registrar('practica.trasladar', 'practica', $p->id, ['destino' => $destino->id], $actorId);
        });

        return $p->refresh();
    }

    public static function cancelar(Practica $p, string $motivo, ?string $detalle, int $actorId): Practica
    {
        if (! in_array($p->estado, ['por_iniciar', 'activa'], true)) {
            throw ValidationException::withMessages(['motivo' => 'Solo se pueden cancelar prácticas por iniciar o activas.']);
        }

        $p->update([
            'estado' => 'cancelada', 'cancelacion_motivo' => $motivo, 'cancelacion_detalle' => $detalle,
            'cancelacion_fecha' => now()->toDateString(),
            'cancelacion_antes_de_iniciar' => $p->estado === 'por_iniciar' || $p->fecha_inicio->isFuture(),
            'cancelada_por' => $actorId,
        ]);
        Auditoria::registrar('practica.cancelar', 'practica', $p->id, ['motivo' => $motivo], $actorId);

        return $p;
    }
}
