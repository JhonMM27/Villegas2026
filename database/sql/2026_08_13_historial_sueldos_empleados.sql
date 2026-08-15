-- Historial salarial de empleados
-- Ejecutar primero este archivo y desplegar inmediatamente el código asociado.
-- Compatible con MySQL 8 y con el analizador estÃ¡tico de phpMyAdmin.

-- 1. Respaldo recuperable de los importes que actualmente viven en empleados.
CREATE TABLE IF NOT EXISTS empleados_sueldos_respaldo_20260813 (
    empleado_id BIGINT UNSIGNED NOT NULL,
    sueldo_planilla DECIMAL(10, 2) NOT NULL,
    sueldo_real DECIMAL(10, 2) NOT NULL,
    fecha_ingreso DATE NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    respaldado_at TIMESTAMP NOT NULL,
    PRIMARY KEY (empleado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO empleados_sueldos_respaldo_20260813 (
    empleado_id,
    sueldo_planilla,
    sueldo_real,
    fecha_ingreso,
    created_at,
    respaldado_at
)
SELECT id,
       sueldo_planilla,
       sueldo_real,
       fecha_ingreso,
       created_at,
       NOW()
FROM empleados;

-- 2. Nueva fuente de verdad salarial.
CREATE TABLE empleado_sueldos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empleado_id BIGINT UNSIGNED NOT NULL,
    sueldo_base DECIMAL(10, 2) UNSIGNED NOT NULL,
    sueldo_real DECIMAL(10, 2) UNSIGNED NOT NULL,
    sueldo_planilla DECIMAL(10, 2) UNSIGNED NOT NULL,
    vigente_desde DATE NOT NULL,
    vigente_hasta DATE NULL,
    motivo VARCHAR(255) NULL,
    observaciones TEXT NULL,
    -- users.id en esta base de producciÃ³n es SMALLINT UNSIGNED.
    -- Una FK exige que ambos tipos coincidan exactamente.
    registrado_por SMALLINT UNSIGNED NULL,
    vigente_actual TINYINT GENERATED ALWAYS AS (
        CASE WHEN vigente_hasta IS NULL THEN 1 ELSE NULL END
    ) STORED,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY empleado_sueldos_empleado_fecha_unique (empleado_id, vigente_desde),
    UNIQUE KEY empleado_sueldos_un_vigente_unique (empleado_id, vigente_actual),
    KEY empleado_sueldos_vigencia_index (empleado_id, vigente_desde, vigente_hasta),
    KEY empleado_sueldos_registrado_por_foreign (registrado_por),
    CONSTRAINT empleado_sueldos_empleado_foreign
        FOREIGN KEY (empleado_id) REFERENCES empleados (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT empleado_sueldos_registrado_por_foreign
        FOREIGN KEY (registrado_por) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Carga de TODOS los empleados existentes al momento de ejecutar el script.
-- El sueldo base inicial conserva la definición actual del sistema:
-- sueldo real menos sueldo de planilla.
INSERT INTO empleado_sueldos (
    empleado_id,
    sueldo_base,
    sueldo_real,
    sueldo_planilla,
    vigente_desde,
    vigente_hasta,
    motivo,
    observaciones,
    registrado_por,
    created_at,
    updated_at
)
SELECT e.id,
       GREATEST(e.sueldo_real - e.sueldo_planilla, 0),
       e.sueldo_real,
       e.sueldo_planilla,
       COALESCE(e.fecha_ingreso, DATE(e.created_at), CURRENT_DATE),
       NULL,
       'Carga inicial desde empleados',
       'Registro generado por el script de implementación del historial salarial',
       NULL,
       NOW(),
       NOW()
FROM empleados e
ORDER BY e.id;

-- La siguiente consulta debe devolver la misma cantidad en ambas columnas.
SELECT (SELECT COUNT(*) FROM empleados) AS empleados,
       (SELECT COUNT(*) FROM empleado_sueldos) AS sueldos_cargados;

-- 4. Vincular cada pago con la versión salarial usada.
ALTER TABLE planilla_pagos
    ADD COLUMN empleado_sueldo_id BIGINT UNSIGNED NULL AFTER empleado_id,
    ADD KEY planilla_pagos_empleado_sueldo_index (empleado_sueldo_id),
    ADD CONSTRAINT planilla_pagos_empleado_sueldo_foreign
        FOREIGN KEY (empleado_sueldo_id) REFERENCES empleado_sueldos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

UPDATE planilla_pagos pp
INNER JOIN empleado_sueldos es ON es.empleado_id = pp.empleado_id
    AND es.vigente_actual = 1
SET pp.empleado_sueldo_id = es.id
WHERE pp.empleado_sueldo_id IS NULL;

-- Debe devolver cero antes de continuar.
SELECT COUNT(*) AS pagos_sin_sueldo
FROM planilla_pagos
WHERE empleado_sueldo_id IS NULL;

ALTER TABLE planilla_pagos
    MODIFY COLUMN empleado_sueldo_id BIGINT UNSIGNED NOT NULL;

-- 5. Los importes dejan de almacenarse en empleados.
-- El respaldo creado al inicio permite recuperar los valores si fuera necesario.
ALTER TABLE empleados
    DROP COLUMN sueldo_planilla,
    DROP COLUMN sueldo_real;

-- Verificación final.
SELECT e.id,
       e.nombre,
       es.sueldo_base,
       es.sueldo_real,
       es.sueldo_planilla,
       es.vigente_desde,
       es.vigente_hasta
FROM empleados e
INNER JOIN empleado_sueldos es ON es.empleado_id = e.id
WHERE es.vigente_actual = 1
ORDER BY e.id;
