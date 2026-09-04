-- CONSORCIOS VILLEGAS - Corrección de cierre de planilla/caja de agosto 2026
-- Compatible con phpMyAdmin: no usa PROCEDURE, DELIMITER, DECLARE ni RESIGNAL.
-- Hacer una copia de seguridad antes de ejecutar.

-- 1. ESTRUCTURA. DDL condicional compatible con versiones que no admiten ADD COLUMN IF NOT EXISTS.
SET @ddl := IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'planilla_pagos' AND column_name = 'fecha_pago_original'),
    'SELECT 1',
    'ALTER TABLE planilla_pagos ADD COLUMN fecha_pago_original DATE NULL AFTER fecha_pago'
);
PREPARE planilla_stmt FROM @ddl;
EXECUTE planilla_stmt;
DEALLOCATE PREPARE planilla_stmt;

SET @ddl := IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'planilla_pagos' AND column_name = 'anulado_at'),
    'SELECT 1',
    'ALTER TABLE planilla_pagos ADD COLUMN anulado_at TIMESTAMP NULL AFTER estado'
);
PREPARE planilla_stmt FROM @ddl;
EXECUTE planilla_stmt;
DEALLOCATE PREPARE planilla_stmt;

SET @ddl := IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'planilla_pagos' AND column_name = 'anulado_por'),
    'SELECT 1',
    'ALTER TABLE planilla_pagos ADD COLUMN anulado_por SMALLINT UNSIGNED NULL AFTER anulado_at'
);
PREPARE planilla_stmt FROM @ddl;
EXECUTE planilla_stmt;
DEALLOCATE PREPARE planilla_stmt;

SET @ddl := IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'planilla_pagos' AND column_name = 'motivo_anulacion'),
    'SELECT 1',
    'ALTER TABLE planilla_pagos ADD COLUMN motivo_anulacion VARCHAR(500) NULL AFTER anulado_por'
);
PREPARE planilla_stmt FROM @ddl;
EXECUTE planilla_stmt;
DEALLOCATE PREPARE planilla_stmt;

ALTER TABLE planilla_pagos
    MODIFY COLUMN estado ENUM('pendiente', 'pagado', 'anulado') NOT NULL DEFAULT 'pendiente';

UPDATE planilla_pagos
SET fecha_pago_original = fecha_pago
WHERE estado = 'pagado'
  AND fecha_pago IS NOT NULL
  AND fecha_pago_original IS NULL;

CREATE TABLE IF NOT EXISTS planilla_pago_movimientos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    planilla_pago_id BIGINT UNSIGNED NOT NULL,
    accion VARCHAR(30) NOT NULL,
    estado_anterior VARCHAR(20) NOT NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    fecha_pago_anterior DATE NULL,
    fecha_pago_nueva DATE NULL,
    motivo VARCHAR(500) NULL,
    user_id SMALLINT UNSIGNED NULL,
    user_nombre VARCHAR(100) NOT NULL DEFAULT 'Sistema',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX pago_movimientos_pago_fecha_index (planilla_pago_id, created_at),
    INDEX pago_movimientos_accion_fecha_index (accion, created_at),
    CONSTRAINT planilla_pago_movimientos_planilla_pago_id_foreign
        FOREIGN KEY (planilla_pago_id) REFERENCES planilla_pagos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. IDENTIFICACIÓN Y VALIDACIÓN DE LOS CASOS.
SET @victor_empleado_id := (
    SELECT MAX(id) FROM empleados
    WHERE dni = '71837739' AND nombre = 'VICTOR KEVIN SALAZAR ASPAJO'
);

SET @rene_empleado_id := (
    SELECT MAX(id) FROM empleados
    WHERE dni = '48670033'
      AND nombre = 'RENE VILLEGAS GUEVARA'
      AND estado = 'inactivo'
      AND fecha_salida = '2026-07-30'
);

SET @victor_pago_id := (
    SELECT MAX(id) FROM planilla_pagos
    WHERE empleado_id = @victor_empleado_id
      AND mes = 8 AND anio = 2026
      AND estado = 'pagado' AND total_pagar = 840.00
);

SET @rene_pago_id := (
    SELECT MAX(id) FROM planilla_pagos
    WHERE empleado_id = @rene_empleado_id
      AND mes = 8 AND anio = 2026
      AND estado IN ('pagado', 'anulado') AND total_pagar = 2000.00
);

SET @victor_fecha_anterior := (
    SELECT fecha_pago FROM planilla_pagos WHERE id = @victor_pago_id
);
SET @victor_fecha_original_anterior := (
    SELECT fecha_pago_original FROM planilla_pagos WHERE id = @victor_pago_id
);
SET @rene_fecha_anterior := (
    SELECT fecha_pago FROM planilla_pagos WHERE id = @rene_pago_id
);
SET @rene_estado_anterior := (
    SELECT estado FROM planilla_pagos WHERE id = @rene_pago_id
);

-- Este resultado debe decir LISTO_PARA_CORREGIR.
SELECT CASE
    WHEN @victor_empleado_id IS NULL THEN 'ERROR: no se encontró a Víctor por DNI y nombre'
    WHEN @rene_empleado_id IS NULL THEN 'ERROR: René no está inactivo con salida 30/07/2026'
    WHEN @victor_pago_id IS NULL THEN 'ERROR: no se encontró el pago pagado de Víctor por S/ 840'
    WHEN @rene_pago_id IS NULL THEN 'ERROR: no se encontró el pago de René por S/ 2,000'
    ELSE 'LISTO_PARA_CORREGIR'
END AS validacion_previa;

