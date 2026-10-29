<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postulante extends Model
{
    protected $table = 'postulantes';
    protected $guarded = [];

    protected $casts = ['discapacidad' => 'boolean', 'fecha_nacimiento' => 'date', 'datos_actualizados_at' => 'datetime'];
    public function region() { return $this->belongsTo(Region::class); }
    public function comuna() { return $this->belongsTo(Comuna::class); }
    public function especialidad() { return $this->belongsTo(Especialidad::class); }
    public function postulaciones() { return $this->hasMany(Postulacion::class); }
    public function practicante() { return $this->hasOne(Practicante::class); }
    public function getNombreCompletoAttribute(): string { return "{$this->nombre} {$this->apellido}"; }
}
