<?php

namespace App\Services;

use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Usuario;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;

class ImportadorPostulaciones
{
    public const COLUMNAS = ['NombrePostulante', 'Universidad', 'NumeroTelefono', 'fechaPostulacion', 'Correo', 'NumeroTelefono'];
    public const MAX_FILAS = 500;

    /** @return array{creadas:int, omitidas:array<int,array{fila:int,motivo:string}>} */
    public static function importar(string $ruta, Usuario $actor, int $regionId): array
    {
        $filas = self::leer($ruta);
        $creadas = 0;
        $omitidas = [];
        $correosVistos = [];

        foreach ($filas as $n => $f) {
            [$nombre, $universidad, $tel1, $fecha, $correo, $tel2] = $f;

            $v = Validator::make(
                ['nombre' => $nombre, 'universidad' => $universidad, 'correo' => $correo],
                ['nombre' => ['required', 'string', 'max:160'], 'universidad' => ['required', 'string', 'max:160'], 'correo' => ['required', 'email', 'max:160']],
                ['nombre.required' => 'Falta el nombre.', 'universidad.required' => 'Falta la universidad.', 'correo.required' => 'Falta el correo.', 'correo.email' => 'El correo no es válido.', 'max' => 'Un dato es demasiado largo.'],
            );
            if ($v->fails()) {
                $omitidas[] = ['fila' => $n, 'motivo' => $v->errors()->first()];
                continue;
            }

            $fechaPost = self::fecha($fecha);
            if ($fechaPost === false) {
                $omitidas[] = ['fila' => $n, 'motivo' => 'La fecha no es válida (use dd-mm-aaaa).'];
                continue;
            }

            $email = mb_strtolower($correo);
            $ya = isset($correosVistos[$email]) || Postulacion::where('estado', 'pendiente')
                ->whereHas('postulante', fn ($q) => $q->where('email', $email))->exists();
            if ($ya) {
                $omitidas[] = ['fila' => $n, 'motivo' => 'Ya hay una postulación pendiente con ese correo.'];
                continue;
            }
            $correosVistos[$email] = true;

            DB::transaction(function () use ($nombre, $universidad, $tel1, $tel2, $email, $fechaPost, $regionId) {
                $postulante = Postulante::create([
                    'nombre' => $nombre, 'apellido' => '', 'email' => $email, 'universidad' => $universidad,
                    'telefono' => $tel1 ?: null, 'emergencia_telefono' => $tel2 ?: null, 'region_id' => $regionId,
                ]);
                Postulacion::create([
                    'postulante_id' => $postulante->id, 'estado' => 'pendiente', 'origen' => 'excel',
                    'region_id' => $regionId, 'fecha_postulacion' => $fechaPost ?? now(),
                ]);
            });
            $creadas++;
        }

        Auditoria::registrar('postulaciones.importar_excel', 'postulacion', null, ['creadas' => $creadas, 'omitidas' => count($omitidas)], $actor->id);

        return ['creadas' => $creadas, 'omitidas' => $omitidas];
    }

    /** @return array<int,array<int,string>> fila (n.º de fila en la hoja) => 6 celdas como texto */
    private static function leer(string $ruta): array
    {
        $reader = new Reader();
        $filas = [];
        try {
            $reader->open($ruta);
            foreach ($reader->getSheetIterator() as $hoja) {
                $n = 0;
                foreach ($hoja->getRowIterator() as $fila) {
                    $n++;
                    $celdas = array_map(
                        fn ($c) => $c instanceof DateTimeInterface ? $c->format('Y-m-d H:i:s') : trim((string) $c),
                        array_slice(array_pad($fila->toArray(), 6, ''), 0, 6),
                    );
                    if ($n === 1) {
                        self::validarEncabezado($celdas);
                        continue;
                    }
                    if (implode('', $celdas) === '') {
                        continue;
                    }
                    if (count($filas) >= self::MAX_FILAS) {
                        throw new RuntimeException('El archivo supera el máximo de '.self::MAX_FILAS.' filas por importación.');
                    }
                    $filas[$n] = $celdas;
                }
                break;
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw new RuntimeException('No se pudo leer el archivo. Verifique que sea un Excel (.xlsx) válido.');
        } finally {
            $reader->close();
        }

        return $filas;
    }

    private static function validarEncabezado(array $celdas): void
    {
        $norm = fn ($s) => preg_replace('/[^a-z]/', '', mb_strtolower(strtr($s, 'áéíóúÁÉÍÓÚñÑ', 'aeiouaeiounn')));
        if (array_map($norm, $celdas) !== array_map($norm, self::COLUMNAS)) {
            throw new RuntimeException('Las columnas no coinciden con el formato. La primera fila debe ser: '.implode(' | ', self::COLUMNAS).'.');
        }
    }

    /** @return \Illuminate\Support\Carbon|null|false  null = vacía (se usa la fecha actual), false = inválida */
    private static function fecha(string $valor)
    {
        if ($valor === '') {
            return null;
        }
        foreach (['Y-m-d H:i:s', 'd-m-Y', 'd/m/Y', 'Y-m-d'] as $formato) {
            $d = \DateTimeImmutable::createFromFormat('!'.$formato, $valor);
            if ($d !== false && $d->format($formato) === $valor) {
                $c = \Illuminate\Support\Carbon::instance($d);

                return $c->isFuture() ? false : $c;
            }
        }

        return false;
    }
}
