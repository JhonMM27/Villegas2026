<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredienteDatoNutricionalSeeder extends Seeder
{
    public function run(): void
    {
        $datos = [
            ['ingrediente' => 'Maiz amarillo molido',         'procedencia' => 'Nacional',  'clasificacion' => 'Energetico',            'nutriente' => 'Almidon',                                          'materia_seca' => 86,   'proteina_cruda' => 8.5,   'enl' => 2.02, 'fdn' => 14,  'grasa' => 3,  'almidon' => 68, 'azucar' => 3],
            ['ingrediente' => 'Maiz amarillo molido',         'procedencia' => 'Importado', 'clasificacion' => 'Energetico',            'nutriente' => 'Almidon',                                          'materia_seca' => 88,   'proteina_cruda' => 8.2,   'enl' => 2.1,  'fdn' => 12,  'grasa' => 3,  'almidon' => 70, 'azucar' => 4],
            ['ingrediente' => 'Nelen',                        'procedencia' => 'Nacional',  'clasificacion' => 'Energetico',            'nutriente' => 'Almidon',                                          'materia_seca' => 88,   'proteina_cruda' => 8.2,   'enl' => 1.98, 'fdn' => 15,  'grasa' => 4,  'almidon' => 62, 'azucar' => 1],
            ['ingrediente' => 'Torta de Soya 44 %',           'procedencia' => 'Importado', 'clasificacion' => 'Proteico',              'nutriente' => 'Proteina',                                         'materia_seca' => 90,   'proteina_cruda' => 48.89, 'enl' => 1.76, 'fdn' => 10,  'grasa' => 2,  'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Torta de Soya 46 %',           'procedencia' => 'Importado', 'clasificacion' => 'Proteico',              'nutriente' => 'Proteina',                                         'materia_seca' => 90,   'proteina_cruda' => 51.11, 'enl' => 1.76, 'fdn' => 9,   'grasa' => 2,  'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Torta de Soya 48 %',           'procedencia' => 'Importado', 'clasificacion' => 'Proteico',              'nutriente' => 'Proteina',                                         'materia_seca' => 90,   'proteina_cruda' => 53.33, 'enl' => 1.78, 'fdn' => 8,   'grasa' => 2,  'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Harina integral de Soya',      'procedencia' => 'Importado', 'clasificacion' => 'Energetico/Proteico',  'nutriente' => 'Grasa/Proteina',                                   'materia_seca' => 89,   'proteina_cruda' => 36,    'enl' => 2.45, 'fdn' => null, 'grasa' => 20, 'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Polvillo de Arroz',            'procedencia' => 'Nacional',  'clasificacion' => 'Subproducto',          'nutriente' => 'Grasa',                                            'materia_seca' => 90,   'proteina_cruda' => 12,    'enl' => 1.64, 'fdn' => 18,  'grasa' => 14, 'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Afrecho de trigo',             'procedencia' => 'Nacional',  'clasificacion' => 'Subproducto',          'nutriente' => 'Fibra/Proteina',                                   'materia_seca' => 90,   'proteina_cruda' => 16,    'enl' => 1.51, 'fdn' => 27,  'grasa' => 1,  'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Menestra',                     'procedencia' => 'Nacional',  'clasificacion' => 'Subproducto',          'nutriente' => 'Proteina',                                         'materia_seca' => 92,   'proteina_cruda' => 20,    'enl' => 1.56, 'fdn' => 15,  'grasa' => 4,  'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Melaza de cana',               'procedencia' => 'Nacional',  'clasificacion' => 'Energetico',            'nutriente' => 'Azucares',                                         'materia_seca' => 64,   'proteina_cruda' => 5,     'enl' => 1.96, 'fdn' => null, 'grasa' => null, 'almidon' => null, 'azucar' => 56],
            ['ingrediente' => 'Torta Palmiste',               'procedencia' => 'Nacional',  'clasificacion' => 'Subproducto',          'nutriente' => 'Proteina',                                         'materia_seca' => 92,   'proteina_cruda' => 13,    'enl' => 1.4,  'fdn' => 34,  'grasa' => 5,  'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Harina Palmiste',              'procedencia' => 'Nacional',  'clasificacion' => 'Subproducto',          'nutriente' => 'Grasa',                                            'materia_seca' => 90,   'proteina_cruda' => 11,    'enl' => 1.54, 'fdn' => 27,  'grasa' => 8,  'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Urea Comun',                   'procedencia' => 'Importado', 'clasificacion' => 'Nitrogeno No proteico', 'nutriente' => 'Amoniaco',                                         'materia_seca' => 99,   'proteina_cruda' => 276,   'enl' => null, 'fdn' => null, 'grasa' => null, 'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Carbonato Calcio',             'procedencia' => 'Nacional',  'clasificacion' => 'Minerales',             'nutriente' => 'Calcio',                                           'materia_seca' => 99.5, 'proteina_cruda' => null,  'enl' => null, 'fdn' => null, 'grasa' => null, 'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Sal comun',                    'procedencia' => 'Nacional',  'clasificacion' => 'Minerales',             'nutriente' => 'Cloro / Sodio',                                    'materia_seca' => 99.5, 'proteina_cruda' => null,  'enl' => null, 'fdn' => null, 'grasa' => null, 'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Bicarbonato de Sodio',         'procedencia' => 'Nacional',  'clasificacion' => 'Minerales',             'nutriente' => 'Sodio',                                            'materia_seca' => 99.5, 'proteina_cruda' => null,  'enl' => null, 'fdn' => null, 'grasa' => null, 'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Oxido de Magnesio',            'procedencia' => 'Importado', 'clasificacion' => 'Minerales',             'nutriente' => 'Magnesio',                                         'materia_seca' => 99.5, 'proteina_cruda' => null,  'enl' => null, 'fdn' => null, 'grasa' => null, 'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Premezcla VIT-MIN',            'procedencia' => 'Importado', 'clasificacion' => 'Vitaminas /Minerales',  'nutriente' => 'Vit A , E , D , Minerales Se , zinc , P , Fe , cu , co , Mn', 'materia_seca' => 99.5, 'proteina_cruda' => null, 'enl' => null, 'fdn' => null, 'grasa' => null, 'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Sales Minerales',              'procedencia' => 'Importado', 'clasificacion' => 'Minerales',             'nutriente' => 'Ca, P , Mn , Fe , Se , Zn , Cu , Co',              'materia_seca' => 99.5, 'proteina_cruda' => null,  'enl' => null, 'fdn' => null, 'grasa' => null, 'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Secuestrante de Micotoxinas',  'procedencia' => 'Importado', 'clasificacion' => 'Minerales',             'nutriente' => null,                                                 'materia_seca' => 99.5, 'proteina_cruda' => null,  'enl' => null, 'fdn' => null, 'grasa' => null, 'almidon' => null, 'azucar' => null],
            ['ingrediente' => 'Acido Propionico',            'procedencia' => 'Importado', 'clasificacion' => 'Acido graso',           'nutriente' => null,                                                 'materia_seca' => 99,   'proteina_cruda' => null,  'enl' => null, 'fdn' => null, 'grasa' => null, 'almidon' => null, 'azucar' => null],
        ];

        foreach ($datos as $d) {
            DB::table('ingrediente_datos_nutricionales')->updateOrInsert(
                ['ingrediente' => $d['ingrediente'], 'procedencia' => $d['procedencia']],
                array_merge($d, ['activo' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
