<?php

namespace App\Services;

use App\Models\Abogado;
use App\Models\Usuario;
use App\Support\Rut;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UsuariosAdmin
{
    public static function passwordTemporal(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < 12; $i++) {
            $out .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        return $out;
    }

    /** @return array{usuario: Usuario, passwordTemporal: string} */
    public static function crear(array $d, int $creadoPor): array
    {
        $temporal = self::passwordTemporal();

        $usuario = DB::transaction(function () use ($d, $creadoPor, $temporal) {
            $rolesConRegion = ['secretaria', 'director_regional', 'jefe_ucoe'];
            $esSegunda = $d['rol'] === 'abogado' && ! empty($d['es_segunda_instancia']);
            $regionId = in_array($d['rol'], $rolesConRegion, true) || $esSegunda ? ($d['region_id'] ?? null) : null;

            $u = Usuario::create([
                'nombre' => $d['nombre'], 'apellido' => $d['apellido'],
                'email' => Str::lower($d['email']),
                'rut' => ! empty($d['rut']) ? Rut::normalizar($d['rut']) : null,
                'rol' => $d['rol'],
                'region_id' => $regionId,
                'password' => $temporal, 'debe_cambiar_password' => true, 'activo' => true,
                'creado_por' => $creadoPor,
            ]);
            if ($d['rol'] === 'abogado') {
                $a = Abogado::create([
                    'usuario_id' => $u->id,
                    'area' => $d['area'] ?? null,
                    'es_segunda_instancia' => $esSegunda,
                    'region_id' => $esSegunda ? $regionId : null,
                ]);
                $a->consultorios()->sync($d['consultorios'] ?? []);
            }
            Auditoria::registrar('usuario.crear', 'usuario', $u->id, ['rol' => $d['rol'], 'email' => $u->email], $creadoPor);

            return $u;
        });

        return ['usuario' => $usuario, 'passwordTemporal' => $temporal];
    }

    public static function editar(Usuario $u, array $d, int $editorId): Usuario
    {
        if ($u->id === $editorId && $d['rol'] !== $u->rol) {
            throw ValidationException::withMessages(['rol' => 'No puede cambiar su propio rol.']);
        }

        DB::transaction(function () use ($u, $d, $editorId) {
            $rolesConRegion = ['secretaria', 'director_regional', 'jefe_ucoe'];
            $esSegunda = $d['rol'] === 'abogado' && ! empty($d['es_segunda_instancia']);
            $regionId = in_array($d['rol'], $rolesConRegion, true) || $esSegunda ? ($d['region_id'] ?? null) : null;

            $u->update([
                'nombre' => $d['nombre'], 'apellido' => $d['apellido'], 'email' => Str::lower($d['email']),
                'rut' => ! empty($d['rut']) ? Rut::normalizar($d['rut']) : null,
                'rol' => $d['rol'],
                'region_id' => $regionId,
            ]);

            if ($d['rol'] === 'abogado') {
                $a = Abogado::updateOrCreate(
                    ['usuario_id' => $u->id],
                    [
                        'area' => $d['area'] ?? null,
                        'es_segunda_instancia' => $esSegunda,
                        'region_id' => $esSegunda ? $regionId : null,
                        'activo' => true,
                    ]
                );
                $a->consultorios()->sync($d['consultorios'] ?? []);
            } elseif ($a = $u->abogado) {
                $a->update(['activo' => false]);
            }
            Auditoria::registrar('usuario.editar', 'usuario', $u->id, collect($d)->except('password')->all(), $editorId);
        });

        return $u->refresh();
    }

    public static function cambiarActivo(Usuario $u, bool $activo, int $actorId): Usuario
    {
        if (! $activo && $u->id === $actorId) {
            throw ValidationException::withMessages(['activo' => 'No puede desactivar su propia cuenta.']);
        }
        $u->update(['activo' => $activo]);
        if (! $activo) {
            self::invalidarSesiones($u->id);
        }
        Auditoria::registrar($activo ? 'usuario.activar' : 'usuario.desactivar', 'usuario', $u->id, null, $actorId);

        return $u;
    }

    public static function resetearPassword(Usuario $u, int $actorId): string
    {
        $temporal = self::passwordTemporal();
        $u->forceFill(['password' => $temporal, 'debe_cambiar_password' => true, 'intentos_fallidos' => 0, 'bloqueado_hasta' => null])->save();
        self::invalidarSesiones($u->id);
        Auditoria::registrar('usuario.resetear_password', 'usuario', $u->id, null, $actorId);

        return $temporal;
    }

    public static function invalidarSesiones(int $usuarioId): void
    {
        DB::table('sessions')->where('user_id', $usuarioId)->delete();
    }
}
