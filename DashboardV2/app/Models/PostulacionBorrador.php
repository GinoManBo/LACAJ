<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostulacionBorrador extends Model
{
    protected $table = 'postulacion_borradores';
    protected $guarded = [];
    protected $casts = ['datos' => 'array'];

    public function usuario() { return $this->belongsTo(Usuario::class); }
}
