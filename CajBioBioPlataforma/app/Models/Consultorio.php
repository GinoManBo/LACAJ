<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultorio extends Model
{
    protected $table = 'consultorios';
    protected $guarded = [];

    protected $casts = ['acepta_postulaciones' => 'boolean', 'activo' => 'boolean'];
    public function comuna() { return $this->belongsTo(Comuna::class); }
    public function region() { return $this->belongsTo(Region::class); }
    public function practicas() { return $this->hasMany(Practica::class); }
    public function abogados() { return $this->belongsToMany(Abogado::class, 'abogado_consultorio'); }
    public function ocupados(): int { return $this->practicas()->whereIn('estado', ['por_iniciar', 'activa'])->count(); }
    public function libres(): int { return max(0, $this->capacidad - $this->ocupados()); }
}
