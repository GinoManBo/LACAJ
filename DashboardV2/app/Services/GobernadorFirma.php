<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Http\UploadedFile;

class GobernadorFirma
{
    public static function guardar(Usuario $g, UploadedFile $archivo): void
    {
        $anterior = $g->firma_nombre_almacenado;
        $guardado = Almacenamiento::guardar($archivo);
        $g->forceFill([
            'firma_nombre_almacenado' => $guardado['nombre_almacenado'],
            'firma_mime' => $guardado['mime'],
            'firma_actualizada_at' => now(),
        ])->save();
        if ($anterior) {
            Almacenamiento::eliminar($anterior);
        }
        Auditoria::registrar('gobernador.subir_firma', 'usuario', $g->id, null, $g->id);
    }

    /** @return array{ruta:string, mime:string}|null */
    public static function archivo(Usuario $g): ?array
    {
        if (! $g->firma_nombre_almacenado || ! $g->firma_mime || ! Almacenamiento::existe($g->firma_nombre_almacenado)) {
            return null;
        }

        return ['ruta' => Almacenamiento::ruta($g->firma_nombre_almacenado), 'mime' => $g->firma_mime];
    }

    public static function dataUri(?Usuario $g = null): ?string
    {
        $g ??= Usuario::where('rol', 'gobernador')->where('activo', true)->whereNotNull('firma_nombre_almacenado')->orderBy('id')->first();
        $a = $g ? self::archivo($g) : null;

        return $a ? 'data:'.$a['mime'].';base64,'.base64_encode(file_get_contents($a['ruta'])) : null;
    }
}
