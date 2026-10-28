# SQL de despliegue de saldos y recibos

Probado en MySQL 8.4.3, sobre una copia restaurada del respaldo anterior a las
correcciones. La base local `03/10/2026` ya tiene estos cambios aplicados.
Estos archivos se entregan para otra base que necesite el mismo despliegue.

## Orden de ejecucion

1. Respaldar la base destino. Seleccionarla en el cliente SQL; usar una conexion
   dedicada sin transacciones pendientes. No ejecutar migraciones Laravel en paralelo.
2. Pausar registro/edicion/eliminacion de cobranzas y pagos e importaciones que
   escriban esos registros. Es necesario para inicializar el contador junto con
   el despliegue del codigo; el bloqueo GET_LOCK del script no bloquea la app antigua.
3. Ejecutar completo `20261005_01_estructura_saldos_recibos.sql`. Reemplazar
   `CAMBIAR_POR_LA_BASE_DESTINO` por el nombre exacto de la base seleccionada.
   Debe terminar con `ESQUEMA VERIFICADO`. Ante cualquier error, detenerse.
4. Para el piloto de Alex/Eliseo, restaurar el respaldo verificado `21/09/2026`
   en el mismo servidor. Ejecutar `20261005_02_conciliar_piloto_saldos.sql`, ajustando
   `@saldos_base_autorizada` y, si se restauro con otro nombre, `@saldos_base_origen`.
   Primero mantener `@saldos_aplicar_piloto = 0`: debe proyectar saldos 76, 0 y 234.
5. Si las comprobaciones coinciden, repetir el segundo archivo con
   `@saldos_aplicar_piloto = 1`. El lote completo se confirma o se revierte;
   quedan registradas las tres conciliaciones, sin crear recibos ni caja.
6. Publicar tambien los cambios PHP/Blade/JS de esta entrega antes de reabrir las
   escrituras. Refrescar las pantallas abiertas y ejecutar `php artisan saldos:auditar`.

El primer archivo no altera importes. Conserva contadores existentes con GREATEST,
verifica columnas/indices y registra las dos migraciones al terminar. No marca
las migraciones ante un error. El segundo aplica exclusivamente los IDs del piloto:
no usar esos importes para otros documentos ni ajustar precondiciones a ciegas.

## Consideraciones de ejecucion

- Ejecutar los archivos completos con un cliente que admita DELIMITER, como
  MySQL CLI, Workbench o HeidiSQL. Detener la ejecucion ante errores; no usar --force.
- DDL de MySQL hace COMMIT implicito: no es reversible con ROLLBACK. Un fallo
  de estructura puede dejar cambios parciales; el script permite repetir despues
  de resolver la causa. Conservar el respaldo para recuperaciones compatibles.
- Las columnas usan ALGORITHM=INSTANT y los indices ALGORITHM=INPLACE, LOCK=NONE.
  Aun pueden esperar bloqueos de metadatos. La espera del DDL se limita a 15 segundos.
  No quitar las opciones para forzar un cambio bloqueante sin ensayarlo primero.
- Se requieren CREATE ROUTINE, ALTER, CREATE, SELECT, INSERT, UPDATE y permisos
  sobre las tablas del respaldo para el piloto. Las rutinas auxiliares se eliminan
  tras terminar correctamente. Sus nombres reservados empiezan por codex_sql_.
- SQL no sustituye la correccion del sincronizador PHP. Con el codigo antiguo,
  el proximo recalculo podria omitir otra vez los historicos conciliados.
- Los eventos de recibos usan `auditoria_eventos`, tabla preexistente que se verifica.
  Estos scripts no inventan un historial de recibos anterior al despliegue.
- Las conciliaciones SQL usan el lote `historico-clientes-2-57-sql-20261005`, con
  evidencia y huella propias; es diferente del lote aplicado por el comando Laravel.

## Ensayo realizado

En `codex_saldos_sql_20261005_103057`: estructura nueva y repeticion, simulacion
sin escrituras, aplicacion y reejecucion idempotente. Se provoco un fallo en la
tercera venta y se comprobo rollback de las anteriores y de su auditoria. Se hizo
un pago posterior de 20 a Eliseo, se repitio el SQL y se conservo el saldo 56.
Tras eliminar ese recibo de ensayo, repetir la estructura conservo el contador
incrementado. Se verifico el rechazo de un nombre de base destino incorrecto.
Evidencia privada: `storage/app/private/respaldo_saldos_20261004_223905/ensayo_sql_verificacion.json`.

Referencias de MySQL:

- https://dev.mysql.com/doc/refman/8.4/en/innodb-online-ddl-operations.html
- https://dev.mysql.com/doc/refman/8.4/en/implicit-commit.html