-- 3. CORRECCIÓN TRANSACCIONAL. Los WHERE impiden modificar casos que no coincidan.
START TRANSACTION;

UPDATE planilla_pagos
SET fecha_pago = '2026-08-18',
    fecha_pago_original = '2026-08-18',
    updated_at = NOW()
WHERE id = @victor_pago_id
  AND estado = 'pagado'
  AND total_pagar = 840.00
  AND (
      fecha_pago <> '2026-08-18'
      OR fecha_pago_original IS NULL
      OR fecha_pago_original <> '2026-08-18'
  );

INSERT INTO planilla_pago_movimientos (
    planilla_pago_id, accion, estado_anterior, estado_nuevo,
    fecha_pago_anterior, fecha_pago_nueva, motivo,
    user_nombre, created_at, updated_at
)
SELECT
    @victor_pago_id, 'rectificacion_fecha', 'pagado', 'pagado',
    @victor_fecha_anterior, '2026-08-18',
    'Restauración de la fecha original después de una reversión masiva',
    'Script producción', NOW(), NOW()
WHERE @victor_pago_id IS NOT NULL
  AND (
      @victor_fecha_anterior <> '2026-08-18'
      OR @victor_fecha_original_anterior IS NULL
      OR @victor_fecha_original_anterior <> '2026-08-18'
  )
  AND NOT EXISTS (
      SELECT 1 FROM planilla_pago_movimientos
      WHERE planilla_pago_id = @victor_pago_id
        AND accion = 'rectificacion_fecha'
        AND fecha_pago_nueva = '2026-08-18'
        AND motivo = 'Restauración de la fecha original después de una reversión masiva'
  );

UPDATE planilla_pagos
SET fecha_pago_original = COALESCE(fecha_pago_original, fecha_pago),
    fecha_pago = NULL,
    estado = 'anulado',
    anulado_at = NOW(),
    anulado_por = NULL,
    motivo_anulacion = 'Pago improcedente de agosto: el empleado salió el 30/07/2026',
    updated_at = NOW()
WHERE id = @rene_pago_id
  AND estado = 'pagado'
  AND total_pagar = 2000.00;

INSERT INTO planilla_pago_movimientos (
    planilla_pago_id, accion, estado_anterior, estado_nuevo,
    fecha_pago_anterior, fecha_pago_nueva, motivo,
    user_nombre, created_at, updated_at
)
SELECT
    @rene_pago_id, 'anulacion', @rene_estado_anterior, 'anulado',
    @rene_fecha_anterior, NULL,
    'Pago improcedente de agosto: el empleado salió el 30/07/2026',
    'Script producción', NOW(), NOW()
WHERE @rene_pago_id IS NOT NULL
  AND @rene_estado_anterior = 'pagado'
  AND NOT EXISTS (
      SELECT 1 FROM planilla_pago_movimientos
      WHERE planilla_pago_id = @rene_pago_id
        AND accion = 'anulacion'
        AND motivo = 'Pago improcedente de agosto: el empleado salió el 30/07/2026'
  );

SET @total_planilla_agosto := (
    SELECT COALESCE(SUM(es.sueldo_real), 0)
    FROM planilla_pagos pp
    LEFT JOIN empleado_sueldos es ON es.id = pp.empleado_sueldo_id
    WHERE pp.mes = 8 AND pp.anio = 2026 AND pp.estado = 'pagado'
);

SET @ultima_fecha_pago_agosto := (
    SELECT MAX(fecha_pago) FROM planilla_pagos
    WHERE mes = 8 AND anio = 2026 AND estado = 'pagado'
);

UPDATE gastos
SET monto = @total_planilla_agosto,
    importe_p = @total_planilla_agosto,
    importe_d = 0,
    importe_c = 0,
    fecha_gasto = @ultima_fecha_pago_agosto,
    descripcion = 'Pago Empleados mes Agosto/2026',
    updated_at = NOW()
WHERE planilla_mes = 8
  AND planilla_anio = 2026
  AND empleado_id IS NULL;

COMMIT;

-- 4. RESULTADOS: Víctor pagado el 18/08 y René anulado sin fecha efectiva.
SELECT
    pp.id, e.dni, e.nombre, pp.mes, pp.anio, pp.estado,
    pp.fecha_pago, pp.fecha_pago_original, pp.total_pagar, pp.motivo_anulacion
FROM planilla_pagos pp
INNER JOIN empleados e ON e.id = pp.empleado_id
WHERE pp.id IN (@victor_pago_id, @rene_pago_id)
ORDER BY pp.id;

-- En los datos auditados: un consolidado por S/ 49,900.00.
SELECT
    COUNT(*) AS cantidad_consolidados,
    MAX(id) AS gasto_id,
    MAX(fecha_gasto) AS fecha_gasto,
    MAX(monto) AS monto,
    MAX(importe_p) AS importe_p,
    MAX(importe_d) AS importe_d,
    MAX(importe_c) AS importe_c
FROM gastos
WHERE planilla_mes = 8
  AND planilla_anio = 2026
  AND empleado_id IS NULL;

-- Debe devolver cero filas: pagos no anulados fuera de la nómina elegible de agosto.
SELECT pp.id, e.dni, e.nombre, e.estado, e.fecha_salida, pp.estado AS estado_pago
FROM planilla_pagos pp
INNER JOIN empleados e ON e.id = pp.empleado_id
WHERE pp.mes = 8
  AND pp.anio = 2026
  AND pp.estado <> 'anulado'
  AND (
      e.estado <> 'activo'
      OR (e.fecha_salida IS NOT NULL AND e.fecha_salida < '2026-08-01')
  );
