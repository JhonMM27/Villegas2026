<?php

declare(strict_types=1);

return [

    'horarios' => [
        'lunes_viernes' => [
            'dias' => [1, 2, 3, 4, 5],
            'entrada' => '08:00',
            'salida' => '18:00',
            'descanso_inicio' => '13:00',
            'descanso_fin' => '14:30',
        ],
        'sabado' => [
            'dias' => [6],
            'entrada' => '08:00',
            'salida' => '16:00',
            'descanso_inicio' => '13:00',
            'descanso_fin' => '14:00',
        ],
        'domingo' => [
            'dias' => [7],
            'laboral' => false,
        ],
    ],

    'tolerancias' => [
        'tardanza_minutos' => 5,
        'salida_temprana_minutos' => 5,
        'duplicado_minutos' => 2,
        'descanso_maximo_minutos' => 90,
    ],

    'clasificacion_franjas' => [
        'entrada' => ['inicio' => '05:00', 'fin' => '10:30'],
        'salida_descanso' => ['inicio' => '11:00', 'fin' => '14:00'],
        'entrada_descanso' => ['inicio' => '13:00', 'fin' => '15:30'],
        'salida' => ['inicio' => '14:30', 'fin' => '20:00'],
    ],

    'clasificacion_labels' => [
        'entrada' => 'Entrada',
        'salida' => 'Salida',
        'salida_descanso' => 'Salida descanso',
        'entrada_descanso' => 'Entrada descanso',
        'extra' => 'Extra',
    ],

    'clasificacion_colores' => [
        'entrada' => 'success',
        'salida' => 'danger',
        'salida_descanso' => 'warning',
        'entrada_descanso' => 'info',
        'extra' => 'secondary',
    ],

    'push' => [
        'enable_device_commands' => true,
        'log_raw_body' => true,
        'max_body_kb' => 5120,
    ],
];
