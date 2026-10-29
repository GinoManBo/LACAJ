<?php

namespace App\Services;

use App\Models\Abogado;
use App\Models\Consultorio;
use App\Models\Postulacion;
use App\Models\Practica;
use App\Models\Practicante;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Postulaciones
{
    /** @return array{tipo:'aceptada'|'derivada', practica:?Practica, passwordTemporal:?string} */
    public static function asignar(Postulacion $postulacion, Usuario $actor, int $consultorioId, ?int $abogadoId, ?string $nota): array
    {
        self::exigirPendiente($postulacion);

        if (! $postulacion->postulante->rut || ! $postulacion->fecha_inicio_solicitada) {
            throw ValidationException::withMessages(['consultorio_id' => 'Esta postulación fue importada desde Excel y aún le faltan datos (RUT y fecha de inicio). Complételos antes de aceptarla o derivarla.']);
        }

        $consultorio = Consultorio::where('activo', true)->findOrFail($consultorioId);

        return $consultorio->region_id === $postulacion->region_id
            ? self::aceptar($postulacion, $actor, $consultorio, $abogadoId, $nota)
            : self::derivar($postulacion, $actor, $consultorio, $nota);
    }

    private static function aceptar(Postulacion $p, Usuario $actor, Consultorio $consultorio, ?int $abogadoId, ?string $nota): array
    {
        if ($consultorio->libres() <= 0) {
            throw ValidationException::withMessages(['consultorio_id' => 'El consultorio seleccionado no tiene cupos disponibles.']);
        }

        $abogado = null;
        if ($abogadoId) {
            $abogado = Abogado::where('activo', true)
                ->whereHas('consultorios', fn ($q) => $q->where('consultorios.id', $consultorio->id))
                ->find($abogadoId);
            if (! $abogado) {
                throw ValidationException::withMessages(['abogado_id' => 'El abogado tutor no pertenece a ese consultorio.']);
            }
        }

        $postulante = $p->postulante;

        return DB::transaction(function () use ($p, $actor, $consultorio, $abogado, $nota, $postulante) {
            $passwordTemporal = null;
            $practicante = Practicante::firstOrCreate(['postulante_id' => $postulante->id]);

            if (! $practicante->usuario_id) {
                $perfil = Usuario::where('rut', $postulante->rut)->where('rol', 'postulante')->first();
                $email = mb_strtolower($postulante->email);
                $conflicto = Usuario::where(fn ($q) => $q->where('email', $email)->orWhere('rut', $postulante->rut))
                    ->when($perfil, fn ($q) => $q->where('id', '!=', $perfil->id))->exists();
                if ($conflicto) {
                    throw ValidationException::withMessages(['consultorio_id' => 'Ya existe una cuenta con el correo o RUT del postulante. Contacte al administrador.']);
                }
                $passwordTemporal = Str::password(10, symbols: false);
                $datosUsuario = [
                    'nombre' => $postulante->nombre, 'apellido' => $postulante->apellido,
                    'email' => $email, 'rut' => $postulante->rut,
                    'rol' => 'practicante', 'password' => $passwordTemporal,
                    'debe_cambiar_password' => true, 'activo' => true, 'creado_por' => $actor->id,
                ];
                $usuario = $perfil ? tap($perfil)->update($datosUsuario) : Usuario::create($datosUsuario);
                $practicante->update(['usuario_id' => $usuario->id]);
            }

            $inicio = $p->fecha_inicio_solicitada->copy();
            $practica = Practica::create([
                'postulacion_id' => $p->id, 'practicante_id' => $practicante->id,
                'consultorio_id' => $consultorio->id, 'abogado_id' => $abogado?->id,
                'fecha_inicio' => $inicio,
                'fecha_termino' => $inicio->copy()->addMonths((int) config('caj.duracion_practica_meses', 6)),
                'estado' => $inicio->lte(today()) ? 'activa' : 'por_iniciar',
            ]);

            if ($nota) {
                $p->notas()->create(['autor_usuario_id' => $actor->id, 'texto' => $nota]);
            }
            $p->update([
                'estado' => 'aceptada', 'revisada_por' => $actor->id, 'resuelta_at' => now(),
                'consultorio_asignado_id' => $consultorio->id,
            ]);

            Auditoria::registrar('postulacion.aceptar', 'postulacion', $p->id, ['consultorio_id' => $consultorio->id, 'practica_id' => $practica->id], $actor->id);

            if ($abogado) {
                Notificaciones::crear($abogado->usuario_id, 'informativo', 'Nuevo practicante asignado',
                    "{$postulante->nombre} {$postulante->apellido} fue asignado/a a {$consultorio->nombre}.", 'practica', $practica->id);
            }
            if ($practicante->usuario_id) {
                Notificaciones::crear($practicante->usuario_id, 'aviso', 'Postulación aceptada',
                    "Su práctica fue asignada a {$consultorio->nombre} desde el {$inicio->format('d-m-Y')}.", 'practica', $practica->id);
            }

            return ['tipo' => 'aceptada', 'practica' => $practica, 'passwordTemporal' => $passwordTemporal];
        });
    }

    private static function derivar(Postulacion $p, Usuario $actor, Consultorio $destino, ?string $nota): array
    {
        return DB::transaction(function () use ($p, $actor, $destino, $nota) {
            $origen = $p->region_id;
            $p->derivaciones()->create([
                'region_origen_id' => $origen, 'region_destino_id' => $destino->region_id,
                'usuario_id' => $actor->id, 'nota' => $nota,
            ]);
            if ($nota) {
                $p->notas()->create(['autor_usuario_id' => $actor->id, 'texto' => $nota]);
            }
            $p->update(['region_id' => $destino->region_id]);

            Auditoria::registrar('postulacion.derivar', 'postulacion', $p->id, ['origen' => $origen, 'destino' => $destino->region_id], $actor->id);

            Usuario::where('rol', 'secretaria')->where('region_id', $destino->region_id)->where('activo', true)->each(
                fn ($s) => Notificaciones::crear($s->id, 'aviso', 'Postulación derivada',
                    "{$p->postulante->nombre} {$p->postulante->apellido} fue derivada a su región.", 'postulacion', $p->id)
            );

            return ['tipo' => 'derivada', 'practica' => null, 'passwordTemporal' => null];
        });
    }

    public static function rechazar(Postulacion $p, Usuario $actor, ?string $motivo): void
    {
        self::exigirPendiente($p);

        DB::transaction(function () use ($p, $actor, $motivo) {
            $p->update([
                'estado' => 'rechazada', 'motivo_rechazo' => $motivo,
                'revisada_por' => $actor->id, 'resuelta_at' => now(),
            ]);
            Auditoria::registrar('postulacion.rechazar', 'postulacion', $p->id, ['motivo' => $motivo], $actor->id);
        });
    }

    private static function exigirPendiente(Postulacion $p): void
    {
        if ($p->estado !== 'pendiente') {
            throw ValidationException::withMessages(['estado' => 'Esta postulación ya fue resuelta.']);
        }
    }
}
