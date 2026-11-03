<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'activo' => 'boolean',
        'debe_cambiar_password' => 'boolean',
        'ultimo_acceso' => 'datetime',
        'bloqueado_hasta' => 'datetime',
        'firma_actualizada_at' => 'datetime',
    ];

    public const ROLES = ['administrador', 'secretaria', 'juridica_central', 'abogado', 'director_regional', 'jefe_ucoe', 'director_general', 'postulante', 'gobernador', 'practicante'];
    public const ROLES_GESTIONABLES = ['administrador', 'secretaria', 'juridica_central', 'abogado', 'director_regional', 'jefe_ucoe', 'director_general'];

    public function region() { return $this->belongsTo(Region::class); }
    public function abogado() { return $this->hasOne(Abogado::class, 'usuario_id'); }
    public function practicante() { return $this->hasOne(Practicante::class, 'usuario_id'); }

    public function getNombreCompletoAttribute(): string { return "{$this->nombre} {$this->apellido}"; }
    public function esRol(string ...$roles): bool { return in_array($this->rol, $roles, true); }

    public function portalInicio(): string
    {
        return match ($this->rol) {
            'administrador' => route('admin.inicio'),
            'secretaria' => route('secretaria.panel'),
            'juridica_central' => route('secretaria.juridica.index'),
            'abogado' => route('tutor.inicio'),
            'director_regional', 'jefe_ucoe' => route('director.inicio'),
            'director_general' => route('director_general.inicio'),
            'practicante' => route('practicante.inicio'),
            'gobernador' => route('gobernador.inicio'),
            'postulante' => $this->portalInicioPostulante(),
        };
    }

    /**
     * Determina el portal al que ingresa un postulante según la etapa en la que se encuentra.
     * Si ya cuenta con una práctica asignada (en curso, por iniciar o finalizada), va al portal de práctica.
     * De lo contrario, se le dirige a su postulación o borrador.
     */
    public function portalInicioPostulante(): string
    {
        $practica = $this->practicante?->practicas()->latest('id')->first();
        if ($practica && $practica->estado !== 'cancelada') {
            return route('practicante.inicio');
        }

        // Si la última práctica fue cancelada pero el postulante tiene una nueva postulación o borrador:
        $tienePostulacion = Postulacion::whereHas('postulante', fn ($q) => $q->where('rut', $this->rut))->exists();
        $tieneBorrador = PostulacionBorrador::where('usuario_id', $this->id)->exists();

        if ($tienePostulacion || $tieneBorrador) {
            return route('postulante.inicio');
        }

        if ($practica) {
            return route('practicante.inicio');
        }

        return route('postulante.inicio');
    }

    /**
     * Retorna la etapa actual del usuario en la plataforma.
     * Permite consultar en qué fase del flujo continuo se encuentra la persona:
     * - 'sin_postulacion': Usuario registrado sin borrador ni postulación enviada.
     * - 'borrador': Tiene un borrador de postulación pendiente de envío.
     * - 'en_revision': Postulación enviada (pendiente o derivada) esperando resolución.
     * - 'rechazada': Postulación rechazada por la secretaría regional.
     * - 'practica_por_iniciar': Postulación aceptada y práctica asignada antes de su fecha de inicio.
     * - 'practica_activa': Práctica profesional en curso.
     * - 'practica_finalizada': Práctica completada y evaluada.
     * - 'practica_cancelada': Práctica cancelada antes o durante su ejecución.
     */
    public function etapa(): string
    {
        $practica = $this->practicante?->practicas()->latest('id')->first();
        $postulacion = Postulacion::whereHas('postulante', fn ($q) => $q->where('rut', $this->rut))
            ->latest('fecha_postulacion')
            ->first();
        $tieneBorrador = PostulacionBorrador::where('usuario_id', $this->id)->exists();

        // 1. Si la última práctica fue cancelada y cuenta con una nueva postulación o borrador posterior:
        if ($practica && $practica->estado === 'cancelada') {
            if ($postulacion && ($postulacion->created_at >= $practica->created_at || in_array($postulacion->estado, ['pendiente', 'derivada', 'subsanacion'], true))) {
                return match ($postulacion->estado) {
                    'pendiente', 'derivada' => 'en_revision',
                    'rechazada' => 'rechazada',
                    'aceptada' => 'practica_por_iniciar',
                    default => $postulacion->estado,
                };
            }
            if ($tieneBorrador) {
                return 'borrador';
            }

            return 'practica_cancelada';
        }

        // 2. Si cuenta con una práctica asignada vigente o completada
        if ($practica) {
            return match ($practica->estado) {
                'por_iniciar' => 'practica_por_iniciar',
                'activa' => 'practica_activa',
                'finalizada' => 'practica_finalizada',
                'cancelada' => 'practica_cancelada',
                default => 'practica_' . $practica->estado,
            };
        }

        // 3. Si no tiene práctica, verificamos el estado de su postulación formal
        if ($postulacion) {
            return match ($postulacion->estado) {
                'pendiente', 'derivada' => 'en_revision',
                'rechazada' => 'rechazada',
                'aceptada' => 'practica_por_iniciar',
                default => $postulacion->estado,
            };
        }

        // 4. Verificamos si tiene un borrador guardado
        if ($tieneBorrador) {
            return 'borrador';
        }

        return 'sin_postulacion';
    }
}
