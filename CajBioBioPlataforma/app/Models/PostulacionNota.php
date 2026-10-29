<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostulacionNota extends Model
{
    protected $table = 'postulacion_notas';
    protected $guarded = [];
    const UPDATED_AT = null;
    public function autor() { return $this->belongsTo(Usuario::class, 'autor_usuario_id'); }
}
