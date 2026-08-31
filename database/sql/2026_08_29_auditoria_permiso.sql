-- Permiso de acceso al módulo de Auditoría.
-- Seguro para ejecutar más de una vez.

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'auditoria_list', 'web', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1
    FROM `permissions`
    WHERE `name` = 'auditoria_list'
      AND `guard_name` = 'web'
);

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.`id`, r.`id`
FROM `permissions` p
INNER JOIN `roles` r
    ON r.`name` = 'admin'
   AND r.`guard_name` = 'web'
WHERE p.`name` = 'auditoria_list'
  AND p.`guard_name` = 'web'
  AND NOT EXISTS (
      SELECT 1
      FROM `role_has_permissions` rhp
      WHERE rhp.`permission_id` = p.`id`
        AND rhp.`role_id` = r.`id`
  );
