-- CORRECCIÓN PUNTUAL Y CONDICIONADA
--
-- Proveedor 102: MOLINORTE SAC
-- Compra 4439: NC 01-4436
--
-- Diagnóstico:
-- La compra fue pagada al contado mediante Consorcio (importe_c = 88,200.00),
-- pero su rectificación reinició acuenta a 0.00 y abrió un saldo de 88,200.00.
--
-- Corrección:
--   acuenta: 0.00      -> 88,200.00
--   saldo:   88,200.00 -> 0.00
--
-- No modifica productos, detalles, movimientos de kardex, costos ni otras compras.
-- Bases autorizadas:
--   - Producción: ene15cod_dbconsorcio26v2-16
--   - Copia local: 24/07/2026_2
--
-- Es idempotente: si la compra ya fue corregida, no vuelve a modificarla.
-- Ante cualquier dato inesperado ejecuta ROLLBACK.

SELECT DATABASE() AS base_actual;

DROP PROCEDURE IF EXISTS corregir_proveedor_102_molinorte;

DELIMITER $$

CREATE PROCEDURE corregir_proveedor_102_molinorte()
SQL SECURITY INVOKER
correccion: BEGIN
    DECLARE base_valida INT DEFAULT 0;
    DECLARE proveedor_valido INT DEFAULT 0;
    DECLARE compra_pendiente_valida INT DEFAULT 0;
    DECLARE compra_ya_corregida INT DEFAULT 0;
    DECLARE pagos_provisionales INT DEFAULT 0;
    DECLARE compras_pendientes_antes INT DEFAULT 0;
    DECLARE saldo_pendiente_antes DECIMAL(14, 2) DEFAULT 0.00;
    DECLARE filas_actualizadas INT DEFAULT 0;
    DECLARE compras_pendientes_despues INT DEFAULT 0;
    DECLARE saldo_pendiente_despues DECIMAL(14, 2) DEFAULT 0.00;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SET base_valida = DATABASE() IN (
        'ene15cod_dbconsorcio26v2-16',
        '24/07/2026_2'
    );

    SELECT COUNT(*)
      INTO proveedor_valido
      FROM proveedores
     WHERE id = 102
       AND UPPER(TRIM(razon_social)) = 'MOLINORTE SAC';

    SELECT COUNT(*)
      INTO compra_pendiente_valida
      FROM compras
     WHERE id = 4439
       AND proveedor_id = 102
       AND proveedor_nombre = 'MOLINORTE SAC'
       AND comprobante_tipo_codigo = 'NC'
       AND serie = '01'
       AND correlativo = 4436
       AND pago_forma_codigo = '1'
       AND LOWER(TRIM(pago_forma_nombre)) = 'contado'
       AND fecha_compra = '2026-06-26 09:23:00'
       AND total = 88200.00
       AND importe_p = 0.00
       AND importe_d = 0.00
       AND importe_c = 88200.00
       AND acuenta = 0.00
       AND abonos = 0.00
       AND saldo = 88200.00
       AND LOWER(TRIM(estado)) = 'rectificada'
       AND rectificacion_count = 1;

    SELECT COUNT(*)
      INTO compra_ya_corregida
      FROM compras
     WHERE id = 4439
       AND proveedor_id = 102
       AND comprobante_tipo_codigo = 'NC'
       AND serie = '01'
       AND correlativo = 4436
       AND total = 88200.00
       AND importe_p = 0.00
       AND importe_d = 0.00
       AND importe_c = 88200.00
       AND acuenta = 88200.00
       AND abonos = 0.00
       AND saldo = 0.00
       AND LOWER(TRIM(estado)) = 'rectificada'
       AND rectificacion_count = 1;

    SELECT COUNT(*)
      INTO pagos_provisionales
      FROM compra_provisional_detalles
     WHERE compra_id = 4439;

    SELECT
        COUNT(*),
        COALESCE(SUM(saldo), 0)
      INTO compras_pendientes_antes, saldo_pendiente_antes
      FROM compras
     WHERE proveedor_id = 102
       AND LOWER(TRIM(estado)) <> 'anulada'
       AND saldo > 0;

    IF base_valida <> 1
       OR proveedor_valido <> 1
       OR pagos_provisionales <> 0
       OR (
            compra_pendiente_valida <> 1
        AND compra_ya_corregida <> 1
       ) THEN
        ROLLBACK;

        SELECT
            'ROLLBACK' AS resultado,
            'La base o los datos no coinciden con las precondiciones esperadas.' AS detalle,
            base_valida,
            proveedor_valido,
            compra_pendiente_valida,
            compra_ya_corregida,
            pagos_provisionales,
            compras_pendientes_antes,
            saldo_pendiente_antes;

        LEAVE correccion;
    END IF;

    SELECT id
      FROM proveedores
     WHERE id = 102
       FOR UPDATE;

    SELECT id
      FROM compras
     WHERE id = 4439
       FOR UPDATE;

    IF compra_ya_corregida = 1 THEN
        COMMIT;

        SELECT
            'SIN CAMBIOS' AS resultado,
            'La compra 4439 ya estaba corregida.' AS detalle;

        LEAVE correccion;
    END IF;

    IF compras_pendientes_antes <> 1
       OR saldo_pendiente_antes <> 88200.00 THEN
        ROLLBACK;

        SELECT
            'ROLLBACK' AS resultado,
            'MOLINORTE presenta otra deuda distinta a la esperada; no se modificó nada.' AS detalle,
            compras_pendientes_antes,
            saldo_pendiente_antes;

        LEAVE correccion;
    END IF;

    UPDATE compras
       SET acuenta = 88200.00,
           saldo = 0.00,
           updated_at = CURRENT_TIMESTAMP
     WHERE id = 4439
       AND proveedor_id = 102
       AND total = 88200.00
       AND importe_c = 88200.00
       AND acuenta = 0.00
       AND abonos = 0.00
       AND saldo = 88200.00
       AND LOWER(TRIM(estado)) = 'rectificada'
       AND rectificacion_count = 1;

    SET filas_actualizadas = ROW_COUNT();

    SELECT
        COUNT(*),
        COALESCE(SUM(saldo), 0)
      INTO compras_pendientes_despues, saldo_pendiente_despues
      FROM compras
     WHERE proveedor_id = 102
       AND LOWER(TRIM(estado)) <> 'anulada'
       AND saldo > 0;

    IF filas_actualizadas <> 1
       OR compras_pendientes_despues <> 0
       OR saldo_pendiente_despues <> 0.00 THEN
        ROLLBACK;

        SELECT
            'ROLLBACK' AS resultado,
            'La corrección no alcanzó las postcondiciones esperadas.' AS detalle,
            filas_actualizadas,
            compras_pendientes_despues,
            saldo_pendiente_despues;

        LEAVE correccion;
    END IF;

    COMMIT;

    SELECT
        'COMMIT' AS resultado,
        'Proveedor 102 MOLINORTE SAC quedó sin deuda registrada.' AS detalle;

    SELECT
        id,
        proveedor_id,
        proveedor_nombre,
        CONCAT(comprobante_tipo_codigo, ' ', serie, '-', correlativo) AS documento,
        fecha_compra,
        pago_forma_nombre,
        total,
        importe_p,
        importe_d,
        importe_c,
        acuenta,
        abonos,
        saldo,
        estado,
        rectificacion_count
      FROM compras
     WHERE id = 4439;

    SELECT
        COUNT(*) AS documentos_pendientes,
        COALESCE(SUM(saldo), 0) AS deuda_total
      FROM compras
     WHERE proveedor_id = 102
       AND LOWER(TRIM(estado)) <> 'anulada'
       AND saldo > 0;
END$$

DELIMITER ;

CALL corregir_proveedor_102_molinorte();

DROP PROCEDURE IF EXISTS corregir_proveedor_102_molinorte;
