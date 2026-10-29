<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'auditoria';
    protected $guarded = [];
    const UPDATED_AT = null;
    protected $casts = ['datos' => 'array'];
}
