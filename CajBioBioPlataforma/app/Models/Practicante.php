<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Practicante extends Model
{
    protected $table = 'practicantes';
    protected $guarded = [];
    public $timestamps = false;
    public function postulante() { return $this->belongsTo(Postulante::class); }
    public function usuario() { return $this->belongsTo(Usuario::class); }
    public function practicas() { return $this->hasMany(Practica::class); }
}
