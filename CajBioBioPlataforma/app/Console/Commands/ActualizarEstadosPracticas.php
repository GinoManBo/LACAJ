<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use App\Models\Practica;
use App\Services\Notificaciones;
use App\Services\Practicas;
use Illuminate\Console\Command;

class ActualizarEstadosPracticas extends Command
{
    protected $signature = 'practicas:actualizar-estados';
    protected $description = 'Activa las prácticas que ya comenzaron y avisa al tutor de las que están por terminar.';

    public function handle(): int
    {
        $activadas = Practica::where('estado', 'por_iniciar')->whereDate('fecha_inicio', '<=', today())->update(['estado' => 'activa']);

        $avisadas = 0;
        // DELIMITACIÓN OPERATIVA DE ALERTAS:
        // Las alertas de término (a dos semanas del fin reglamentario / día 166) se despachan
        // estrictamente al Abogado Tutor y al Postulante en formación.
        // El Administrador Central está EXPRESAMENTE EXCLUIDO de la recepción de estas alertas automáticas
        // masivas para prevenir la saturación de su bandeja institucional.
        Practica::with('abogado', 'practicante.postulante')
            ->where('estado', 'activa')
            ->whereNotNull('abogado_id')
            ->whereBetween('fecha_termino', [today(), today()->addDays(Practicas::DIAS_POR_TERMINAR)])
            ->get()
            ->each(function (Practica $p) use (&$avisadas) {
                // 1. Alerta al Abogado Jefe / Tutor
                $yaAvisadaTutor = Notificacion::where('tipo', 'urgente')
                    ->where('entidad_tipo', 'practica')
                    ->where('entidad_id', $p->id)
                    ->where('usuario_destino_id', $p->abogado->usuario_id)
                    ->exists();

                $po = $p->practicante?->postulante;

                if (! $yaAvisadaTutor && $po) {
                    Notificaciones::crear(
                        $p->abogado->usuario_id,
                        'urgente',
                        'Práctica próxima a finalizar',
                        "La práctica de {$po->nombre} {$po->apellido} termina el {$p->fecha_termino->format('d-m-Y')}. Solicite el Informe de Práctica y prepare la evaluación reglamentaria.",
                        'practica',
                        $p->id
                    );
                    $avisadas++;
                }

                // 2. Alerta recordatoria al Postulante (Día 166 / 2 semanas antes)
                if ($p->practicante?->usuario_id) {
                    $yaAvisadoPost = Notificacion::where('tipo', 'urgente')
                        ->where('entidad_tipo', 'practica')
                        ->where('entidad_id', $p->id)
                        ->where('usuario_destino_id', $p->practicante->usuario_id)
                        ->exists();

                    if (! $yaAvisadoPost) {
                        Notificaciones::crear(
                            $p->practicante->usuario_id,
                            'urgente',
                            'Próximo a cumplir 6 meses de práctica',
                            "Su práctica profesional concluye el {$p->fecha_termino->format('d-m-Y')}. Recuerde emitir y cargar su Informe de Práctica del SAJ para revisión de su Abogado Jefe.",
                            'practica',
                            $p->id
                        );
                    }
                }
            });

        $this->info("Prácticas activadas: {$activadas}. Avisos de cierre: {$avisadas}.");

        return self::SUCCESS;
    }
}
