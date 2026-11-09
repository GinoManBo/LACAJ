<?php

namespace App\Services;

use App\Models\Practica;
use App\Models\Usuario;
use App\Support\Rut;
use Barryvdh\DomPDF\Facade\Pdf;

class CartaPresentacionService
{
    /**
     * Genera el contenido binario del PDF tipo de la Carta de Presentación Oficial.
     */
    public static function generarPdf(Practica $p, ?Usuario $emisor = null): string
    {
        $p->loadMissing([
            'practicante.postulante.comuna',
            'consultorio.comuna',
            'consultorio.region',
            'abogado.usuario',
        ]);

        $post = $p->practicante->postulante;
        $consultorio = $p->consultorio;
        $abogado = $p->abogado?->usuario;
        $femenino = $post->genero === 'femenino';
        $inst = config('caj_documentos');

        $ciudad = $consultorio->comuna?->nombre ?? ($inst['ciudad'] ?? 'Concepción');
        $fecha = now()->locale('es')->translatedFormat('d \d\e F \d\e Y');
        $fechaInicio = $p->fecha_inicio ? $p->fecha_inicio->locale('es')->translatedFormat('d \d\e F \d\e Y') : $fecha;

        // Número correlativo de registro institucional (folio manual o fallback institucional)
        $numeroRegistro = $p->numero_registro_carta;

        $abogadoJefeNombre = $abogado
            ? $abogado->nombre.' '.$abogado->apellido
            : ($consultorio->nombre_responsable ?? 'ABOGADO JEFE');

        $esBiobio = $consultorio->region?->codigo === 'BI';
        if ($esBiobio) {
            $autoridad = ($emisor && $emisor->rol === 'jefe_ucoe')
                ? $emisor
                : Usuario::where('rol', 'jefe_ucoe')->where('activo', true)->first();
            $emisorNombre = $autoridad
                ? $autoridad->nombre_completo
                : ($inst['director_nombre'] ?? 'LUIS NÚÑEZ OVIEDO');
            $emisorCargo = 'JEFE DE LA UNIDAD DE CONSULTORIOS Y OFICINAS ESPECIALIZADAS (UCOE)';
        } else {
            $autoridad = ($emisor && $emisor->rol === 'director_regional' && $emisor->region_id === $consultorio->region_id)
                ? $emisor
                : Usuario::where('rol', 'director_regional')
                    ->where('region_id', $consultorio->region_id)
                    ->where('activo', true)
                    ->first();
            $nomRegion = $consultorio->region?->nombre ?? 'REGIONAL';
            $emisorNombre = $autoridad
                ? $autoridad->nombre_completo
                : 'DIRECTOR REGIONAL';
            $emisorCargo = 'DIRECTOR REGIONAL DE '.mb_strtoupper($nomRegion);
        }

        $ciudadCorte = match ($consultorio->region?->codigo) {
            'NB' => 'Chillán',
            'AR' => 'Temuco',
            'LR' => 'Valdivia',
            'LL' => 'Puerto Montt',
            'AI' => 'Coyhaique',
            default => 'Concepción',
        };

        $domicilio = trim(($post->direccion ?? '').' '.($post->comuna?->nombre ? ', '.$post->comuna->nombre : ''));

        $logoPath = public_path('img/logo-caj.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : null;

        $datos = [
            'logo' => $logoBase64,
            'ciudad' => $ciudad,
            'fecha' => $fecha,
            'emisor_nombre' => $emisorNombre,
            'emisor_cargo' => $emisorCargo,
            'abogado_jefe_nombre' => $abogadoJefeNombre,
            'consultorio_nombre' => $consultorio->nombre,
            'postulante_tratamiento' => $femenino ? 'la postulante, doña' : 'el postulante, don',
            'postulante_nombre_completo' => $post->nombre.' '.$post->apellido,
            'postulante_rut' => Rut::formatear($post->rut),
            'postulante_universidad' => $post->universidad,
            'postulante_email' => $post->email,
            'postulante_telefono' => $post->telefono,
            'postulante_domicilio' => $domicilio ?: 'Domicilio registrado en secretaría',
            'fecha_inicio' => $fechaInicio,
            'numero_registro' => $numeroRegistro,
            'ciudad_segunda_instancia' => $ciudadCorte,
            'firma' => null,
        ];

        return Pdf::loadView('pdf.carta_presentacion', $datos)
            ->setPaper('a4')
            ->output();
    }
}
