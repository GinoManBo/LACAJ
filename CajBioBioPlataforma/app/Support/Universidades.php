<?php

namespace App\Support;

final class Universidades
{
    /**
     * Catálogo oficial de instituciones y universidades registradas en la plataforma.
     */
    public const TODAS = [
        'Pontificia Universidad Católica de Chile (UC)',
        'Pontificia Universidad Católica de Valparaíso (PUCV)',
        'Universidad Academia de Humanismo Cristiano (UAHC)',
        'Universidad Adolfo Ibáñez (UAI)',
        'Universidad Adventista de Chile (UNACH)',
        'Universidad Alberto Hurtado (UAH)',
        'Universidad Andrés Bello (UNAB)',
        'Universidad Arturo Prat (UNAP)',
        'Universidad Autónoma de Chile',
        'Universidad Bernardo O\'Higgins (UBO)',
        'Universidad Bolivariana',
        'Universidad Católica de la Santísima Concepción (UCSC)',
        'Universidad Católica de Temuco (UCT)',
        'Universidad Católica del Maule (UCM)',
        'Universidad Católica del Norte (UCN)',
        'Universidad Católica Silva Henríquez (UCSH)',
        'Universidad Central de Chile',
        'Universidad de Aconcagua (UAC)',
        'Universidad de Antofagasta (UA)',
        'Universidad de Atacama (UDA)',
        'Universidad de Aysén',
        'Universidad de Chile (UCH)',
        'Universidad de Concepción (UdeC)',
        'Universidad de La Frontera (UFRO)',
        'Universidad de La Serena (ULS)',
        'Universidad de Las Américas (UDLA)',
        'Universidad de los Andes (UANDES)',
        'Universidad de Los Lagos (ULagos)',
        'Universidad de Magallanes (UMAG)',
        'Universidad de O\'Higgins (UOH)',
        'Universidad de Santiago de Chile (USACH)',
        'Universidad de Talca (UTalca)',
        'Universidad de Tarapacá (UTA)',
        'Universidad de Valparaíso (UV)',
        'Universidad del Alba (UDALBA)',
        'Universidad del Bío-Bío (UBB)',
        'Universidad del Desarrollo (UDD)',
        'Universidad del Mar (Extinta / Cerrada)',
        'Universidad del Pacífico (Extinta / Cerrada)',
        'Universidad Diego Portales (UDP)',
        'Universidad Finis Terrae',
        'Universidad Gabriela Mistral (UGM)',
        'Universidad La República',
        'Universidad Mayor',
        'Universidad Miguel de Cervantes (UMC)',
        'Universidad Regional San Marcos',
        'Universidad San Sebastián (USS)',
        'Universidad Santo Tomás (UST)',
        'Universidad SEK',
        'Universidad Tecnológica de Chile - INACAP (Cerrada)',
        'Universidad Tecnológica Metropolitana (UTEM)',
        'Universidad UNIACC',
        'Universidad Viña del Mar (UVM)',
        'Otra institución no listada',
    ];

    /**
     * Retorna el listado completo de universidades oficiales.
     *
     * @return array<string>
     */
    public static function todas(): array
    {
        return self::TODAS;
    }
}
