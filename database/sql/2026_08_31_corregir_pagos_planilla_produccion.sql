-- Corrección de pagos de planilla - agosto 2026
-- Ejecutar DESPUÉS de desplegar el código de la aplicación.
-- Compatible con MySQL 8 / phpMyAdmin. Es transaccional e idempotente.

DELIMITER $$

DROP PROCEDURE IF EXISTS corregir_pagos_planilla_agosto_2026$$

CREATE PROCEDURE corregir_pagos_planilla_agosto_2026()
BEGIN
    DECLARE v_kevin_id BIGINT UNSIGNED;
    DECLARE v_augusto_id BIGINT UNSIGNED;
    DECLARE v_ercli_id BIGINT UNSIGNED;
    DECLARE v_kevin_pago_id BIGINT UNSIGNED;
    DECLARE v_augusto_pago_id BIGINT UNSIGNED;
    DECLARE v_ercli_pago_id BIGINT UNSIGNED;
    DECLARE v_kevin_sueldo_id BIGINT UNSIGNED;
    DECLARE v_augusto_sueldo_id BIGINT UNSIGNED;
    DECLARE v_total INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT id INTO v_kevin_id
    FROM empleados
    WHERE dni = '72985795' AND nombre = 'KEVIN IPANAQUE VASQUEZ'
    LIMIT 1;

    SELECT id INTO v_augusto_id
    FROM empleados
    WHERE dni = '71173143' AND nombre = 'AUGUSTO LOPEZ JIMENEZ'
    LIMIT 1;

    SELECT id INTO v_ercli_id
    FROM empleados
    WHERE dni = '60155210' AND nombre = 'ERCLI RENAN CUSMA IRIGOIN'
    LIMIT 1;

    IF v_kevin_id IS NULL OR v_augusto_id IS NULL OR v_ercli_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se encontraron exactamente los empleados esperados por DNI y nombre';
    END IF;

    SELECT COUNT(*), MAX(id), MAX(empleado_sueldo_id)
    INTO v_total, v_kevin_pago_id, v_kevin_sueldo_id
    FROM planilla_pagos
    WHERE empleado_id = v_kevin_id AND mes = 8 AND anio = 2026 AND estado = 'pendiente';

    IF v_total <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Kevin no tiene exactamente un pago pendiente en agosto de 2026';
    END IF;

    SELECT COUNT(*), MAX(id), MAX(empleado_sueldo_id)
    INTO v_total, v_augusto_pago_id, v_augusto_sueldo_id
    FROM planilla_pagos
    WHERE empleado_id = v_augusto_id AND mes = 8 AND anio = 2026 AND estado = 'pendiente';

    IF v_total <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Augusto no tiene exactamente un pago pendiente en agosto de 2026';
    END IF;

    SELECT COUNT(*), MAX(id)
    INTO v_total, v_ercli_pago_id
    FROM planilla_pagos
    WHERE empleado_id = v_ercli_id AND mes = 8 AND anio = 2026 AND estado = 'pendiente';

    IF v_total <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ercli no tiene exactamente un pago pendiente en agosto de 2026';
    END IF;

    START TRANSACTION;

    -- Auditoría previa de las versiones de Kevin que fueron creadas en agosto.
    INSERT INTO auditoria_eventos (
        tipo_evento, modulo, registro_id, numero_rectificacion,
        registro_referencia, user_id, user_nombre, motivo,
        datos_anteriores, datos_nuevos, cambios, created_at, updated_at
    )
    SELECT
        'rectificacion',
        'empleado_sueldos',
        es.id,
        1,
        CONCAT(e.nombre, ' - ', DATE_FORMAT(es.vigente_desde, '%Y-%m-%d')),
        NULL,
        'Script producción',
        'Corrección de aumento erróneo de agosto 2026',
        JSON_OBJECT(
            'cabecera', JSON_OBJECT(
                'sueldo_real', JSON_OBJECT('etiqueta', 'Sueldo real', 'formato', 'moneda', 'valor', es.sueldo_real),
                'sueldo_planilla', JSON_OBJECT('etiqueta', 'Sueldo planilla', 'formato', 'moneda', 'valor', es.sueldo_planilla),
                'sueldo_base', JSON_OBJECT('etiqueta', 'Sueldo no planilla', 'formato', 'moneda', 'valor', es.sueldo_base),
                'vigente_desde', JSON_OBJECT('etiqueta', 'Vigente desde', 'formato', 'fecha', 'valor', es.vigente_desde),
                'vigente_hasta', JSON_OBJECT('etiqueta', 'Vigente hasta', 'formato', 'fecha', 'valor', es.vigente_hasta)
            ),
            'detalles', JSON_ARRAY()
        ),
        JSON_OBJECT(
            'cabecera', JSON_OBJECT(
                'sueldo_real', JSON_OBJECT('etiqueta', 'Sueldo real', 'formato', 'moneda', 'valor', 1900.00),
                'sueldo_planilla', JSON_OBJECT('etiqueta', 'Sueldo planilla', 'formato', 'moneda', 'valor', 0.00),
                'sueldo_base', JSON_OBJECT('etiqueta', 'Sueldo no planilla', 'formato', 'moneda', 'valor', 1900.00),
                'vigente_desde', JSON_OBJECT('etiqueta', 'Vigente desde', 'formato', 'fecha', 'valor', IF(es.id = v_kevin_sueldo_id, '2026-08-01', es.vigente_desde)),
                'vigente_hasta', JSON_OBJECT('etiqueta', 'Vigente hasta', 'formato', 'fecha', 'valor', es.vigente_hasta)
            ),
            'detalles', JSON_ARRAY()
        ),
        JSON_OBJECT(
            'cabecera', JSON_ARRAY(
                JSON_OBJECT('campo', 'Sueldo real', 'anterior', es.sueldo_real, 'nuevo', 1900.00, 'tipo', 'modificado', 'formato', 'moneda'),
                JSON_OBJECT('campo', 'Sueldo no planilla', 'anterior', es.sueldo_base, 'nuevo', 1900.00, 'tipo', 'modificado', 'formato', 'moneda')
            ),
            'detalles', JSON_ARRAY()
        ),
        NOW(),
        NOW()
    FROM empleado_sueldos es
    INNER JOIN empleados e ON e.id = es.empleado_id
    WHERE es.empleado_id = v_kevin_id
      AND es.vigente_desde BETWEEN '2026-08-01' AND '2026-08-31'
      AND (es.sueldo_real <> 1900 OR es.sueldo_planilla <> 0 OR es.sueldo_base <> 1900
           OR (es.id = v_kevin_sueldo_id AND es.vigente_desde <> '2026-08-01'))
      AND NOT EXISTS (
          SELECT 1
          FROM auditoria_eventos ae
          WHERE ae.modulo = 'empleado_sueldos'
            AND ae.registro_id = es.id
            AND ae.motivo = 'Corrección de aumento erróneo de agosto 2026'
      );

    -- Auditoría previa de Augusto.
    INSERT INTO auditoria_eventos (
        tipo_evento, modulo, registro_id, numero_rectificacion,
        registro_referencia, user_id, user_nombre, motivo,
        datos_anteriores, datos_nuevos, cambios, created_at, updated_at
    )
    SELECT
        'rectificacion',
        'empleado_sueldos',
        es.id,
        1,
        CONCAT(e.nombre, ' - ', DATE_FORMAT(es.vigente_desde, '%Y-%m-%d')),
        NULL,
        'Script producción',
        'Corrección del sueldo no planilla de agosto 2026',
        JSON_OBJECT(
            'cabecera', JSON_OBJECT(
                'sueldo_real', JSON_OBJECT('etiqueta', 'Sueldo real', 'formato', 'moneda', 'valor', es.sueldo_real),
                'sueldo_planilla', JSON_OBJECT('etiqueta', 'Sueldo planilla', 'formato', 'moneda', 'valor', es.sueldo_planilla),
                'sueldo_base', JSON_OBJECT('etiqueta', 'Sueldo no planilla', 'formato', 'moneda', 'valor', es.sueldo_base),
                'vigente_desde', JSON_OBJECT('etiqueta', 'Vigente desde', 'formato', 'fecha', 'valor', es.vigente_desde)
            ),
            'detalles', JSON_ARRAY()
        ),
        JSON_OBJECT(
            'cabecera', JSON_OBJECT(
                'sueldo_real', JSON_OBJECT('etiqueta', 'Sueldo real', 'formato', 'moneda', 'valor', 1900.00),
                'sueldo_planilla', JSON_OBJECT('etiqueta', 'Sueldo planilla', 'formato', 'moneda', 'valor', 1130.00),
                'sueldo_base', JSON_OBJECT('etiqueta', 'Sueldo no planilla', 'formato', 'moneda', 'valor', 770.00),
                'vigente_desde', JSON_OBJECT('etiqueta', 'Vigente desde', 'formato', 'fecha', 'valor', '2026-08-01')
            ),
            'detalles', JSON_ARRAY()
        ),
        JSON_OBJECT(
            'cabecera', JSON_ARRAY(
                JSON_OBJECT('campo', 'Sueldo no planilla', 'anterior', es.sueldo_base, 'nuevo', 770.00, 'tipo', 'modificado', 'formato', 'moneda'),
                JSON_OBJECT('campo', 'Vigente desde', 'anterior', es.vigente_desde, 'nuevo', '2026-08-01', 'tipo', 'modificado', 'formato', 'fecha')
            ),
            'detalles', JSON_ARRAY()
        ),
        NOW(),
        NOW()
    FROM empleado_sueldos es
    INNER JOIN empleados e ON e.id = es.empleado_id
    WHERE es.id = v_augusto_sueldo_id
      AND (es.sueldo_real <> 1900 OR es.sueldo_planilla <> 1130 OR es.sueldo_base <> 770
           OR es.vigente_desde <> '2026-08-01')
      AND NOT EXISTS (
          SELECT 1
          FROM auditoria_eventos ae
          WHERE ae.modulo = 'empleado_sueldos'
            AND ae.registro_id = es.id
            AND ae.motivo = 'Corrección del sueldo no planilla de agosto 2026'
      );

    -- Cerrar correctamente la versión anterior de cada empleado.
    UPDATE empleado_sueldos
    SET vigente_hasta = '2026-07-31', updated_at = NOW()
    WHERE empleado_id IN (v_kevin_id, v_augusto_id)
      AND vigente_desde < '2026-08-01'
      AND (vigente_hasta IS NULL OR vigente_hasta >= '2026-08-01');

    -- Kevin: corregir todas las versiones creadas en agosto y conservar sus IDs.
    UPDATE empleado_sueldos
    SET sueldo_real = 1900.00,
        sueldo_planilla = 0.00,
        sueldo_base = 1900.00,
        motivo = 'Corrección de aumento erróneo de agosto 2026',
        observaciones = 'Rectificación controlada del incidente reportado el 31/08/2026',
        updated_at = NOW()
    WHERE empleado_id = v_kevin_id
      AND vigente_desde BETWEEN '2026-08-01' AND '2026-08-31';

    UPDATE empleado_sueldos
    SET vigente_desde = '2026-08-01',
        sueldo_real = 1900.00,
        sueldo_planilla = 0.00,
        sueldo_base = 1900.00,
        updated_at = NOW()
    WHERE id = v_kevin_sueldo_id;

    -- Augusto: sueldo no planilla = 1900 - 1130 = 770.
    UPDATE empleado_sueldos
    SET vigente_desde = '2026-08-01',
        sueldo_real = 1900.00,
        sueldo_planilla = 1130.00,
        sueldo_base = 770.00,
        motivo = 'Corrección del sueldo no planilla de agosto 2026',
        observaciones = 'Sueldo no planilla derivado: 1900 - 1130 = 770',
        updated_at = NOW()
    WHERE id = v_augusto_sueldo_id;

    -- Recalcular todos los pagos pendientes con la misma regla de la aplicación.
    DROP TEMPORARY TABLE IF EXISTS tmp_planilla_recalculo;
    CREATE TEMPORARY TABLE tmp_planilla_recalculo AS
    SELECT
        pp.id,
        es.id AS empleado_sueldo_id,
        ROUND(SUM(CASE
            WHEN YEAR(pa.fecha) = pp.anio AND MONTH(pa.fecha) = pp.mes THEN pa.monto
            ELSE 0
        END), 2) AS adelantos,
        GREATEST(
            ROUND(
                GREATEST(es.sueldo_real - es.sueldo_planilla, 0)
                * GREATEST(
                    0,
                    LEAST(30,
                        (CASE
                            WHEN e.fecha_salida IS NOT NULL
                             AND YEAR(e.fecha_salida) = pp.anio
                             AND MONTH(e.fecha_salida) = pp.mes
                            THEN LEAST(DAY(e.fecha_salida), 30)
                            ELSE 30
                         END)
                        -
                        (CASE
                            WHEN e.fecha_ingreso IS NOT NULL
                             AND YEAR(e.fecha_ingreso) = pp.anio
                             AND MONTH(e.fecha_ingreso) = pp.mes
                            THEN LEAST(DAY(e.fecha_ingreso), 30)
                            ELSE 1
                         END)
                        + 1
                    )
                ) / 30,
                2
            )
            - ROUND(SUM(CASE
                WHEN YEAR(pa.fecha) = pp.anio AND MONTH(pa.fecha) = pp.mes THEN pa.monto
                ELSE 0
            END), 2),
            0
        ) AS disponible,
        ROUND(pp.dias_faltados * (es.sueldo_real / 30), 2) AS descuento_faltas,
        ROUND(
            GREATEST(
                ROUND(
                    GREATEST(es.sueldo_real - es.sueldo_planilla, 0)
                    * GREATEST(
                        0,
                        LEAST(30,
                            (CASE
                                WHEN e.fecha_salida IS NOT NULL
                                 AND YEAR(e.fecha_salida) = pp.anio
                                 AND MONTH(e.fecha_salida) = pp.mes
                                THEN LEAST(DAY(e.fecha_salida), 30)
                                ELSE 30
                             END)
                            -
                            (CASE
                                WHEN e.fecha_ingreso IS NOT NULL
                                 AND YEAR(e.fecha_ingreso) = pp.anio
                                 AND MONTH(e.fecha_ingreso) = pp.mes
                                THEN LEAST(DAY(e.fecha_ingreso), 30)
                                ELSE 1
                             END)
                            + 1
                        )
                    ) / 30,
                    2
                )
                - ROUND(SUM(CASE
                    WHEN YEAR(pa.fecha) = pp.anio AND MONTH(pa.fecha) = pp.mes THEN pa.monto
                    ELSE 0
                END), 2),
                0
            )
            + pp.horas_extras
            - ROUND(pp.dias_faltados * (es.sueldo_real / 30), 2)
            + COALESCE(pp.cts_sueldo_real, 0),
            2
        ) AS total_nuevo,
        pp.total_pagar AS total_anterior,
        pp.importe_p AS importe_p_anterior,
        pp.importe_d AS importe_d_anterior,
        pp.importe_c AS importe_c_anterior
    FROM planilla_pagos pp
    INNER JOIN empleados e ON e.id = pp.empleado_id
    INNER JOIN empleado_sueldos es ON es.id = (
        SELECT es2.id
        FROM empleado_sueldos es2
        WHERE es2.empleado_id = pp.empleado_id
          AND es2.vigente_desde <= LAST_DAY(STR_TO_DATE(CONCAT(pp.anio, '-', LPAD(pp.mes, 2, '0'), '-01'), '%Y-%m-%d'))
          AND (es2.vigente_hasta IS NULL
               OR es2.vigente_hasta >= LAST_DAY(STR_TO_DATE(CONCAT(pp.anio, '-', LPAD(pp.mes, 2, '0'), '-01'), '%Y-%m-%d')))
        ORDER BY es2.vigente_desde DESC, es2.id DESC
        LIMIT 1
    )
    LEFT JOIN planilla_adelantos pa ON pa.empleado_id = pp.empleado_id
    WHERE pp.estado = 'pendiente'
    GROUP BY
        pp.id, es.id, es.sueldo_real, es.sueldo_planilla,
        e.fecha_ingreso, e.fecha_salida, pp.anio, pp.mes,
        pp.dias_faltados, pp.horas_extras, pp.cts_sueldo_real,
        pp.total_pagar, pp.importe_p, pp.importe_d, pp.importe_c;

    UPDATE planilla_pagos pp
    INNER JOIN tmp_planilla_recalculo t ON t.id = pp.id
    SET pp.empleado_sueldo_id = t.empleado_sueldo_id,
        pp.sueldo_base = t.disponible,
        pp.adelantos = t.adelantos,
        pp.descuento_faltas = t.descuento_faltas,
        pp.total_pagar = t.total_nuevo,
        pp.importe_d = CASE
            WHEN t.total_anterior > 0
             AND (t.importe_p_anterior + t.importe_d_anterior + t.importe_c_anterior) > 0
            THEN ROUND(t.importe_d_anterior * t.total_nuevo / t.total_anterior, 2)
            ELSE 0
        END,
        pp.importe_c = CASE
            WHEN t.total_anterior > 0
             AND (t.importe_p_anterior + t.importe_d_anterior + t.importe_c_anterior) > 0
            THEN ROUND(t.importe_c_anterior * t.total_nuevo / t.total_anterior, 2)
            ELSE 0
        END,
        pp.importe_p = CASE
            WHEN t.total_anterior > 0
             AND (t.importe_p_anterior + t.importe_d_anterior + t.importe_c_anterior) > 0
            THEN t.total_nuevo
                 - ROUND(t.importe_d_anterior * t.total_nuevo / t.total_anterior, 2)
                 - ROUND(t.importe_c_anterior * t.total_nuevo / t.total_anterior, 2)
            ELSE t.total_nuevo
        END,
        pp.updated_at = NOW();

    DROP TEMPORARY TABLE IF EXISTS tmp_planilla_recalculo;

    -- Validaciones finales: cualquier incumplimiento revierte toda la transacción.
    SELECT COUNT(*) INTO v_total
    FROM planilla_pagos
    WHERE id = v_kevin_pago_id
      AND empleado_sueldo_id = v_kevin_sueldo_id
      AND sueldo_base = 1100.00
      AND adelantos = 800.00
      AND total_pagar = 1100.00
      AND importe_p + importe_d + importe_c = 1100.00;
    IF v_total <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La validación final de Kevin falló';
    END IF;

    SELECT COUNT(*) INTO v_total
    FROM planilla_pagos
    WHERE id = v_augusto_pago_id
      AND empleado_sueldo_id = v_augusto_sueldo_id
      AND sueldo_base = 770.00
      AND descuento_faltas = 63.33
      AND total_pagar = 706.67
      AND importe_p + importe_d + importe_c = 706.67;
    IF v_total <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La validación final de Augusto falló';
    END IF;

    SELECT COUNT(*) INTO v_total
    FROM planilla_pagos
    WHERE id = v_ercli_pago_id
      AND sueldo_base = 1680.00
      AND total_pagar = 1680.00
      AND importe_p + importe_d + importe_c = 1680.00;
    IF v_total <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La validación final de Ercli falló';
    END IF;

    COMMIT;

    SELECT
        pp.id AS pago_id,
        e.nombre,
        pp.mes,
        pp.anio,
        es.sueldo_real,
        es.sueldo_planilla,
        es.sueldo_base AS sueldo_no_planilla,
        pp.adelantos,
        pp.descuento_faltas,
        pp.total_pagar,
        pp.estado
    FROM planilla_pagos pp
    INNER JOIN empleados e ON e.id = pp.empleado_id
    INNER JOIN empleado_sueldos es ON es.id = pp.empleado_sueldo_id
    WHERE pp.id IN (v_kevin_pago_id, v_augusto_pago_id, v_ercli_pago_id)
    ORDER BY pp.id;
END$$

CALL corregir_pagos_planilla_agosto_2026()$$
DROP PROCEDURE IF EXISTS corregir_pagos_planilla_agosto_2026$$

DELIMITER ;
