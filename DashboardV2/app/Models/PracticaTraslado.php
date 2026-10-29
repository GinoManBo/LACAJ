<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticaTraslado extends Model
{
    protected $table = 'practica_traslados';
    protected $guarded = [];
    const UPDATED_AT = null;
    public function practica() { return $this->belongsTo(Practica::class); }
    public function consultorioOrigen() { return $this->belongsTo(Consultorio::class, 'consultorio_origen_id'); }
    public function consultorioDestino() { return $this->belongsTo(Consultorio::class, 'consultorio_destino_id'); }
}
