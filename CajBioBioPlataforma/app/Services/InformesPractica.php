<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Practica;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InformesPractica
{
    public const TIPO = 'informe_practica';

    public static function vigente(Practica $p): ?Documento
    {
        return $p->documentos()->where('tipo', self::TIPO)->latest('id')->first();
    }

    public static function subir(Practica $p, UploadedFile $archivo, Usuario $por): Documento
    {
        if (! in_array($p->estado, ['por_iniciar', 'activa'], true) || $p->evaluacion()->exists()) {
            throw ValidationException::withMessages(['informe' => 'La práctica ya fue evaluada o cerrada; el informe no puede modificarse.']);
        }

        return DB::transaction(function () use ($p, $archivo, $por) {
            foreach ($p->documentos()->where('tipo', self::TIPO)->get() as $viejo) {
                Almacenamiento::eliminar($viejo->nombre_almacenado);
                $viejo->delete();
            }
            $doc = Documento::create(['tipo' => self::TIPO, 'practica_id' => $p->id, 'subido_por' => $por->id] + Almacenamiento::guardar($archivo));
            Auditoria::registrar('practica.informe_subido', 'practica', $p->id, ['documento' => $doc->id]);

            $tutor = $p->abogado?->usuario_id;
            if ($tutor) {
                Notificaciones::crear($tutor, 'aviso', 'Informe de práctica recibido', "{$p->practicante->postulante->nombre_completo} subió su informe: ya puede completar la encuesta de desempeño.", 'practica', $p->id);
            }

            return $doc;
        });
    }

    public static function observar(Practica $p, Usuario $por, string $observacion): void
    {
        Auditoria::registrar('practica.informe_observado', 'practica', $p->id, [
            'observacion' => $observacion,
            'abogado_id' => $por->id,
        ], $por->id);

        if ($usuario = $p->practicante?->usuario_id) {
            Notificaciones::crear(
                $usuario,
                'urgente',
                'Observaciones a su informe de práctica',
                'El Abogado Jefe ha registrado observaciones a su informe final: "'.$observacion.'". Por favor revise y vuelva a cargar el documento subsanado.',
                'practica',
                $p->id
            );
        }
    }
}
