<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';
    protected $guarded = [];
    const UPDATED_AT = null;
    protected $casts = ['leida_at' => 'datetime'];
}
