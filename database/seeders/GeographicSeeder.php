<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Municipality;
use App\Models\District;
use Illuminate\Database\Seeder;

class GeographicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dataset estructurado de El Salvador (Reestructuración 2024: 14 Deptos, 44 Municipios, 262 Distritos)
        $data = [
            '01' => [
                'name' => 'Ahuachapán',
                'short' => 'AH',
                'municipalities' => [
                    '0101' => [
                        'name' => 'Ahuachapán Norte',
                        'districts' => ['Atiquizaya', 'El Refugio', 'San Lorenzo', 'Turín']
                    ],
                    '0102' => [
                        'name' => 'Ahuachapán Centro',
                        'districts' => ['Ahuachapán', 'Apaneca', 'Concepción de Ataco', 'Tacuba']
                    ],
                    '0103' => [
                        'name' => 'Ahuachapán Sur',
                        'districts' => ['Guaymango', 'Jujutla', 'San Francisco Menéndez', 'San Pedro Puxtla']
                    ],
                ]
            ],
            '02' => [
                'name' => 'Santa Ana',
                'short' => 'SA',
                'municipalities' => [
                    '0201' => [
                        'name' => 'Santa Ana Norte',
                        'districts' => ['Masahuat', 'Metapán', 'Santa Rosa Guachipilín', 'Texistepeque']
                    ],
                    '0202' => [
                        'name' => 'Santa Ana Centro',
                        'districts' => ['Santa Ana']
                    ],
                    '0203' => [
                        'name' => 'Santa Ana Oeste',
                        'districts' => ['Chalchuapa', 'El Porvenir', 'San Sebastián Salitrillo', 'San Antonio Pajonal', 'Santiago de la Frontera']
                    ],
                    '0204' => [
                        'name' => 'Santa Ana Este',
                        'districts' => ['Coatepeque', 'El Congo']
                    ],
                ]
            ],
            '03' => [
                'name' => 'Sonsonate',
                'short' => 'SO',
                'municipalities' => [
                    '0301' => [
                        'name' => 'Sonsonate Norte',
                        'districts' => ['Juayúa', 'Nahuizalco', 'Salcoatitán', 'Santa Catarina Masahuat']
                    ],
                    '0302' => [
                        'name' => 'Sonsonate Centro',
                        'districts' => ['Sonsonate', 'Sonzacate', 'Izalco', 'Nahulingo', 'San Antonio del Monte', 'Santo Domingo de Guzmán']
                    ],
                    '0303' => [
                        'name' => 'Sonsonate Este',
                        'districts' => ['Armenia', 'Caluco', 'Cuisnahuat', 'San Julián', 'Santa Isabel Ishuatán']
                    ],
                    '0304' => [
                        'name' => 'Sonsonate Oeste',
                        'districts' => ['Acajutla']
                    ],
                ]
            ],
            '04' => [
                'name' => 'Chalatenango',
                'short' => 'CH',
                'municipalities' => [
                    '0401' => [
                        'name' => 'Chalatenango Norte',
                        'districts' => ['La Palma', 'San Ignacio', 'Citalá']
                    ],
                    '0402' => [
                        'name' => 'Chalatenango Centro',
                        'districts' => ['Chalatenango', 'Dulce Nombre de María', 'San Fernando', 'San Francisco Morazán', 'San Rafael', 'Santa Rita', 'Tejutla', 'La Reina', 'Agua Caliente', 'El Carrizal', 'Nueva Concepción']
                    ],
                    '0403' => [
                        'name' => 'Chalatenango Sur',
                        'districts' => ['Arcatao', 'Azacualpa', 'Cancasque', 'Comalapa', 'Concepción Quezaltepeque', 'El Paraíso', 'Las Flores', 'La Laguna', 'Ojos de Agua', 'Potonico', 'San Antonio de la Cruz', 'San Antonio Los Ranchos', 'San Francisco Lempa', 'San Isidro Labrador', 'San Luis del Carmen', 'San Miguel de Mercedes']
                    ],
                ]
            ],
            '05' => [
                'name' => 'La Libertad',
                'short' => 'LL',
                'municipalities' => [
                    '0501' => [
                        'name' => 'La Libertad Norte',
                        'districts' => ['Quezaltepeque', 'San Matías', 'Pablo Tacachico']
                    ],
                    '0502' => [
                        'name' => 'La Libertad Centro',
                        'districts' => ['San Juan Opico', 'Ciudad Arce']
                    ],
                    '0503' => [
                        'name' => 'La Libertad Oeste',
                        'districts' => ['Colón', 'Jayaque', 'Sacacoyo', 'Tepecoyo', 'Talnique']
                    ],
                    '0504' => [
                        'name' => 'La Libertad Este',
                        'districts' => ['Antiguo Cuscatlán', 'Huizúcar', 'Zaragoza', 'San José Villanueva', 'Nuevo Cuscatlán']
                    ],
                    '0505' => [
                        'name' => 'La Libertad Costa',
                        'districts' => ['Chiltiupán', 'Jicalapa', 'La Libertad', 'Tamanique', 'Teotepeque']
                    ],
                    '0506' => [
                        'name' => 'La Libertad Sur',
                        'districts' => ['Santa Tecla', 'Comasagua']
                    ],
                ]
            ],
            '06' => [
                'name' => 'San Salvador',
                'short' => 'SS',
                'municipalities' => [
                    '0601' => [
                        'name' => 'San Salvador Norte',
                        'districts' => ['Aguilares', 'El Paisnal', 'Guazapa']
                    ],
                    '0602' => [
                        'name' => 'San Salvador Oeste',
                        'districts' => ['Apopa', 'Nejapa']
                    ],
                    '0603' => [
                        'name' => 'San Salvador Centro',
                        'districts' => ['Ayutuxtepeque', 'Mejicanos', 'San Salvador', 'Cuscatancingo', 'Ciudad Delgado']
                    ],
                    '0604' => [
                        'name' => 'San Salvador Este',
                        'districts' => ['Ilopango', 'San Martín', 'Soyapango', 'Tonacatepeque']
                    ],
                    '0605' => [
                        'name' => 'San Salvador Sur',
                        'districts' => ['Panchimalco', 'Rosario de Mora', 'San Marcos', 'Santo Tomás', 'Santiago Texacuangos']
                    ],
                ]
            ],
            '07' => [
                'name' => 'Cuscatlán',
                'short' => 'CU',
                'municipalities' => [
                    '0701' => [
                        'name' => 'Cuscatlán Norte',
                        'districts' => ['Suchitoto', 'San José Guayabal', 'Oratorio de Concepción', 'San Bartolomé Perulapía', 'San Pedro Perulapán']
                    ],
                    '0702' => [
                        'name' => 'Cuscatlán Sur',
                        'districts' => ['Cojutepeque', 'Candelaria', 'El Carmen', 'El Rosario', 'Monte San Juan', 'San Cristóbal', 'San Rafael Cedros', 'San Ramón', 'Santa Cruz Analquito', 'Santa Cruz Michapa', 'Tenancingo']
                    ],
                ]
            ],
            '08' => [
                'name' => 'La Paz',
                'short' => 'LP',
                'municipalities' => [
                    '0801' => [
                        'name' => 'La Paz Centro',
                        'districts' => ['El Rosario', 'Jerusalén', 'Mercedes La Ceiba', 'Paraíso de Osorio', 'San Antonio Masahuat', 'San Emigdio', 'San Juan Tepezontes', 'San Luis La Herradura', 'San Pedro Nonualco', 'Santa María Ostuma', 'Santiago Nonualco']
                    ],
                    '0802' => [
                        'name' => 'La Paz Este',
                        'districts' => ['San Juan Nonualco', 'San Rafael Obrajuelo', 'Zacatecoluca']
                    ],
                    '0803' => [
                        'name' => 'La Paz Oeste',
                        'districts' => ['Cuyultitán', 'Olocuilta', 'San Juan Talpa', 'San Luis Talpa', 'San Pedro Masahuat', 'Tapalhuaca']
                    ],
                ]
            ],
            '09' => [
                'name' => 'Cabañas',
                'short' => 'CA',
                'municipalities' => [
                    '0901' => [
                        'name' => 'Cabañas Este',
                        'districts' => ['Sensuntepeque', 'Victoria', 'Dolores', 'Guacotecti', 'San Isidro']
                    ],
                    '0902' => [
                        'name' => 'Cabañas Oeste',
                        'districts' => ['Ilobasco', 'Tejutepeque', 'Jutiapa', 'Cinquera']
                    ],
                ]
            ],
            '10' => [
                'name' => 'San Vicente',
                'short' => 'SV',
                'municipalities' => [
                    '1001' => [
                        'name' => 'San Vicente Norte',
                        'districts' => ['Apastepeque', 'Santa Clara', 'San Ildefonso', 'San Esteban Catarina', 'San Sebastián', 'San Lorenzo']
                    ],
                    '1002' => [
                        'name' => 'San Vicente Sur',
                        'districts' => ['San Vicente', 'Guadalupe', 'San Cayetano Istepeque', 'Tecoluca', 'Tepetitán', 'Verapaz']
                    ],
                ]
            ],
            '11' => [
                'name' => 'Usulután',
                'short' => 'US',
                'municipalities' => [
                    '1101' => [
                        'name' => 'Usulután Norte',
                        'districts' => ['Santiago de María', 'Alegría', 'Berlín', 'El Triunfo', 'Estanzuelas', 'Jucuapa', 'Mercedes Umaña', 'Nueva Granada']
                    ],
                    '1102' => [
                        'name' => 'Usulután Centro',
                        'districts' => ['Usulután', 'Concepción Batres', 'San Dionisio', 'Santa Elena', 'Santa María', 'Tecapán', 'Jucuarán']
                    ],
                    '1103' => [
                        'name' => 'Usulután Sur',
                        'districts' => ['Jiquilisco', 'Puerto El Triunfo', 'San Agustín', 'San Francisco Javier']
                    ],
                ]
            ],
            '12' => [
                'name' => 'San Miguel',
                'short' => 'SM',
                'municipalities' => [
                    '1201' => [
                        'name' => 'San Miguel Norte',
                        'districts' => ['Ciudad Barrios', 'Sesori', 'Nuevo Edén de San Juan', 'San Gerardo', 'San Luis de la Reina', 'Carolina', 'San Antonio', 'Chapeltique']
                    ],
                    '1202' => [
                        'name' => 'San Miguel Centro',
                        'districts' => ['San Miguel', 'Comacarán', 'Lolotique', 'Moncagua', 'Quelepa', 'Chirilagua']
                    ],
                    '1203' => [
                        'name' => 'San Miguel Sur',
                        'districts' => ['El Tránsito', 'San Jorge', 'San Rafael Oriente', 'Chinameca', 'Nueva Guadalupe']
                    ],
                ]
            ],
            '13' => [
                'name' => 'Morazán',
                'short' => 'MO',
                'municipalities' => [
                    '1301' => [
                        'name' => 'Morazán Norte',
                        'districts' => ['Arambala', 'Cacaopera', 'Corinto', 'El Rosario', 'Joateca', 'Jocoaitique', 'Meanguera', 'Perquín', 'San Fernando', 'San Isidro', 'Torola']
                    ],
                    '1302' => [
                        'name' => 'Morazán Sur',
                        'districts' => ['San Francisco Gotera', 'Chilanga', 'Delicias de Concepción', 'El Divisadero', 'Gualococti', 'Guatajiagua', 'Jocoro', 'Osicala', 'San Carlos', 'San Esteban Sociedad', 'Sensembra', 'Yamabal', 'Yoloaiquín']
                    ],
                ]
            ],
            '14' => [
                'name' => 'La Unión',
                'short' => 'LU',
                'municipalities' => [
                    '1401' => [
                        'name' => 'La Unión Norte',
                        'districts' => ['Anamorós', 'Bolívar', 'Concepción de Oriente', 'El Sauce', 'Lislique', 'Nueva Esparta', 'Polorós', 'San José']
                    ],
                    '1402' => [
                        'name' => 'La Unión Sur',
                        'districts' => ['La Unión', 'Conchagua', 'El Carmen', 'Intipucá', 'Meanguera del Golfo', 'Pasaquina', 'San Alejo', 'Yayantique', 'Yucuaiquín']
                    ],
                ]
            ],
        ];

        // Recorrer e insertar datos de departamentos, municipios y distritos
        foreach ($data as $deptCode => $deptInfo) {
            $department = Department::firstOrCreate(
                ['code' => $deptCode],
                [
                    'name' => $deptInfo['name'],
                    'short_name' => $deptInfo['short']
                ]
            );

            foreach ($deptInfo['municipalities'] as $muniCode => $muniInfo) {
                $municipality = Municipality::firstOrCreate(
                    ['code' => $muniCode],
                    [
                        'department_id' => $department->id,
                        'name' => $muniInfo['name']
                    ]
                );

                foreach ($muniInfo['districts'] as $index => $districtName) {
                    $distCode = $muniCode . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                    District::firstOrCreate(
                        ['code' => $distCode],
                        [
                            'municipality_id' => $municipality->id,
                            'name' => $districtName
                        ]
                    );
                }
            }
        }
    }
}
