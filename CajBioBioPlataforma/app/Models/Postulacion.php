<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postulacion extends Model
{
    protected $table = 'postulaciones';
    protected $guarded = [];

    protected $casts = ['fecha_postulacion' => 'datetime', 'fecha_inicio_solicitada' => 'date', 'resuelta_at' => 'datetime'];
    public function postulante() { return $this->belongsTo(Postulante::class); }
    public function region() { return $this->belongsTo(Region::class); }
    public function revisadaPor() { return $this->belongsTo(Usuario::class, 'revisada_por'); }
    public function consultorioAsignado() { return $this->belongsTo(Consultorio::class, 'consultorio_asignado_id'); }
    public function preferencias() { return $this->hasMany(PostulacionPreferencia::class)->orderBy('orden'); }
    public function derivaciones() { return $this->hasMany(PostulacionDerivacion::class); }
    public function notas() { return $this->hasMany(PostulacionNota::class)->orderBy('created_at'); }
    public function documentos() { return $this->hasMany(Documento::class); }
    public function practica() { return $this->hasOne(Practica::class); }
}
