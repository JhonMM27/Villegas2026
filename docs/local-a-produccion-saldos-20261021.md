# Aplicar en local y publicar la solucion de saldos

## Resultado recibido

El resultado de phpMyAdmin adjuntado por el usuario termina en SIMULACION OK.
No es un fallo: el archivo usado tenia `@saldos_aplicar = 0`. No instalo las
migraciones ni modifico saldos. Los mensajes de conjunto vacio son normales
para SET/CREATE/DROP; la nota 1305 corresponde al DROP IF EXISTS de una rutina
inexistente; la advertencia BINARY obsoleto no impidio simular.

Se genero un archivo de APLICACION distinto para evitar confundir los modos:
`database/sql/20261021_APLICAR_LOCAL_20-10-2026.sql`.
Usa CAST(... AS BINARY) para evitar la advertencia de MySQL. El guardado por
defecto exige confirmar la pausa; no escribe si esa confirmacion sigue en cero.

## 1. Modificar y verificar la copia local

1. Conservar el dump original. Detener las escrituras de la app/importadores
   sobre la copia local `20/10/2026`.
2. En el archivo de APLICACION, cambiar SOLO la confirmacion al inicio:

   ```sql
   SET @saldos_base_autorizada = '20/10/2026';
   SET @saldos_aplicar = 1;
   SET @saldos_escrituras_pausadas = 1;
   ```

3. En phpMyAdmin LOCAL seleccionar `20/10/2026`, entrar a Importar y seleccionar
   el archivo completo. No ejecutar trozos de CREATE PROCEDURE ni quitar DELIMITER.
4. Esperar DESPLIEGUE OK. Verificar ventas: 90582 saldo 76.00; 118388 saldo 0.00;
   118981 saldo 234.00. El script tambien registra ambos nombres de migracion
   en la tabla migrations: no ejecutar despues esas migraciones por separado.
5. Para probar los reportes de Laravel, la conexion LOCAL debe apuntar a esta
   copia. El script no configura `.env` ni vacia caches de Laravel. Al revisar
   anteriormente el proyecto, `.env` apuntaba a `03/10/2026`; no se ha cambiado.
   Limpiar caches con `php artisan optimize:clear` tras configurar la conexion.

El archivo incluye TODO el esquema de esta solucion: `abonos_migrados` nullable
en ventas/compras, `saldo_conciliaciones`, `recibo_contadores`, `recibo_solicitudes`,
indices unicos de numero_recibo en ambos modulos, inicializacion sin retroceso
de contadores y las dos entradas migrations. Incluye la conciliacion documentada
de las tres ventas, sin crear caja, recibos ficticios ni movimientos de inventario.
No es una migracion general de todas las funcionalidades pendientes del proyecto.

La copia de ensayo `codex_saldos_mysql_20261021` paso instalacion y repeticion.
El nuevo archivo de aplicacion se volvio a verificar alli: rechaza la falta de
confirmacion de pausa, conserva los tres saldos y no genera la advertencia BINARY.
No se ejecuto sobre la BD del usuario `20/10/2026` ni sobre produccion.

## 2. Subir el codigo

El ZIP existente contiene exactamente los 21 archivos de esta solucion:
`storage/app/private/entrega_saldos_20261021/archivos-ftp-saldos-20261021.zip`.
La lista exacta es `docs/archivos-ftp-saldos-20261021.txt`.
No hay archivos de la solucion adicionales para FTP por esta continuacion.

Conservar una copia del codigo remoto, comparar si hay modificaciones propias
en produccion y subir archivos individuales preservando las rutas. No borrar
carpetas enteras ni sobrescribir `.env`, `vendor`, `storage`, `routes/web.php` o
los tickets de otras funcionalidades. Los archivos SQL no van al directorio publico.
La ruta public/js debe corresponder al directorio publico real del hosting.
FTP no limpia caches ni ejecuta Artisan: usar terminal del hosting o pedir al
administrador que lo haga. No crear una ruta PHP publica para ejecutar comandos.

## 3. Actualizar la BD principal en produccion

Produccion es MariaDB 10.11.19 y local es MySQL 8.4.3. Los nombres de las bases
son distintos. Por ello hay una variante SQL ensayada para cada entorno.
Probar MySQL local no sustituye la comprobacion de MariaDB: la variante de
produccion ya se ensayo con el dump en MariaDB 10.11.19 aislado.

NO usar el archivo LOCAL en cPanel. Usar exclusivamente:
`database/sql/20261021_produccion_saldos_completo.sql`.

1. Respaldar la BD ACTUAL de produccion y verificar su restaurabilidad.
2. En cPanel seleccionar `ene15cod_dbconsorcio26v2-16`. Importar el SQL de
   produccion como viene (aplicar=0); exigir SIMULACION OK.
3. Pausar todas las escrituras relacionadas, incluidos workers/importadores.
4. Publicar los 21 archivos PHP/Blade/JS. Durante la pausa, reimportar el SQL
   de produccion con aplicar=1 y escrituras_pausadas=1. Exigir DESPLIEGUE OK.
5. Limpiar caches con `php artisan optimize:clear`, comprobar reportes y
   cargar de nuevo las pantallas de recibos antes de reabrir las escrituras.

Si hubo pagos/cambios en las ventas revisadas, el primer despliegue se detendra.
No cambiar importes esperados para forzarlo: revisar el nuevo respaldo. El parche
conserva las otras operaciones de produccion y no requiere exportar toda la BD local.
Las rutinas necesitan permisos CREATE ROUTINE/EXECUTE y el resto de permisos
indicados en la guia de despliegue. Si cPanel los deniega, pedir ejecucion al hosting.

## Si se pretende reemplazar toda la BD

Solo es admisible como operacion planificada si produccion queda completamente
sin escrituras DESDE ANTES de sacar un respaldo NUEVO hasta terminar la restauracion
y validacion. La copia preparada localmente tiene que provenir de ese respaldo
congelado, no del dump del 20/10 si hubo operaciones posteriores. Requiere ensayo
de restauracion en MariaDB y plan de recuperacion compatible.

Si produccion siguio funcionando desde el respaldo del 20/10, reemplazarla con
la copia local perderia ventas, pagos, gastos y otros registros posteriores.
Subir el parche SQL y el codigo es el flujo indicado para este caso. No se ha
generado ni ejecutado una restauracion completa contra la BD principal.

Esta entrega no resuelve todas las inconsistencias antiguas ni implementa el
reporte historico a cualquier fecha pasada; conserva el alcance documentado.
