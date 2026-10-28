# Saldos y recibos: entrega de produccion del 21/10/2026

## Archivos de esta entrega

- SQL unico: `database/sql/20261021_produccion_saldos_completo.sql`.
- Codigo FTP: `storage/app/private/entrega_saldos_20261021/archivos-ftp-saldos-20261021.zip`.
- Lista exacta del ZIP: `docs/archivos-ftp-saldos-20261021.txt` (21 archivos).
- Hashes del ZIP y su contenido: carpeta privada de la entrega.
- Prueba reproducible: `tests/Support/ProduccionSaldosSqlMariaDb.php`; solo copia local.

No se ha conectado a FTP ni se ha ejecutado nada en el servidor de produccion.
El ZIP contiene versiones completas de los 21 archivos, no un parche Git.
Comparar contra los archivos remotos si produccion tiene cambios propios posteriores.
Hacer copia del codigo remoto antes de reemplazarlo. No sustituir carpetas enteras.

## Base revisada y alcance

Respaldo aportado: `ene15cod_dbconsorcio26v2-16 (12).sql`, exportado el
20/10/2026 a las 16:36:23, segun su cabecera. Motor: MariaDB 10.11.19.
SHA256: `f320faf4ae4f3c34d3f1a498bac3fe22a5a8290d262f1041ce57dfc8840806c9`.
Base de produccion autorizada: `ene15cod_dbconsorcio26v2-16`.

El SQL anterior del 05/10 era para MySQL 8 y requeria otra base restaurada.
NO usarlo en esta entrega ni ejecutar tambien las migraciones manualmente.
El nuevo SQL es autonomo, selecciona la BD que ya existe e instala el esquema
de las dos migraciones, registrandolas al finalizar la transaccion de datos.
No requiere importar el dump completo a produccion ni crear una BD adicional.

La evidencia historica procede de la revision ya aprobada contra `21/09/2026`.
El archivo contiene los importes verificados, la identidad de las tres ventas
y las aplicaciones actuales exactas contrastadas con el respaldo del 20/10.
No deriva historicos automaticamente de `importe_p` ni inventa recibos.

| Venta | Cliente | Saldo del dump | Historico a preservar | Saldo esperado |
|---|---|---:|---:|---:|
| 90582 | 57, Eliseo | 76.00 | 103.00 | 76.00 |
| 118388 | 2, Alex | 1180.00 | 1180.00 | 0.00 |
| 118981 | 2, Alex | 6114.00 | 5880.00 | 234.00 |

En las dos ventas de Alex el saldo conjunto pasa de 7294.00 a 234.00.
Si se modificaron esos documentos o sus recibos despues del respaldo, el primer
despliegue se detiene. No cambiar constantes para saltarse la comprobacion:
revisar un nuevo respaldo. Tras aplicarse, repetir valida y conserva pagos posteriores.
Una conciliacion previa de otro lote tambien se rechaza para no sobrescribirla.

## Secuencia de publicacion

1. Exportar y guardar un respaldo ACTUAL de la BD principal. Conservar tambien
   el codigo remoto que sera reemplazado. El dump del 20/10 no cubre operaciones
   posteriores; no restaurarlo encima de produccion.
2. En phpMyAdmin o cliente SQL, seleccionar `ene15cod_dbconsorcio26v2-16`.
   Importar el archivo unico completo con sus valores predeterminados:

   ```sql
   SET @saldos_aplicar = 0;
   SET @saldos_escrituras_pausadas = 0;
   ```

   Debe terminar con `SIMULACION OK`, mostrar saldos 76.00, 0.00 y 234.00 y no
   instalar tablas ni modificar importes. Solo se crean y eliminan rutinas
   auxiliares y tablas temporales de la conexion.
3. Pausar TODOS los procesos que escriben recibos, ventas/compras o importaciones.
   Lo mas sencillo es una ventana breve de mantenimiento. Si hay terminal:

   ```bash
   php artisan down
   ```

   Esto no detiene workers, cron, importadores ni conexiones SQL externas:
   pausarlos tambien si pueden escribir. Una variable SQL no detiene la aplicacion.
4. Subir por FTP SOLO los 21 archivos del ZIP, preservando las rutas de la lista.
   La carpeta `app` va junto a `artisan`; no dentro de `public_html` salvo que
   esa sea realmente la raiz Laravel. `public/js/recibo-form.js` va al directorio
   publico real, junto a los otros JS, si el hosting separa `public_html` de Laravel.
   Sobrescribir archivos individuales, sin borrar otros archivos del servidor.
5. Reimportar el MISMO SQL completo, cambiando las dos asignaciones del inicio:

   ```sql
   SET @saldos_aplicar = 1;
   SET @saldos_escrituras_pausadas = 1;
   ```

   Debe terminar con `DESPLIEGUE OK`. En el respaldo los contadores parten como
   minimo de venta=44238 y compra=519; se calculan en el servidor al ejecutar,
   no se fijan a esos valores. Nunca se reduce un contador existente.
