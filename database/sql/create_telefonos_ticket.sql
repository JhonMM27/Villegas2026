-- Alternativa manual a la migración. No ejecutar ambos mecanismos.
CREATE TABLE `telefonos_ticket` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `telefono_1` VARCHAR(25) NOT NULL,
  `telefono_2` VARCHAR(25) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `telefonos_ticket` (`id`, `telefono_1`, `telefono_2`, `created_at`, `updated_at`)
VALUES (1, '967984895', '978431737', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
