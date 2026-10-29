<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostulacionPreferencia extends Model
{
    protected $table = 'postulacion_preferencias';
    protected $guarded = [];
    public $timestamps = false;
    public function consultorio() { return $this->belongsTo(Consultorio::class); }
}
