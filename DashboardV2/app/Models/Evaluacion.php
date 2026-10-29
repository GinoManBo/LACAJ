<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    protected $table = 'evaluaciones';
    protected $guarded = [];
    const UPDATED_AT = null;
    protected $casts = ['aprobado' => 'boolean', 'promedio' => 'float'];
    public function practica() { return $this->belongsTo(Practica::class); }
    public function abogado() { return $this->belongsTo(Abogado::class); }
}
