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

    public const ROLES = ['administrador', 'secretaria', 'abogado', 'practicante', 'gobernador', 'postulante'];
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
            'postulante' => route('postulante.inicio'),
        };
    }
}
