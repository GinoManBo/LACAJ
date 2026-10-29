<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comuna extends Model
{
    protected $table = 'comunas';
    protected $guarded = [];
    public $timestamps = false;
    public function region() { return $this->belongsTo(Region::class, 'region_id'); }
}
