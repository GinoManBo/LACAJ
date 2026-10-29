<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    protected $table = 'calificaciones';
    protected $guarded = [];
    const UPDATED_AT = null;
    protected $casts = ['nota' => 'float', 'fecha' => 'date'];
    public function practica() { return $this->belongsTo(Practica::class); }
}
