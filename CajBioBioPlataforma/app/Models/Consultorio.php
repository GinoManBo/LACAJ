<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultorio extends Model
{
    protected $table = 'consultorios';
    protected $guarded = [];

    protected $casts = [
        'acepta_postulaciones' => 'boolean',
        'activo' => 'boolean',
        'capacidad' => 'integer',
    ];

    public function comuna()
    {
        return $this->belongsTo(Comuna::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function practicas()
    {
        return $this->hasMany(Practica::class);
    }

    public function abogados()
    {
        return $this->belongsToMany(Abogado::class, 'abogado_consultorio');
    }

    public function ocupados(): int
    {
        // =========================================================================
        // TÉRMINO PROVISIONAL Y LIBERACIÓN AUTOMÁTICA DE VACANTES (DÍA 180):
        // Cumplidos los 6 meses (fecha_termino <= today()), se declara el término
        // provisional y la vacante del consultorio se libera automáticamente
        // para recibir un nuevo postulante, sin esperar el cierre burocrático.
        // =========================================================================
        return $this->practicas()
            ->where(function ($q) {
                $q->where('estado', 'por_iniciar')
                    ->orWhere(function ($q2) {
                        $q2->where('estado', 'activa')
                            ->where('fecha_termino', '>', today());
                    });
            })
            ->count();
    }

    public function libres(): int
    {
        return max(0, $this->capacidad - $this->ocupados());
    }

    /**
     * Retorna una etiqueta amigable y legible para el tipo de unidad institucional.
     */
    public function getTipoUnidadTextoAttribute(): string
    {
        return match ($this->tipo_unidad) {
            'CJ' => 'Consultorio Jurídico (CJ)',
            'OJA' => 'Oficina Jurídica Asistencial (OJA)',
            'CAVI' => 'Centro de Atención a Víctimas (CAVI)',
            'CMED' => 'Centro de Mediación',
            'PMA' => 'Programa Mi Abogado (PMA)',
            'NAD' => 'Niñez y Adolescencia Defendida (NAD)',
            'ODL' => 'Oficina de Defensa Laboral (ODL)',
            'ODAM' => 'Oficina de Defensa Adulto Mayor (ODAM)',
            '2DA INSTANCIA' => 'Oficina de Segunda Instancia (Corte)',
            'MOVIL' => 'Unidad Móvil',
            'DG/DR' => 'Dirección Regional / General',
            default => $this->tipo_unidad ?: 'Unidad Operativa',
        };
    }

    /**
     * Retorna el nombre de la unidad con su comuna para desambiguación precisa en selectores.
     */
    public function getNombreCompletoAttribute(): string
    {
        if ($this->comuna && ! str_contains(mb_strtolower($this->nombre), mb_strtolower($this->comuna->nombre))) {
            return "{$this->nombre} ({$this->comuna->nombre})";
        }

        return $this->nombre;
    }
}
