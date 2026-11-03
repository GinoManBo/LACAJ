<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Practica extends Model
{
    protected $table = 'practicas';
    protected $guarded = [];

    protected $casts = [
        'fecha_inicio' => 'date', 'fecha_termino' => 'date', 'finalizada_at' => 'datetime',
        'cancelacion_fecha' => 'date', 'cancelacion_antes_de_iniciar' => 'boolean',
        'documentos_autorizados' => 'boolean', 'documentos_autorizados_at' => 'datetime',
        'remitida_at' => 'datetime', 'nivelada_at' => 'datetime', 'despachada_at' => 'datetime',
        'validada_director_at' => 'datetime', 'firmado_director_general_at' => 'datetime',
        'carta_presentacion_subida_at' => 'datetime',
        'nota_practica' => 'float',
    ];
    public function postulacion() { return $this->belongsTo(Postulacion::class); }
    public function practicante() { return $this->belongsTo(Practicante::class); }
    public function consultorio() { return $this->belongsTo(Consultorio::class); }
    public function abogado() { return $this->belongsTo(Abogado::class); }
    public function abogadoSegundaInstancia() { return $this->belongsTo(Abogado::class, 'abogado_segunda_instancia_id'); }
    public function evaluacion() { return $this->hasOne(Evaluacion::class); }
    public function canceladaPor() { return $this->belongsTo(Usuario::class, 'cancelada_por'); }
    public function remitidaPor() { return $this->belongsTo(Usuario::class, 'remitida_por'); }
    public function niveladaPor() { return $this->belongsTo(Usuario::class, 'nivelada_por'); }
    public function despachadaPor() { return $this->belongsTo(Usuario::class, 'despachada_por'); }
    public function validadaDirectorPor() { return $this->belongsTo(Usuario::class, 'validada_director_por'); }
    public function revisorColegiado() { return $this->belongsTo(Usuario::class, 'revisor_colegiado_id'); }
    public function firmadoDirectorGeneralPor() { return $this->belongsTo(Usuario::class, 'firmado_director_general_por'); }
    public function certificadoFirmagob() { return $this->belongsTo(Documento::class, 'certificado_firmagob_id'); }
    public function calificaciones() { return $this->hasMany(Calificacion::class)->orderBy('instancia'); }
    public function calificacionPrimeraInstancia() { return $this->hasOne(Calificacion::class)->where('instancia', 1); }
    public function calificacionSegundaInstancia() { return $this->hasOne(Calificacion::class)->where('instancia', 2); }
    public function calificacionTerceraInstancia() { return $this->hasOne(Calificacion::class)->where('instancia', 3); }
    public function traslados() { return $this->hasMany(PracticaTraslado::class); }
    public function documentos() { return $this->hasMany(Documento::class); }
    public function certificados() { return $this->hasMany(Certificado::class); }
}
