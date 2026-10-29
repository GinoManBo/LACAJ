<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'regiones';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = ['operativa' => 'boolean'];

    public function comunas()
    {
        return $this->hasMany(Comuna::class, 'region_id');
    }

    public function consultorios()
    {
        return $this->hasMany(Consultorio::class, 'region_id');
    }

    public function scopeOperativas($q)
    {
        return $q->where('operativa', true);
    }
}
