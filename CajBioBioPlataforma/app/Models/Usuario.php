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

    public const ROLES = ['administrador', 'secretaria', 'abogado', 'postulante', 'gobernador', 'practicante'];
    public const ROLES_GESTIONABLES = ['administrador', 'secretaria', 'abogado', 'gobernador'];

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
            'abogado' => route('tutor.inicio'),
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
        if ($this->practicante?->practicas()->exists()) {
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
        // 1. Verificamos si ya cuenta con una práctica asignada (fase activa / finalizada / cancelada)
        $practica = $this->practicante?->practicas()->latest('id')->first();
        if ($practica) {
            return match ($practica->estado) {
                'por_iniciar' => 'practica_por_iniciar',
                'activa' => 'practica_activa',
                'finalizada' => 'practica_finalizada',
                'cancelada' => 'practica_cancelada',
                default => 'practica_' . $practica->estado,
            };
        }

        // 2. Si no tiene práctica, verificamos el estado de su postulación formal
        $postulacion = Postulacion::whereHas('postulante', fn ($q) => $q->where('rut', $this->rut))
            ->latest('fecha_postulacion')
            ->first();

        if ($postulacion) {
            return match ($postulacion->estado) {
                'pendiente', 'derivada' => 'en_revision',
                'rechazada' => 'rechazada',
                'aceptada' => 'practica_por_iniciar',
                default => $postulacion->estado,
            };
        }

        // 3. Verificamos si tiene un borrador guardado
        $tieneBorrador = PostulacionBorrador::where('usuario_id', $this->id)->exists();
        if ($tieneBorrador) {
            return 'borrador';
        }

        return 'sin_postulacion';
    }
}
