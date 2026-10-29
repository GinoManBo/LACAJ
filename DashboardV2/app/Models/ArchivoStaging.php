<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoStaging extends Model
{
    protected $table = 'archivos_staging';
    protected $guarded = [];
    const UPDATED_AT = null;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $casts = ['expires_at' => 'datetime', 'consumed_at' => 'datetime'];
}
