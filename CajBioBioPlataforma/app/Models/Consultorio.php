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
            ->whereIn('estado', ['por_iniciar', 'activa'])
            ->where('fecha_termino', '>', today())
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
     * Normaliza los saltos de línea del horario de atención,
     * convirtiendo secuencias de escape literales ('\n') y espaciados tabulares en saltos de línea reales.
     */
    public function getHorarioAtencionAttribute($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        // 1. Reemplazar secuencias literales de escape de texto (\r\n, \n, \r)
        $texto = str_replace(['\r\n', '\r', '\n'], "\n", (string) $value);

        // 2. Reemplazar bloques de múltiples espacios (5 o más espacios consecutivos producto de importaciones tabulares)
        $texto = preg_replace('/[ \t]{5,}/', "\n", $texto);

        // 3. Normalizar saltos de línea Windows / Mac a estándar Unix
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);

        // 4. Limpiar espacios al inicio y final de cada línea
        $lineas = array_map('trim', explode("\n", $texto));

        return implode("\n", array_filter($lineas, fn ($l) => $l !== ''));
    }

    /**
     * Sanitiza y normaliza los saltos de línea al guardar el horario de atención.
     */
    public function setHorarioAtencionAttribute($value): void
    {
        if ($value === null || trim((string) $value) === '') {
            $this->attributes['horario_atencion'] = null;
            return;
        }

        $texto = str_replace(['\r\n', '\r', '\n'], "\n", (string) $value);
        $texto = preg_replace('/[ \t]{5,}/', "\n", $texto);
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $lineas = array_map('trim', explode("\n", $texto));

        $this->attributes['horario_atencion'] = implode("\n", array_filter($lineas, fn ($l) => $l !== ''));
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