6. En terminal del hosting/SSH, desde la raiz Laravel:

   ```bash
   php artisan optimize:clear
   php artisan saldos:auditar --tipo=ventas --titular=2
   ```

   El auditor puede devolver codigo 1 por incidencias historicas pendientes;
   leer el resultado. No ejecuta reparaciones. No ejecutar `migrate` general,
   `migrate:fresh`, `db:seed`, ni el manifiesto local del 05/10.
   Si el hosting no ofrece terminal, solicitar que limpien caches Laravel
   (especialmente vistas compiladas) y OPcache si no revalida archivos.
   No publicar un PHP de mantenimiento sin autenticacion.
7. Verificar la cuenta de Alex y las pantallas de cobranzas/pagos. Recargar las
   pestanas abiertas para cargar UUID/version de recibo. No registrar pagos
   ficticios en produccion para probar. Reabrir con `php artisan up` cuando este
   todo verificado y reanudar los procesos que se pausaron.

FTP por si solo no ejecuta SQL ni comandos Artisan. No sustituir `.env`, `vendor`,
`composer.json`, `routes/web.php`, los tickets, `public/build` ni `storage`.
No hay dependencias nuevas ni se requiere una compilacion Vite para esta entrega.
Las pruebas, documentos, el dump y el SQL NO se suben al directorio publico.

## Limites y recuperacion

No se garantiza cero interrupcion: la app antigua no toma los bloqueos nuevos.
Los ALTER usan INSTANT/INPLACE y LOCK=NONE, pero necesitan bloqueos de metadatos.
La espera se limita a 15 segundos. No quitar algoritmos o forzar LOCK para que pase.
Si hay timeout, mantener la pausa, revisar conexiones activas y repetir tras
resolver la causa. No ignorar errores ni usar `--force`.

MariaDB hace COMMIT implicito en DDL. Un fallo puede dejar columnas/tablas/indices
instalados aunque se hayan revertido los importes. El archivo admite repetir
tras resolver la causa. La conciliacion, sus evidencias, contadores y registro
de migraciones se confirman juntos al final de una transaccion separada.
No ejecutar un rollback de esquema ni volver al sincronizador PHP antiguo
despues de conciliar. Recuperar exige codigo y datos compatibles, sin perder pagos nuevos.

Permisos requeridos: CREATE ROUTINE, ALTER ROUTINE (incluida eliminacion de las
rutinas propias), EXECUTE, CREATE TEMPORARY TABLES, CREATE, ALTER, SELECT, INSERT,
UPDATE y acceso a information_schema. Si el hosting rechaza permisos, detenerse
y pedir al administrador que ejecute el archivo; no continuar solo con el FTP.

El SQL no cambia compras existentes, caja, kardex ni las demas ventas. El codigo
protege ventas y compras; bloquea documentos inconsistentes para no destruir
evidencia. No resuelve todas las anomalias historicas ni reconstruye saldos a
cualquier fecha pasada. Esas partes del plan siguen pendientes.

## Verificacion de esta entrega

Restauracion completa del dump en un contenedor local MariaDB 10.11.19, base
`codex_saldos_prod_20261021`, sin usar ni cambiar `.env` y sin conectar a produccion.
101 comprobaciones correctas: simulacion sin DDL persistente ni cambios de datos;
rechazo de BD incorrecta, falta de pausa y saldos modificados; fallo provocado en
la ultima venta con rollback de todo el lote, evidencias y contadores; instalacion
tras DDL parcial; solo tres ventas conciliadas; checksums intactos de las demas
tablas originales salvo el registro de migraciones; repeticion sin cambios;
pago posterior real a traves del servicio Laravel conservado al repetir; eliminacion
del pago de ensayo sin perder historico ni retroceder la numeracion.

Regresion Laravel ejecutada de nuevo el 21/10: 105 pruebas, 549 aserciones,
todas correctas en SQLite en memoria. El ZIP se verifico entrada por entrada
contra SHA256 de los 21 archivos originales. En la copia, el saldo total positivo
final de Alex es 234.00 y el de Eliseo es 76.00. El contador de ventas de ensayo
termina en 44239 por el recibo creado y eliminado exclusivamente en esa copia.

Referencias oficiales:
- [ALTER TABLE](https://mariadb.com/docs/server/reference/sql-statements/data-definition/alter/alter-table)
- [Bloqueos de metadatos](https://mariadb.com/docs/server/reference/sql-statements/transactions/metadata-locking)
- [COMMIT implicito](https://mariadb.com/docs/server/reference/sql-statements/transactions/sql-statements-that-cause-an-implicit-commit)
