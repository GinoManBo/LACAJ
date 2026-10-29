<?php

namespace Database\Seeders;

use App\Models\Comuna;
use App\Models\Consultorio;
use App\Models\Especialidad;
use App\Models\Region;
use Illuminate\Database\Seeder;

class ReferenciasSeeder extends Seeder
{
    private const REGIONES = [
        ['NB', 'Región de Ñuble', 1], ['BI', 'Región del Biobío', 2],
        ['AR', 'Región de La Araucanía', 3], ['LR', 'Región de Los Ríos', 4],
        ['LL', 'Región de Los Lagos', 5], ['AI', 'Región de Aysén', 6],
    ];

    private const COMUNAS_POR_REGION = [
        'NB' => 'Chillán, Bulnes, Chillán Viejo, El Carmen, Pemuco, Pinto, Quillón, San Ignacio, Yungay, Quirihue, Cobquecura, Coelemu, Ninhue, Portezuelo, Ránquil, Treguaco, San Carlos, Coihueco, Ñiquén, San Fabián, San Nicolás',
        'BI' => 'Concepción, Coronel, Chiguayante, Florida, Hualqui, Lota, Penco, San Pedro de la Paz, Santa Juana, Talcahuano, Tomé, Hualpén, Lebu, Arauco, Cañete, Contulmo, Curanilahue, Los Álamos, Tirúa, Los Ángeles, Antuco, Cabrero, Laja, Mulchén, Nacimiento, Negrete, Quilaco, Quilleco, San Rosendo, Santa Bárbara, Tucapel, Yumbel, Alto Biobío',
        'AR' => 'Temuco, Carahue, Cunco, Curarrehue, Freire, Galvarino, Gorbea, Lautaro, Loncoche, Melipeuco, Nueva Imperial, Padre Las Casas, Perquenco, Pitrufquén, Pucón, Saavedra, Teodoro Schmidt, Toltén, Vilcún, Villarrica, Cholchol, Angol, Collipulli, Curacautín, Ercilla, Lonquimay, Los Sauces, Lumaco, Purén, Renaico, Traiguén, Victoria',
        'LR' => 'Valdivia, Corral, Lanco, Los Lagos, Máfil, Mariquina, Paillaco, Panguipulli, La Unión, Futrono, Lago Ranco, Río Bueno',
        'LL' => 'Puerto Montt, Calbuco, Cochamó, Fresia, Frutillar, Los Muermos, Llanquihue, Maullín, Puerto Varas, Castro, Ancud, Chonchi, Curaco de Vélez, Dalcahue, Puqueldón, Queilén, Quellón, Quemchi, Quinchao, Osorno, Puerto Octay, Purranque, Puyehue, Río Negro, San Juan de la Costa, San Pablo, Chaitén, Futaleufú, Hualaihué, Palena',
        'AI' => "Coyhaique, Lago Verde, Aysén, Cisnes, Guaitecas, Cochrane, O'Higgins, Tortel, Chile Chico, Río Ibáñez",
    ];

    private const COMUNAS = [
        ['BI', 'Concepción', -36.8270, -73.0503], ['BI', 'Talcahuano', -36.7249, -73.1169],
        ['BI', 'San Pedro de la Paz', -36.8386, -73.1066], ['BI', 'Los Ángeles', -37.4697, -72.3537],
        ['BI', 'Coronel', -37.0276, -73.1379], ['NB', 'Chillán', -36.6063, -72.1034],
        ['AR', 'Temuco', -38.7359, -72.5904], ['LR', 'Valdivia', -39.8142, -73.2459],
        ['LL', 'Puerto Montt', -41.4693, -72.9424], ['AI', 'Coyhaique', -45.5712, -72.0685],
    ];

    private const CONSULTORIOS = [
        ['Consultorio Centro — Concepción', "O'Higgins 440, piso 3, Concepción", 'Concepción', 6],
        ['Consultorio Talcahuano', 'Colón 1262, Talcahuano', 'Talcahuano', 4],
        ['Consultorio San Pedro de la Paz', 'Av. Gran Bretaña 3035, San Pedro', 'San Pedro de la Paz', 5],
        ['Consultorio Los Ángeles', 'Caupolicán 380, Los Ángeles', 'Los Ángeles', 3],
        ['Consultorio Chillán', 'Arauco 584, Chillán', 'Chillán', 3],
        ['Consultorio Coronel', 'Manuel Rodríguez 481, Coronel', 'Coronel', 2],
        ['Consultorio Temuco', 'Manuel Montt 855, Temuco', 'Temuco', 4],
        ['Consultorio Valdivia', 'Arauco 371, Valdivia', 'Valdivia', 4],
        ['Consultorio Puerto Montt', 'Benavente 405, Puerto Montt', 'Puerto Montt', 4],
        ['Consultorio Coyhaique', 'Baquedano 310, Coyhaique', 'Coyhaique', 2],
    ];

    private const ESPECIALIDADES = [
        ['Derecho Civil', 1], ['Derecho Penal', 2], ['Derecho Laboral', 3],
        ['Derecho de Familia', 4], ['Otra', 99],
    ];

    public function run(): void
    {
        $regiones = [];
        foreach (self::REGIONES as [$codigo, $nombre, $orden]) {
            $regiones[$codigo] = Region::updateOrCreate(
                ['codigo' => $codigo],
                ['nombre' => $nombre, 'orden' => $orden, 'operativa' => true],
            );
        }

        foreach (self::COMUNAS_POR_REGION as $codigo => $nombres) {
            foreach (explode(', ', $nombres) as $nombre) {
                Comuna::firstOrCreate(['region_id' => $regiones[$codigo]->id, 'nombre' => $nombre]);
            }
        }

        $comunas = [];
        foreach (self::COMUNAS as [$codigo, $nombre, $lat, $lng]) {
            $comunas[$nombre] = Comuna::updateOrCreate(
                ['region_id' => $regiones[$codigo]->id, 'nombre' => $nombre],
                ['lat' => $lat, 'lng' => $lng],
            );
        }

        foreach (self::ESPECIALIDADES as [$nombre, $orden]) {
            Especialidad::updateOrCreate(['nombre' => $nombre], ['orden' => $orden]);
        }

        foreach (self::CONSULTORIOS as [$nombre, $direccion, $comuna, $capacidad]) {
            Consultorio::updateOrCreate(['nombre' => $nombre], [
                'direccion' => $direccion,
                'comuna_id' => $comunas[$comuna]->id,
                'region_id' => $comunas[$comuna]->region_id,
                'capacidad' => $capacidad,
            ]);
        }
    }
}
