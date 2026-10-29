<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $table = 'documentos';
    protected $guarded = [];
    const UPDATED_AT = null;
    public function postulacion() { return $this->belongsTo(Postulacion::class); }
    public function practica() { return $this->belongsTo(Practica::class); }
}
