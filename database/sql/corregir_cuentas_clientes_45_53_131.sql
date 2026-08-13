-- CORRECCIONES CONSOLIDADAS Y CONDICIONADAS
--
-- Base esperada: 24/07/2026_2
--
-- 1. Cliente 45 / NP 01-125560:
--    restaura el pago S/ 1,772.60 y deja saldo cero.
--
-- 2. Cliente 53 / NP 01-126307:
--    restaura el abono S/ 3,762.00 y deja saldo S/ 8,769.00.
--
-- 3. Cliente 131:
--    conserva el pago original de NP 01-126308 y reasigna S/ 5,826.00:
--      - S/ 3,747.00 a NP 01-127287;
--      - S/ 2,079.00 a NP 01-127346.
--    Al corte 18/07/2026 quedan 5 documentos y saldo S/ 23,415.00.
--
-- No elimina ventas, recibos, aplicaciones, movimientos ni detalles.
-- Si una precondición o postcondición no coincide, ejecuta ROLLBACK.

SELECT DATABASE() AS base_objetivo;

DROP PROCEDURE IF EXISTS corregir_cuentas_clientes_45_53_131;

DELIMITER $$

CREATE PROCEDURE corregir_cuentas_clientes_45_53_131()
SQL SECURITY INVOKER
correccion: BEGIN
    DECLARE base_valida INT DEFAULT 0;
    DECLARE ventas_validas INT DEFAULT 0;
    DECLARE aplicaciones_validas INT DEFAULT 0;
    DECLARE recibos_validos INT DEFAULT 0;
    DECLARE recibos_cuadrados INT DEFAULT 0;

    DECLARE filas_cliente_45 INT DEFAULT 0;
    DECLARE filas_cliente_53 INT DEFAULT 0;
    DECLARE filas_aplicacion_3000 INT DEFAULT 0;
    DECLARE filas_aplicacion_747 INT DEFAULT 0;
    DECLARE filas_aplicacion_2079 INT DEFAULT 0;
    DECLARE filas_venta_127287 INT DEFAULT 0;
    DECLARE filas_venta_127346 INT DEFAULT 0;

    DECLARE vinculado_126308 DECIMAL(12, 2) DEFAULT 0.00;
    DECLARE vinculado_127287 DECIMAL(12, 2) DEFAULT 0.00;
    DECLARE vinculado_127346 DECIMAL(12, 2) DEFAULT 0.00;
    DECLARE pendientes_corte INT DEFAULT 0;
    DECLARE saldo_corte DECIMAL(12, 2) DEFAULT 0.00;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SET base_valida = DATABASE() = '24/07/2026_2';

    SELECT COUNT(*)
      INTO ventas_validas
      FROM ventas
     WHERE (
            id = 125575
        AND cliente_id = 45
        AND comprobante_tipo_codigo = 'NP'
        AND serie = '01'
        AND correlativo = 125560
        AND LOWER(TRIM(estado)) = 'rectificada'
        AND rectificacion_count = 3
        AND total = 1772.60
        AND acuenta = 0.00
        AND abonos = 0.00
        AND saldo = 1772.60
     ) OR (
            id = 126322
        AND cliente_id = 53
        AND comprobante_tipo_codigo = 'NP'
        AND serie = '01'
        AND correlativo = 126307
        AND LOWER(TRIM(estado)) = 'rectificada'
        AND rectificacion_count = 1
        AND total = 12531.00
        AND acuenta = 0.00
        AND abonos = 0.00
        AND saldo = 12531.00
     ) OR (
            id = 126323
        AND cliente_id = 131
        AND comprobante_tipo_codigo = 'NP'
        AND serie = '01'
        AND correlativo = 126308
        AND LOWER(TRIM(estado)) = 'rectificada'
        AND rectificacion_count = 1
        AND total = 5826.00
        AND acuenta = 0.00
        AND abonos = 5826.00
        AND saldo = 0.00
     ) OR (
            id = 127303
        AND cliente_id = 131
        AND comprobante_tipo_codigo = 'NP'
        AND serie = '01'
        AND correlativo = 127287
        AND LOWER(TRIM(estado)) <> 'anulada'
        AND total = 8240.00
        AND acuenta = 0.00
        AND abonos = 4493.00
        AND saldo = 3747.00
     ) OR (
            id = 127362
        AND cliente_id = 131
        AND comprobante_tipo_codigo = 'NP'
        AND serie = '01'
        AND correlativo = 127346
        AND LOWER(TRIM(estado)) = 'rectificada'
        AND rectificacion_count = 2
        AND total = 3044.00
        AND acuenta = 0.00
        AND abonos = 0.00
        AND saldo = 3044.00
     );

    SELECT COUNT(*)
      INTO aplicaciones_validas
      FROM venta_provisional_detalles AS detalle
      INNER JOIN venta_provisionales AS provisional
        ON provisional.id = detalle.venta_provisional_id
     WHERE (
            detalle.id = 21069
        AND detalle.venta_provisional_id = 43171
        AND detalle.venta_id = 125575
        AND detalle.monto = 1772.60
        AND provisional.cliente_id = 45
        AND provisional.numero_recibo = '43165'
     ) OR (
            detalle.id = 21452
        AND detalle.venta_provisional_id = 43347
        AND detalle.venta_id = 126322
        AND detalle.monto = 3762.00
        AND provisional.cliente_id = 53
        AND provisional.numero_recibo = '43341'
     ) OR (
            detalle.id = 20995
        AND detalle.venta_provisional_id = 43144
        AND detalle.venta_id = 126323
        AND detalle.monto = 5826.00
        AND provisional.cliente_id = 131
        AND provisional.numero_recibo = '43139'
     ) OR (
            detalle.id = 21690
        AND detalle.venta_provisional_id = 43426
        AND detalle.venta_id = 126323
        AND detalle.monto = 3000.00
        AND provisional.cliente_id = 131
        AND provisional.numero_recibo = '43420'
     ) OR (
            detalle.id = 21693
        AND detalle.venta_provisional_id = 43427
        AND detalle.venta_id = 126323
        AND detalle.monto = 2826.00
        AND provisional.cliente_id = 131
        AND provisional.numero_recibo = '43421'
     ) OR (
            detalle.id = 21694
        AND detalle.venta_provisional_id = 43427
        AND detalle.venta_id = 127136
        AND detalle.monto = 1094.00
        AND provisional.cliente_id = 131
        AND provisional.numero_recibo = '43421'
     ) OR (
            detalle.id = 21698
        AND detalle.venta_provisional_id = 43374
        AND detalle.venta_id = 127303
        AND detalle.monto = 4493.00
        AND provisional.cliente_id = 131
        AND provisional.numero_recibo = '43368'
     );

    SELECT COUNT(*)
      INTO recibos_validos
      FROM venta_provisionales
     WHERE (
            id = 43171
        AND cliente_id = 45
        AND numero_recibo = '43165'
        AND monto = 1772.60
        AND libre = 0.00
     ) OR (
            id = 43347
        AND cliente_id = 53
        AND numero_recibo = '43341'
        AND monto = 40000.00
        AND libre = 0.00
     ) OR (
            id = 43144
        AND cliente_id = 131
        AND numero_recibo = '43139'
        AND monto = 14000.00
        AND libre = 0.00
     ) OR (
            id = 43426
        AND cliente_id = 131
        AND numero_recibo = '43420'
        AND monto = 3000.00
        AND libre = 0.00
        AND tipo = 'APLICADO'
     ) OR (
            id = 43427
        AND cliente_id = 131
        AND numero_recibo = '43421'
        AND monto = 3920.00
        AND libre = 0.00
        AND tipo = 'APLICADO'
     ) OR (
            id = 43374
        AND cliente_id = 131
        AND numero_recibo = '43368'
        AND monto = 19609.00
        AND libre = 0.00
     );

    SELECT COUNT(*)
      INTO recibos_cuadrados
      FROM (
            SELECT
                provisional.id,
                provisional.monto,
                COALESCE(SUM(detalle.monto), 0) AS total_aplicado
              FROM venta_provisionales AS provisional
              LEFT JOIN venta_provisional_detalles AS detalle
                ON detalle.venta_provisional_id = provisional.id
             WHERE provisional.id IN (
                43144,
                43171,
                43347,
                43374,
                43426,
                43427
             )
             GROUP BY provisional.id, provisional.monto
            HAVING provisional.monto = COALESCE(SUM(detalle.monto), 0)
      ) AS recibos;

    IF base_valida <> 1
       OR ventas_validas <> 5
       OR aplicaciones_validas <> 7
       OR recibos_validos <> 6
       OR recibos_cuadrados <> 6 THEN
        ROLLBACK;

        SELECT
            'ROLLBACK' AS resultado,
            'La base, ventas, recibos o aplicaciones no coinciden con las precondiciones.' AS detalle,
            base_valida,
            ventas_validas,
            aplicaciones_validas,
            recibos_validos,
            recibos_cuadrados;

        LEAVE correccion;
    END IF;

    SELECT id
      FROM ventas
     WHERE id IN (125575, 126322, 126323, 127303, 127362)
     ORDER BY id
       FOR UPDATE;

    SELECT id
      FROM venta_provisionales
     WHERE id IN (43144, 43171, 43347, 43374, 43426, 43427)
     ORDER BY id
       FOR UPDATE;

    SELECT id
      FROM venta_provisional_detalles
     WHERE id IN (20995, 21069, 21452, 21690, 21693, 21694, 21698)
     ORDER BY id
       FOR UPDATE;

    UPDATE ventas
       SET abonos = 1772.60,
           saldo = 0.00,
           updated_at = CURRENT_TIMESTAMP
     WHERE id = 125575
       AND cliente_id = 45
       AND total = 1772.60
       AND acuenta = 0.00
       AND abonos = 0.00
       AND saldo = 1772.60;

    SET filas_cliente_45 = ROW_COUNT();

    UPDATE ventas
       SET abonos = 3762.00,
           saldo = 8769.00,
           updated_at = CURRENT_TIMESTAMP
     WHERE id = 126322
       AND cliente_id = 53
       AND total = 12531.00
       AND acuenta = 0.00
       AND abonos = 0.00
       AND saldo = 12531.00;

    SET filas_cliente_53 = ROW_COUNT();

    UPDATE venta_provisional_detalles
       SET venta_id = 127303,
           comprobante_tipo_codigo = 'NP',
           serie = '01',
           correlativo = 127287,
           comentario = 'COBRANZA A NP01-127287 ALIPIO VILLEGAS'
     WHERE id = 21690
       AND venta_provisional_id = 43426
       AND venta_id = 126323
       AND monto = 3000.00;

    SET filas_aplicacion_3000 = ROW_COUNT();

    UPDATE venta_provisional_detalles
       SET venta_id = 127303,
           comprobante_tipo_codigo = 'NP',
           serie = '01',
           correlativo = 127287,
           monto = 747.00,
           comentario = 'COBRANZA A NP01-127287 ALIPIO VILLEGAS'
     WHERE id = 21693
       AND venta_provisional_id = 43427
       AND venta_id = 126323
       AND monto = 2826.00;

    SET filas_aplicacion_747 = ROW_COUNT();

    INSERT INTO venta_provisional_detalles (
        venta_provisional_id,
        venta_id,
        comprobante_tipo_codigo,
        serie,
        correlativo,
        monto,
        comentario
    )
    SELECT
        43427,
        127362,
        'NP',
        '01',
        127346,
        2079.00,
        'COBRANZA A NP01-127346 ALIPIO VILLEGAS'
    WHERE NOT EXISTS (
        SELECT 1
          FROM venta_provisional_detalles
         WHERE venta_provisional_id = 43427
           AND venta_id = 127362
           AND monto = 2079.00
    );

    SET filas_aplicacion_2079 = ROW_COUNT();

    UPDATE ventas
       SET abonos = 8240.00,
           saldo = 0.00,
           updated_at = CURRENT_TIMESTAMP
     WHERE id = 127303
       AND cliente_id = 131
       AND total = 8240.00
       AND acuenta = 0.00
       AND abonos = 4493.00
       AND saldo = 3747.00;

    SET filas_venta_127287 = ROW_COUNT();

    UPDATE ventas
       SET abonos = 2079.00,
           saldo = 965.00,
           updated_at = CURRENT_TIMESTAMP
     WHERE id = 127362
       AND cliente_id = 131
       AND total = 3044.00
       AND acuenta = 0.00
       AND abonos = 0.00
       AND saldo = 3044.00;

    SET filas_venta_127346 = ROW_COUNT();

    SELECT COALESCE(SUM(monto), 0)
      INTO vinculado_126308
      FROM venta_provisional_detalles
     WHERE venta_id = 126323;

    SELECT COALESCE(SUM(monto), 0)
      INTO vinculado_127287
      FROM venta_provisional_detalles
     WHERE venta_id = 127303;

    SELECT COALESCE(SUM(monto), 0)
      INTO vinculado_127346
      FROM venta_provisional_detalles
     WHERE venta_id = 127362;

    SELECT
        COUNT(*),
        COALESCE(SUM(saldo), 0)
      INTO pendientes_corte, saldo_corte
      FROM ventas
     WHERE cliente_id = 131
       AND fecha_venta < '2026-07-19 00:00:00'
       AND LOWER(TRIM(estado)) <> 'anulada'
       AND saldo > 0;

    IF filas_cliente_45 <> 1
       OR filas_cliente_53 <> 1
       OR filas_aplicacion_3000 <> 1
       OR filas_aplicacion_747 <> 1
       OR filas_aplicacion_2079 <> 1
       OR filas_venta_127287 <> 1
       OR filas_venta_127346 <> 1
       OR vinculado_126308 <> 5826.00
       OR vinculado_127287 <> 8240.00
       OR vinculado_127346 <> 2079.00
       OR pendientes_corte <> 5
       OR saldo_corte <> 23415.00 THEN
        ROLLBACK;

        SELECT
            'ROLLBACK' AS resultado,
            'La corrección no alcanzó las postcondiciones esperadas.' AS detalle,
            filas_cliente_45,
            filas_cliente_53,
            filas_aplicacion_3000,
            filas_aplicacion_747,
            filas_aplicacion_2079,
            filas_venta_127287,
            filas_venta_127346,
            vinculado_126308,
            vinculado_127287,
            vinculado_127346,
            pendientes_corte,
            saldo_corte;

        LEAVE correccion;
    END IF;

    COMMIT;

    SELECT
        'COMMIT' AS resultado,
        'Las tres cuentas fueron corregidas.' AS detalle;

    SELECT
        id,
        cliente_id,
        CONCAT(comprobante_tipo_codigo, ' ', serie, '-', correlativo) AS documento,
        total,
        acuenta,
        abonos,
        saldo,
        estado
      FROM ventas
     WHERE id IN (125575, 126322, 126323, 127303, 127362)
     ORDER BY cliente_id, id;

    SELECT
        COUNT(*) AS documentos_pendientes_al_18_07_2026,
        SUM(total) AS total_documentos,
        SUM(acuenta) AS total_acuenta,
        SUM(abonos) AS total_abonos,
        SUM(saldo) AS saldo_al_18_07_2026
      FROM ventas
     WHERE cliente_id = 131
       AND fecha_venta < '2026-07-19 00:00:00'
       AND LOWER(TRIM(estado)) <> 'anulada'
       AND saldo > 0;

    SELECT
        fecha_venta,
        fecha_vencimiento,
        CONCAT(comprobante_tipo_codigo, ' ', serie, '-', correlativo) AS documento,
        total,
        acuenta,
        abonos,
        saldo,
        items
      FROM ventas
     WHERE cliente_id = 131
       AND fecha_venta < '2026-07-19 00:00:00'
       AND LOWER(TRIM(estado)) <> 'anulada'
       AND saldo > 0
     ORDER BY fecha_venta, id;
END$$

DELIMITER ;

CALL corregir_cuentas_clientes_45_53_131();

DROP PROCEDURE IF EXISTS corregir_cuentas_clientes_45_53_131;
