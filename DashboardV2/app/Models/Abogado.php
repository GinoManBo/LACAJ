<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abogado extends Model
{
    protected $table = 'abogados';
    protected $guarded = [];
    public $timestamps = false;
    protected $casts = ['activo' => 'boolean'];
    public function usuario() { return $this->belongsTo(Usuario::class); }
    public function consultorios() { return $this->belongsToMany(Consultorio::class, 'abogado_consultorio'); }
    public function practicas() { return $this->hasMany(Practica::class); }
}
