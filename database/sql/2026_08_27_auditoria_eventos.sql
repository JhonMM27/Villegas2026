-- ============================================================================
-- SCRIPT DE AUDITORÍA Y RECTIFICACIONES UNIFICADO (PRODUCCIÓN)
-- Consolidación de: rectificacion_historiales + auditoria_eventos + motivo_nullable
-- ============================================================================

-- 1. Agregar columna 'rectificacion_count' en venta_entregas (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'venta_entregas' 
      AND COLUMN_NAME = 'rectificacion_count'
);
SET @sql_col = IF(@col_exists = 0, 'ALTER TABLE `venta_entregas` ADD COLUMN `rectificacion_count` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `estado`', 'SELECT 1');
PREPARE stmt_col FROM @sql_col;
EXECUTE stmt_col;
DEALLOCATE PREPARE stmt_col;

-- 2. Crear tabla auditoria_eventos (con motivo nullable por defecto)
CREATE TABLE IF NOT EXISTS `auditoria_eventos` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tipo_evento` VARCHAR(20) NOT NULL,
    `modulo` VARCHAR(50) NOT NULL,
    `registro_id` BIGINT UNSIGNED NOT NULL,
    `numero_rectificacion` TINYINT UNSIGNED NULL,
    `registro_referencia` VARCHAR(150) NOT NULL,
    `user_id` SMALLINT UNSIGNED NULL,
    `user_nombre` VARCHAR(100) NOT NULL,
    `motivo` VARCHAR(500) NULL,
    `datos_anteriores` JSON NOT NULL,
    `datos_nuevos` JSON NOT NULL,
    `cambios` JSON NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `audit_tipo_modulo_registro_num_unique` (`tipo_evento`, `modulo`, `registro_id`, `numero_rectificacion`),
    KEY `audit_tipo_fecha_index` (`tipo_evento`, `created_at`),
    KEY `audit_modulo_registro_index` (`modulo`, `registro_id`),
    KEY `audit_usuario_index` (`user_id`),
    KEY `audit_referencia_index` (`registro_referencia`),
    CONSTRAINT `audit_usuario_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Garantizar que 'motivo' sea NULL en caso de que la tabla ya haya existido previamente con NOT NULL
ALTER TABLE `auditoria_eventos`
    MODIFY COLUMN `motivo` VARCHAR(500) NULL;

-- 4. Migrar datos históricos desde 'rectificacion_historiales' (si la tabla existe)
SET @table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'rectificacion_historiales'
);
SET @sql_migrate = IF(@table_exists > 0, '
    INSERT INTO `auditoria_eventos`
        (`tipo_evento`, `modulo`, `registro_id`, `numero_rectificacion`, `registro_referencia`,
         `user_id`, `user_nombre`, `motivo`, `datos_anteriores`, `datos_nuevos`, `cambios`, `created_at`, `updated_at`)
    SELECT ''rectificacion'', rh.`modulo`, rh.`registro_id`, rh.`numero_rectificacion`, rh.`registro_referencia`,
           rh.`user_id`, rh.`user_nombre`, NULLIF(TRIM(rh.`motivo`), ''''),
           rh.`datos_anteriores`, rh.`datos_nuevos`, rh.`cambios`, rh.`created_at`, rh.`updated_at`
    FROM `rectificacion_historiales` rh
    WHERE NOT EXISTS (
        SELECT 1 FROM `auditoria_eventos` ae
        WHERE ae.`tipo_evento` = ''rectificacion''
          AND ae.`modulo` = rh.`modulo`
          AND ae.`registro_id` = rh.`registro_id`
          AND ae.`numero_rectificacion` = rh.`numero_rectificacion`
    )
', 'SELECT 1');
PREPARE stmt_migrate FROM @sql_migrate;
EXECUTE stmt_migrate;
DEALLOCATE PREPARE stmt_migrate;
