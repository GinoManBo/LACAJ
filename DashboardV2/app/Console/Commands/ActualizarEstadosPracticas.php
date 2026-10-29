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
        Practica::with('abogado', 'practicante.postulante')
            ->where('estado', 'activa')
            ->whereNotNull('abogado_id')
            ->whereBetween('fecha_termino', [today(), today()->addDays(Practicas::DIAS_POR_TERMINAR)])
            ->get()
            ->each(function (Practica $p) use (&$avisadas) {
                $yaAvisada = Notificacion::where('tipo', 'urgente')->where('entidad_tipo', 'practica')
                    ->where('entidad_id', $p->id)->where('usuario_destino_id', $p->abogado->usuario_id)->exists();
                if ($yaAvisada) {
                    return;
                }
                $po = $p->practicante->postulante;
                Notificaciones::crear($p->abogado->usuario_id, 'urgente', 'Práctica próxima a finalizar',
                    "La práctica de {$po->nombre} {$po->apellido} termina el {$p->fecha_termino->format('d-m-Y')}. Complete el informe de cierre y la encuesta final.",
                    'practica', $p->id);
                $avisadas++;
            });

        $this->info("Prácticas activadas: {$activadas}. Avisos de cierre: {$avisadas}.");

        return self::SUCCESS;
    }
}
