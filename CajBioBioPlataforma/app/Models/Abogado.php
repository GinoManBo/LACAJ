<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abogado extends Model
{
    protected $table = 'abogados';
    protected $guarded = [];
    public $timestamps = false;
    protected $casts = [
        'activo' => 'boolean',
        'es_segunda_instancia' => 'boolean',
    ];

    public function usuario() { return $this->belongsTo(Usuario::class); }
    public function region() { return $this->belongsTo(Region::class); }
    public function consultorios() { return $this->belongsToMany(Consultorio::class, 'abogado_consultorio'); }
    public function practicas() { return $this->hasMany(Practica::class); }
    public function practicasSegundaInstancia() { return $this->hasMany(Practica::class, 'abogado_segunda_instancia_id'); }
    public function esSegundaInstancia(): bool { return (bool) $this->es_segunda_instancia; }
}
