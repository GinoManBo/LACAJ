<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificado extends Model
{
    protected $table = 'certificados';
    protected $guarded = [];
    const UPDATED_AT = null;
    protected $casts = ['fecha_emision' => 'date'];
    public function practica() { return $this->belongsTo(Practica::class); }
    public function documento() { return $this->belongsTo(Documento::class); }
}
