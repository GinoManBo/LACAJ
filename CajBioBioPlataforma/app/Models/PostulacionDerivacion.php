<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostulacionDerivacion extends Model
{
    protected $table = 'postulacion_derivaciones';
    protected $guarded = [];
    const UPDATED_AT = null;
    public function regionOrigen() { return $this->belongsTo(Region::class, 'region_origen_id'); }
    public function regionDestino() { return $this->belongsTo(Region::class, 'region_destino_id'); }
    public function usuario() { return $this->belongsTo(Usuario::class); }
}
