# Ensayo local del respaldo 20/10/2026

La consulta del servidor local confirmo MySQL 8.4.3 y la existencia de la BD
`20/10/2026`. El respaldo de produccion procede de MariaDB 10.11.19: restaurar
sus datos en MySQL no convierte el motor local en MariaDB.

El error recibido no es solo una advertencia del analizador de phpMyAdmin:
incluye el error real #1064. La consulta enviada termina en la linea 75 del
procedimiento, `IF EXISTS (SELECT 1 FROM information_schema.TABLES;`, sin el
WHERE ni el cierre del procedimiento. Esta incompleta. No se puede concluir
si se corto al copiar/pegar o durante la seleccion/envio del cliente.

## Archivo correcto para la copia local

`database/sql/20261021_ensayo_local_mysql_saldos_completo.sql`

Es una variante para MySQL 8 con nombre de BD autorizado `20/10/2026`, comprobacion
del tipo JSON nativo de MySQL y rutinas auxiliares con nombres de ensayo local.
No se ha eliminado ninguna validacion de pagos, historicos o identidad.
El archivo original de produccion no fue modificado.
Se separaron las condiciones que consultaban dos veces la misma tabla temporal
en una sentencia (limitacion MySQL: #1137) y se usa ALGORITHM=INSTANT sin
LOCK=NONE en las dos altas de columna, como requiere MySQL.

1. En phpMyAdmin LOCAL seleccionar la BD `20/10/2026`.
2. Ir a **Importar**, seleccionar el archivo SQL local completo y usar formato SQL.
   No pegar solo una seleccion del CREATE PROCEDURE ni quitar DELIMITER.
3. Ejecutarlo como viene: `@saldos_aplicar = 0`. Debe terminar con `SIMULACION OK`.
   Los saldos proyectados del piloto deben ser 76.00, 0.00 y 234.00.
4. Si se desea aplicar en ESA COPIA local, conservar el respaldo original, detener
   las escrituras de la app local y cambiar al inicio del mismo archivo:

   ```sql
   SET @saldos_aplicar = 1;
   SET @saldos_escrituras_pausadas = 1;
   ```

   Volver a importar el archivo completo. Debe terminar con `DESPLIEGUE OK`.
   Repetir no duplica conciliaciones ni baja los contadores.
5. El script no cambia `.env`. La app local debe apuntar a `20/10/2026` para que
   sus reportes muestren la copia que se acaba de ensayar. Al comprobar el motor
   se encontro que el `.env` del proyecto seguia configurado para `03/10/2026`;
   se dejo intacto. No cambiar nunca el `.env` de produccion con el local.

Un fallo en la creacion de una rutina no demuestra que todos los intentos previos
de importacion hayan sido inocuos. El archivo permite rutinas auxiliares existentes
de su propio nombre y verifica el estado antes de tocar importes. Ante cualquier
error real, detenerse y revisar; no quitar las precondiciones para hacerlo pasar.

## Produccion

Usar exclusivamente `database/sql/20261021_produccion_saldos_completo.sql` en
`ene15cod_dbconsorcio26v2-16`, con su pausa y respaldo actuales. NO subir la copia
local completa a produccion: perderia pagos y otros registros creados despues del
respaldo. El ZIP de los 21 archivos FTP sigue siendo el mismo.

## Ensayo efectuado

Se restauro el respaldo en `codex_saldos_mysql_20261021`, servidor local MySQL
8.4.3. Se verificaron simulacion sin cambios de tablas, aplicacion, tres
conciliaciones y repeticion sin modificaciones. Saldos finales: 76.00, 0.00,
234.00; contadores: compra 519, venta 44238.

La comparacion de todas las columnas originales mediante SHA256 confirmo que
las 4808 compras y 58770 ventas fuera del piloto conservaron sus datos. Los
checksums de las demas tablas originales, salvo migrations, se conservaron.
La copia del usuario `20/10/2026` solo se consulto y conserva sus saldos originales.
No se modificaron `.env`, el SQL MariaDB ni el ZIP FTP. El ensayo se hizo con
el cliente MySQL; no se ha probado la interfaz concreta de phpMyAdmin del usuario.

Referencias:
- [Importacion de archivos en phpMyAdmin](https://docs.phpmyadmin.net/en/latest/import_export.html)
- [Delimitadores de rutinas](https://dev.mysql.com/doc/refman/8.4/en/stored-programs-defining.html)
