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

    public function practicasPreviasCanceladas()
    {
        return Practica::where('estado', 'cancelada')
            ->where(function ($q) {
                $q->whereHas('practicante.postulante', fn ($sub) => $sub->where('rut', $this->rut))
                    ->orWhereHas('postulacion.postulante', fn ($sub) => $sub->where('rut', $this->rut));
            })
            ->with(['consultorio.region', 'abogado.usuario', 'canceladaPor'])
            ->orderByDesc('cancelacion_fecha')
            ->orderByDesc('id')
            ->get();
    }

    public function tieneCancelacionPrevia(): bool
    {
        return Practica::where('estado', 'cancelada')
            ->where(function ($q) {
                $q->whereHas('practicante.postulante', fn ($sub) => $sub->where('rut', $this->rut))
                    ->orWhereHas('postulacion.postulante', fn ($sub) => $sub->where('rut', $this->rut));
            })
            ->exists();
    }
}
