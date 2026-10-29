<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Almacenamiento
{
    private const DISCO = 'local';
    private const DIR = 'documentos';

    /** @return array{nombre_almacenado:string, mime:string, tamano_bytes:int, sha256:string, nombre_original:string} */
    public static function guardar(UploadedFile $archivo): array
    {
        $nombre = (string) Str::uuid();
        $mime = $archivo->getMimeType() ?: 'application/octet-stream';
        $sha = hash_file('sha256', $archivo->getRealPath());
        Storage::disk(self::DISCO)->putFileAs(self::DIR, $archivo, $nombre);

        return [
            'nombre_almacenado' => $nombre,
            'mime' => $mime,
            'tamano_bytes' => $archivo->getSize(),
            'sha256' => $sha,
            'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 255),
        ];
    }

    public static function ruta(string $nombreAlmacenado): string
    {
        return Storage::disk(self::DISCO)->path(self::DIR.'/'.$nombreAlmacenado);
    }

    public static function existe(string $nombreAlmacenado): bool
    {
        return Storage::disk(self::DISCO)->exists(self::DIR.'/'.$nombreAlmacenado);
    }

    public static function eliminar(string $nombreAlmacenado): void
    {
        Storage::disk(self::DISCO)->delete(self::DIR.'/'.$nombreAlmacenado);
    }
}
