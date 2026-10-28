# Revision de saldos y plan de reparacion

Fecha de elaboracion: 04/10/2026, America/Lima.
Base objetivo verificada: `03/10/2026` (2026, no 2016).
Estado: diagnostico y plan. No se aplicaron reparaciones ni migraciones.

## 1. Alcance y evidencia

Se revisaron los servicios de cobranzas y pagos a proveedores, sus controladores,
reportes de cuentas corrientes, relacion con caja, validadores existentes,
pruebas relacionadas e historial Git. Se consultaron MySQL 8.4.3 y los respaldos
`08/09/2026` y `21/09/2026` mediante transacciones de solo lectura.

Durante la revision la conexion apuntaba inicialmente a `08/09/2026`. Despues del
cambio realizado por el usuario se verifico `03/10/2026` en la configuracion y
en la conexion efectiva. Las cifras de situacion actual de este documento
corresponden a esa ultima base.

Se reprodujeron dos comportamientos en SQLite en memoria, sin modificar MySQL:
la perdida de abonos historicos y el uso de pagos posteriores al corte en el
saldo del reporte. Se ejecuto ademas el validador de kardex en modo de lectura.
Esto no constituye una auditoria completa de todos los modulos del sistema.

## 2. Causa confirmada del caso Alex Cubas

`app/Services/VentaProvisionalService.php:323` suma solamente los detalles
positivos de `venta_provisional_detalles`. En la linea 329 sustituye
`ventas.abonos` con ese resultado y recalcula `ventas.saldo`.

El supuesto de que todo abono tiene un detalle es falso en los datos importados.
Parte del pago anterior existe solamente en la cabecera de la venta.

El commit `37475ae`, del 26/08/2026, sustituyo la suma/resta del pago individual
por esta sincronizacion completa. El cambio esta presente tanto en ventas como
en compras. La fecha del commit no demuestra cuando se desplego en produccion;
el cambio de las dos ventas de Alex si queda ubicado por los respaldos y sus
marcas de actualizacion.

### Reconstruccion

Cliente 2, ALEX CUBAS. Montos en soles.

| Concepto | Venta 118388 | Venta 118981 | Total |
|---|---:|---:|---:|
| Importe de venta | 2,608.00 | 6,671.00 | 9,279.00 |
| Pago inicial (`acuenta`) | 0.00 | 0.00 | 0.00 |
| Abonos en respaldo 21/09 | 2,240.00 | 5,880.00 | 8,120.00 |
| Detalles de pago en respaldo 21/09 | 1,060.00 | 0.00 | 1,060.00 |
| Historico sin detalle | 1,180.00 | 5,880.00 | 7,060.00 |
| Saldo en respaldo 21/09 | 368.00 | 791.00 | 1,159.00 |
| Aplicacion recibo 44043, 29/09 | 368.00 | 557.00 | 925.00 |
| Abonos guardados actualmente | 1,428.00 | 557.00 | 1,985.00 |
| Saldo guardado actualmente | 1,180.00 | 6,114.00 | 7,294.00 |
| Abonos esperados conservando el historico | 2,608.00 | 6,437.00 | 9,045.00 |
| Saldo esperado despues del recibo | 0.00 | 234.00 | 234.00 |

El recibo 44043 es el provisional ID 44049; sus detalles son 23086 y 23087.
El pago anterior de S/ 1,060 es el recibo 42744, provisional 42749, detalle 20069.
Las dos ventas y el recibo nuevo coinciden en `2026-09-29 12:41:55` como marca
de actualizacion/creacion, respectivamente.

La reproduccion aislada del metodo actual devolvio exactamente abonos 1428/557
y saldos 1180/6114. El defecto del codigo queda reproducido.

El resultado esperado es S/ 234 si se conservan como validos los abonos del
respaldo y el recibo nuevo, como indican los datos y la referencia del usuario.
S/ 1,159 corresponde al estado anterior a ese recibo. Los S/ 6,135 mencionados
inicialmente eran la diferencia entre capturas; el historico omitido es S/ 7,060.

## 3. Extension y otros hallazgos

### P1. Historico expuesto a sobrescritura

Entre 57,977 ventas no anuladas en `03/10/2026`, hay 33,667 cuyo abono de cabecera
supera la suma de detalles positivos. Esto NO significa que las 33,667 tengan
deuda incorrecta: 33,666 estan pagadas. Significa que dependen de informacion
historica que una reconstruccion basada solo en detalles no conservaria.

Queda una venta pendiente en esta situacion:

| Venta | Cliente | Total | Abonos cabecera | Detalles positivos | Saldo |
|---|---|---:|---:|---:|---:|
| 90582 | 57, ELISEO FERNANDEZ | 179.00 | 103.00 | 0.00 | 76.00 |

Un nuevo cobro sobre esta venta puede disparar la misma perdida que en Alex.
Alex ya no aparece en la consulta de brechas actuales: sus abonos fueron
sobrescritos y ahora coinciden con sus detalles. Una auditoria que mira solo la
base actual no puede reconstruir lo perdido.

La comparacion contra `21/09/2026`, restringida a ventas no anuladas que mantienen
ID, cliente, total y pago inicial, detecto dos cambios del componente historico:
precisamente las dos ventas de Alex. No descarta casos previos al respaldo,
documentos rectificados ni cambios de identidad que requieran otro analisis.

`app/Services/CompraProvisionalService.php:323` tiene el mismo supuesto. En las
4,737 compras no anuladas analizadas no se encontro brecha positiva entre abonos
y detalles. Se confirma la vulnerabilidad del codigo, no perdida historica de
proveedores por esta causa en este corte.

### P1. Relaciones y aplicaciones que necesitan conciliacion

Se encontraron 12 detalles de venta donde el cliente del recibo difiere del
cliente de la venta. Estan en los recibos 6791, 5072, 21830, 25458 y 37175.
El recibo 5072 concentra ocho de esos detalles.

Esto puede hacer que una cobranza se descuente de una venta pero se muestre en
el estado de otro cliente. Se debe verificar cada relacion contra la fuente;
no hay evidencia suficiente para reasignarlos automaticamente.

Hay 12 grupos repetidos de recibo/venta. No se debe concluir que son cobros
duplicados: pueden ser partidas historicas separadas de un mismo pago. El
servicio, sin embargo, recupera aplicaciones previas asignando un solo monto
por venta (`VentaProvisionalService.php:163`), en lugar de sumar todas las filas.
Editar esos recibos puede calcular mal el saldo disponible.

### P2. Sobreaplicaciones y valores negativos heredados

| Tipo | Recibo | Recibido/pagado | Aplicado | Exceso |
|---|---:|---:|---:|---:|
| Venta | 42366 | 674.60 | 674.62 | 0.02 |
| Venta | 42591 | 206.00 | 207.70 | 1.70 |
| Venta | 43237 | 4,720.00 | 4,721.00 | 1.00 |
| Venta | 43468 | 3,781.00 | 3,787.00 | 6.00 |
| Compra | 222 | 17,828.00 | 17,828.80 | 0.80 |
| Compra | 223 | 5,000.00 | 5,000.80 | 0.80 |

Sus campos `libre` son negativos. Las formas de pago si suman las cabeceras de
los 9,945 recibos de ventas y 478 de compras revisados. El problema de esos seis
recibos esta en la distribucion, no en esa suma.

Hay cinco documentos no anulados con saldo negativo: ventas 87836 (-0.20) y
120764 (-0.60); compras 3806 (-220.55), 3982 (-1,164.00) y 3992 (-184.00).
Tambien existe un detalle de cobranza negativo: ID 16740, venta 114437,
provisional 37030, monto -10.00. El sincronizador lo omite por su filtro `monto > 0`,
mientras otros reportes suman todos los detalles.

La intencion de estos valores puede ser vuelto, credito, reverso, redondeo o
error de importacion. Debe resolverse con evidencia antes de convertirlos a cero
o a pagos positivos. No se encontraron detalles huerfanos, aplicaciones positivas
a documentos anulados ni numeros de recibo repetidos en las consultas realizadas.

### P2. Reportes con significado temporal diferente

El estado de cuenta del cliente selecciona pagos del rango, pero toma el saldo
actual de la venta y calcula su total global con `now()->endOfDay()`:
`app/Http/Controllers/CuentaCorrienteClienteController.php:1731`, 1873 y 1939.
`saldoFechaSolicitadaPdf()` tambien usa el saldo actual filtrando fecha de venta.

Reproduccion aislada: venta de S/ 100 el 05/01, pago de S/ 60 el 01/02 y reporte
al 31/01. El metodo devuelve saldo S/ 40 y pagos mostrados S/ 0; el saldo historico
al 31/01 seria S/ 100. Ademas, un total calculado hasta hoy puede incluir ventas
posteriores al fin de las filas mostradas.

El codigo expresa que desea mostrar saldo actual. Por eso hay que definir dos
salidas explicitas: deuda actual y estado historico al corte. No basta cambiar
una resta. Los reportes de proveedores ya realizan otros calculos por fecha y
algunos reconstruyen desde detalles, mientras otros usan el saldo de cabecera.
Tambien deben consumir una regla compartida de saldos.

En proveedores, `estadoCuentaProveedorPdf()` muestra como abonos sueltos las
cabeceras con `tipo=ADELANTO`; un recibo parcialmente aplicado queda como
`APLICADO` y su remanente no se mostraria. No habia casos de ese tipo en compras
en la base auditada; es un defecto potencial comprobable en el flujo del codigo.

### P2. Concurrencia y trazabilidad incompletas

Los servicios tienen transacciones, validacion de titular, importes en centavos
y bloqueos de documentos; son protecciones que conviene mantener.

Faltan protecciones especificas al editar o borrar un mismo recibo concurrentemente:
la cabecera y sus aplicaciones anteriores se leen sin bloquear primero el recibo.
La validacion inicial bloquea solo los nuevos documentos, no la union de anteriores
y nuevos. Ordenar el array de IDs no expresa un `ORDER BY` en la consulta de bloqueo.

La numeracion utiliza `MAX(numero_recibo)+1` sin secuencia bloqueada ni indice unico
de numero de recibo en la base inspeccionada. Dos operaciones simultaneas pueden
obtener el mismo numero. No se hallaron numeros repetidos actualmente; el riesgo
de concurrencia requiere pruebas en MySQL con dos conexiones.

Editar recrea detalles y eliminar borra fisicamente cabecera y detalles. No se
encontraron registros de estos servicios en `auditoria_eventos` ni llamadas de
auditoria en su flujo. Esto dificulta reconstruir importes perdidos o distinguir
un ajuste legitimo de una sobrescritura.

### P2. Alertas que pueden informar exito con incidencias

`app/Console/Commands/AuditProvisionalesCommand.php:219` comprueba saldos fuera
de rango, pero no preservacion del componente historico. Devuelve codigo 0 aun
con incidencias. Su lista fija de recibos destacados tambien cuenta como incidencia
aunque un recibo ya estuviera corregido.

`ventas:validar-saldos-rectificados` cubre solamente ventas rectificadas con
detalles superiores al abono. No detecta el caso Alex ni el riesgo de Eliseo.

`kardex:validar` igualmente devuelve codigo 0 incluso con hallazgos. Un monitor
que solo revise el codigo de salida interpretaria el resultado como saludable.

### Kardex: frente separado

El validador reviso 153 productos y reporto 1,202 inconsistencias en 67 productos.
Sus comprobaciones incluyen cadena, formula, valorizacion y saldo del producto.
El conteo no representa necesariamente 1,202 diferencias fisicas independientes.
No se ha determinado en esta revision el origen de cada hallazgo ni una relacion
causal con las cobranzas. Se requiere clasificar antes de recalcular.

## 4. Plan de implementacion, en orden

### Fase 1. Respaldo y contencion

1. Generar un respaldo consistente de la base objetivo y probar su restauracion
   en una base separada. Conservar tambien el respaldo anterior al incidente.
2. Registrar base, version de codigo, fecha del corte y estado de cada documento
   que se pretenda reparar. Mantener una lista identificada de casos pendientes.
3. Instalar una validacion previa a crear, editar o eliminar aplicaciones:
   detener operaciones sobre documentos con historico sin conciliar o relaciones
   inconsistentes. Incluir las ventas de Alex en la lista: la igualdad actual
   entre cabecera y detalles ya no permite detectar su perdida.
4. Evitar recalculos masivos hasta contar con una base historica verificada.
   Preparar el cambio y ensayarlo primero en la restauracion, no en la base operativa.

Entrega: respaldo restaurable, inventario de diferencias y operaciones afectadas
protegidas. No requiere detener modulos sanos durante toda la investigacion;
la aplicacion final si debe tener una ventana controlada sobre las escrituras afectadas.

### Fase 2. Representar y conservar el historico

Adoptar un componente `abonos_migrados` por venta y compra, de precision decimal,
administrado exclusivamente por el proceso de conciliacion. La regla propuesta es:

```text
abonos = abonos_migrados_verificados + aplicaciones_vigentes
saldo_documento = total - acuenta - abonos
```

La validacion debe detectar sobrepagos y registrarlos segun su naturaleza; usar
`max(..., 0)` por si solo oculta diferencias y no constituye una conciliacion.

Cada base historica debe quedar respaldada por una evidencia de conciliacion:
documento, origen, fecha de corte conocida, importes antes/despues, motivo, usuario
y lote. Distinguir un cero verificado de un documento todavia no conciliado.
Reutilizar y ampliar la auditoria existente cuando corresponda.

Para obtener ese componente se usa un respaldo fiable de la misma epoca:
abono del respaldo menos aplicaciones ya representadas EN ESE MISMO RESPALDO.
Luego se conserva ese componente al incorporar operaciones posteriores validas.
Comparar identidad completa, titular, total, pago inicial, estado, rectificaciones,
aplicaciones eliminadas/reasignadas y posibles importaciones posteriores.

No inicializar todas las filas automaticamente con la diferencia de la base actual:
Alex daria cero y seguiria incorrecto. Tampoco interpretar las 33,667 brechas como
importes autorizados sin validar su origen. Las excepciones de pagos negativos,
partidas repetidas o titular distinto se revisan por separado.

Si existen recibos originales fiables, recuperar sus vinculos conservando identidad
y evitando doble conteo. Si no existen, registrar una base historica identificada,
sin inventar fechas de pago ni una nueva entrada de efectivo.

Crear recibos ficticios para cerrar la diferencia alteraria caja, porque
`app/Services/ReporteCajaService.php:62` y 72 suman las cabeceras provisionales.
La regularizacion debe dejar caja sin variaciones.

Entrega: datos historicos trazables, reglas unicas de saldo y proteccion en crear,
editar, eliminar/revertir, anular, rectificar e importar para ventas y compras.

### Fase 3. Reparacion piloto y expansion controlada

1. Preparar un comando de conciliacion con simulacion por defecto. Debe mostrar
   base objetivo, documentos, evidencia, importes actuales y propuestos antes de aplicar.
2. Ensayar Alex: conservar historico 1180/5880 y aplicaciones actuales 1428/557;
   exigir saldos finales 0/234. Conservar recibos 42744 y 44043.
3. Conciliar Eliseo: historico esperado 103, saldo 76 antes de nuevos cobros.
4. En la copia, probar un nuevo cobro parcial y su reverso: el saldo disminuye
   solo por lo recibido y vuelve al valor anterior al revertirlo, sin perder historico.
5. Ampliar por lotes unicamente a registros cuya evidencia sea suficiente. Exportar
   excepciones para revision; no completar automaticamente datos desconocidos.

La aplicacion debe bloquear los registros, verificar nuevamente precondiciones,
operar en una transaccion y comprobar postcondiciones antes de confirmar. Si el
estado cambio desde la simulacion, abortar y volver a conciliar.
Cada lote tiene identificador unico: ejecutarlo otra vez no debe duplicar abonos.
Una reversa posterior debe verificar que no haya nuevas operaciones; no restaurar
valores viejos encima de cobros posteriores.

Entrega: piloto verificado y reparaciones idempotentes con evidencia antes/despues.

### Fase 4. Corregir recibos y escrituras simultaneas

Conciliar los seis recibos sobreaplicados, cinco saldos negativos, un detalle
negativo, doce relaciones de titular y doce grupos repetidos. Determinar el
importe o vinculo correcto desde el comprobante o la fuente original.

Al editar, sumar aplicaciones previas por documento. Si se decide consolidar
filas repetidas, conservar su trazabilidad e importe agregado. No borrar filas
solo por compartir recibo y documento.

Bloquear primero la cabecera del recibo; despues, la union de documentos antiguos
y nuevos en un orden comun para crear, editar y eliminar. Releer bajo bloqueo
antes de validar saldo y escribir. Probar con el aislamiento real de MySQL.

Generar numeros mediante un contador bloqueado y garantizar unicidad en la base,
definiendo previamente el alcance de la numeracion y la compatibilidad con el
historico. Agregar una clave de idempotencia a altas para evitar cobros duplicados
por doble clic o reintentos. Responder 422 a errores de validacion de negocio.

Registrar las modificaciones y reversas de recibos en la misma transaccion que
los saldos. Para periodos cerrados, usar anulacion/reverso trazable en vez de
edicion o eliminacion fisica que borre la evidencia del cierre.

### Fase 5. Reportes y fecha de corte

Separar explicitamente deuda actual de estado historico al corte. Centralizar
las consultas para que pantalla, PDF y exportacion coincidan para el mismo modo.

El modo historico usa una base de apertura verificada y movimientos efectivos
hasta la fecha solicitada, incluyendo reversas/rectificaciones pertinentes.
No debe descontar pagos futuros ni incluir ventas fuera del corte en el total.
El saldo a favor debe contemplar remanentes de recibos parcialmente aplicados.

Un acumulado historico sin fechas individuales permite fijar un corte fiable,
pero NO reconstruir todos los dias anteriores. Para fechas previas al corte
verificado se necesitan documentos originales; mientras falten, informar esa
limitacion en lugar de inventar un historial.

### Fase 6. Auditoria permanente y despliegue

Ampliar el auditor de provisionales para validar, por documento y por recibo:
historico + aplicaciones = abonos; total - inicial - abonos = saldo; distribucion
no mayor que pago; libre consistente y no negativo; titular y referencias validos;
numeracion y claves de idempotencia unicas; aplicacion compatible con estado.
Las tolerancias y excepciones de redondeo deben estar documentadas.

Separar diagnostico y reparacion. Producir un reporte estructurado, con errores
y advertencias diferenciados, y devolver codigo distinto de cero ante errores.
Corregir tambien la salida del validador de kardex.

Ejecutar los validadores al importar, antes/despues de un despliegue que afecte
saldos y en el cierre diario. Proponer alerta sobre incidencias nuevas, sin
reparaciones automaticas. Configurar respaldo periodico y ensayar recuperacion.
No se creo ninguna tarea programada durante esta revision.

El despliegue debe aplicar esquema, datos conciliados y codigo en un orden
compatible, con pausa breve de las escrituras afectadas; verificar transacciones,
pantallas y PDFs antes de reabrir. No volver al sincronizador antiguo una vez
que existan bases historicas nuevas, pues volveria a omitirlas.

### Fase 7. Kardex y controles de otros modulos

Abrir una conciliacion independiente para los 67 productos reportados. Clasificar
cadena, formula, valorizacion y saldo final; revisar aperturas, movimientos
retroactivos, anulaciones, rectificaciones y unidades sacos/kg. Verificar la
evidencia de apertura citada en `docs/kardex_reconciliacion_20260531.md`.

Ensayar cada reparacion en copia y pasar siempre por `MovimientoService`. No
modificar directamente `stock_almacen` ni ejecutar un recalculo global como
respuesta automatica al conteo del validador.

Extender despues el mismo esquema de conciliacion a caja, gastos y planilla:
origen identificable, escritura transaccional, reversas auditadas y pruebas por
operacion. Su estado completo no fue auditado en este trabajo; no se atribuyen
fallos concretos en esos modulos sin revision adicional.

## 5. Pruebas y criterios de aceptacion

Las pruebas actuales ejecutadas pasaron: 17 pruebas, 74 aserciones, SQLite en
memoria. Archivos: `CuentaCorrienteClienteControllerTest`,
`VentaRectificacionAbonosTest` y `PagoInicialTest`. Esto confirma sus casos actuales,
pero no cubre el escenario que rompio los saldos de Alex.

| Caso que debe agregarse | Resultado exigido |
|---|---|
| Alex, datos del respaldo + recibo 44043 | Saldo 234; caja sin nuevo ingreso |
| Eliseo, pago parcial y reversa | Conserva los 103 historicos; vuelve a saldo 76 |
| Venta nueva, sin historico | Mantiene el comportamiento correcto actual |
| Crear, editar, eliminar/revertir recibo con historico | Conserva siempre la base conciliada |
| Recibo repetido/reintento | Se procesa una sola vez |
| Dos cobros simultaneos a una venta | No exceden el saldo disponible |
| Ediciones simultaneas del mismo recibo | Sin aplicaciones perdidas o saldo desfasado |
| Reasignar pago entre documentos | Recalcula anteriores y nuevos de forma atomica |
| Varias filas historicas de una venta en un recibo | Suma correctamente la aplicacion previa |
| Anular/rectificar con abonos historicos | Conserva las restricciones y evidencia |
| Recibo de otro titular o documento anulado | Rechazo antes de escribir |
| Pago posterior al corte | No altera el estado historico anterior |
| Adelanto parcialmente aplicado | Remanente visible y consistente |
| Fuente historica incompleta | Caso pendiente, sin importe o fecha inventados |
| Reejecucion de conciliacion | Cero duplicaciones, mismo resultado |
| Fallo en mitad de reparacion | Rollback total de los registros del lote |
| Comparacion antes/despues de regularizar | Caja e inventario sin cambios por reparar deuda |

Las pruebas de concurrencia e indices requieren MySQL con dos conexiones reales;
SQLite no verifica esas garantias. Antes de aplicar en la base operativa, los
documentos del piloto, el reporte actual y el historico desde el corte conocido
deben coincidir con la evidencia. Los casos sin evidencia quedan identificados
y bloqueados para operaciones que pudieran destruir su historico.

## 6. Prioridad recomendada

Primero respaldo y contencion; despues componente historico, pruebas y reparacion
piloto de Alex/Eliseo. Continuar con las otras inconsistencias de recibos y las
protecciones de concurrencia, luego unificar los reportes y automatizar auditorias.
La investigacion del kardex debe avanzar como un trabajo separado con su propia
evidencia y validacion. Una correccion exclusiva de `ventas.saldo` no satisface
estos criterios y volveria a quedar expuesta al proximo recalculo.

## 7. Ejecucion del piloto: 2026-10-05

Estado: fases 1-3 ejecutadas para los tres documentos verificados. Contencion
incorporada en cobranzas y pagos de proveedores. Auditor de saldos de solo lectura
disponible. Las fases restantes NO estan terminadas ni se programaron tareas.

### Respaldo y ensayo

- Carpeta privada: `storage/app/private/respaldo_saldos_20261004_223905/`.
- `antes.sql`: 64,358,677 bytes; SHA256
  `bca5fda9e0825d78fbb427b32ec345cee433d911eeb9faaa264ae63046cf29b3`.
- Restaurado en `codex_saldos_20261004_223905`; comparadas las 81 tablas por
  CHECKSUM TABLE, sin diferencias. Evidencia: `verificacion.json`.
- Respaldo adicional inmediatamente antes de aplicar:
  `antes_aplicar_20261005_003817.sql`; SHA256
  `e62cb9e125fb7bd97d7cb4c70ad9437b0e3d98a713e27f91342b734c781ef674`.
- Migracion especifica ensayada en la copia; simulacion sin escrituras, aplicacion
  y segunda ejecucion idempotente comprobadas. Pruebas MySQL de crear/editar/borrar
  un pago de Eliseo y borrar el recibo 44043 de Alex, dentro de una transaccion
  revertida. Eliminar ese recibo devuelve Alex a 368 + 791 = 1,159.

### Datos aplicados

Base operativa comprobada: `03/10/2026`. No se cambio `.env`. Se uso mantenimiento
breve con reapertura en `finally`; aplicacion operativa al terminar.

| Venta | Titular | Historico conciliado | Abonos finales | Saldo final |
|---|---|---:|---:|---:|
| 118388 | Alex Cubas, 2 | 1,180.00 | 2,608.00 | 0.00 |
| 118981 | Alex Cubas, 2 | 5,880.00 | 6,437.00 | 234.00 |
| 90582 | Eliseo Fernandez, 57 | 103.00 | 103.00 | 76.00 |

Se conservaron los recibos originales, incluido el 44043 por 925.00. No se crearon
ingresos de caja ni movimientos de inventario para justificar los historicos.
Comparacion posterior a migracion: solo cambiaron datos de `ventas` y
`saldo_conciliaciones`. El hash de todas las ventas fuera del piloto no cambio.
Recibos, aplicaciones, compras, productos y movimientos conservaron sus checksums.
Registro de aplicacion: `aplicacion.json` en la carpeta privada del respaldo.

El PDF generado por el controlador real con el rango solicitado muestra 234.00,
las dos ventas y los recibos 42744 y 44043. Se reviso la imagen del PDF de ensayo.
No se modifico el calculo temporal de los reportes: sigue siendo deuda actual,
no una reconstruccion contable garantizada a cualquier fecha anterior.

### Protecciones incorporadas

- `abonos_migrados` nullable en ventas/compras, fuera de asignacion masiva.
  NULL no autoriza a inferir un historico: solo se permite operar si el documento
  cuadra con las aplicaciones existentes y un historico cero. Si no cuadra, 422.
- `SaldoDocumentoService`: historico + aplicaciones = abonos; credito - abonos =
  saldo. Rechaza negativos, referencias sin recibo y aplicaciones de otro titular.
- Los servicios bloquean recibo y documentos anteriores/nuevos, validan antes de
  modificar, preservan historicos y suman todas las filas previas de un documento.
- `saldos:conciliar-historico`: simula por defecto; requiere fuente verificable,
  identidad, importes esperados y continuidad de aplicaciones del respaldo.
  Lote atomico, evidencia JSON y huella; reejecucion no borra pagos posteriores.
- Los controladores conservan los errores de validacion como 422 en vez de 500.
- `saldos:auditar`: solo lectura, resumen y JSON; codigo 1 con incidencias.
  No detecta historicos perdidos que ya cuadren internamente: hace falta comparar
  contra evidencia externa, como se hizo con Alex.

### Comandos de operacion

```bash
# Auditoria general, sin escrituras (codigo 1 significa revisar incidencias)
php artisan saldos:auditar
php artisan saldos:auditar --json
php artisan saldos:auditar --tipo=ventas --titular=2

# Simulacion del lote revisado; si ya se aplico muestra YA APLICADO
php artisan saldos:conciliar-historico database/data/saldos_historicos_piloto_20261005.json
```

Para nuevas conciliaciones: respaldo/restauracion comprobada, manifiesto separado
con precondiciones revisadas, ensayo en copia con `--ensayo`, comprobacion del
resultado y aplicacion con `--apply` durante una ventana controlada. No editar el
manifiesto de un lote ya aplicado ni rellenar historicos por diferencia sin fuente.
No ejecutar todas las migraciones pendientes: en este despliegue se aplico solo
`2026_10_04_000001_add_historico_to_saldos.php`.

No retirar la columna ni volver al sincronizador anterior despues de conciliar.
Un rollback operativo exige preservar operaciones posteriores y restaurar una
combinacion compatible de codigo/datos; nunca importar el respaldo ciegamente
sobre una base que haya seguido recibiendo operaciones.

### Pendientes detectados, no reparados

Auditoria general posterior guardada como `auditoria-general.json`:

| Tipo | Cantidad |
|---|---:|
| Ventas con historico sin conciliar | 33,666 |
| Documentos con importes negativos | 5 |
| Recibos sobreaplicados o negativos | 6 |
| Aplicaciones de otro titular | 12 |
| Aplicaciones negativas | 1 |

Las 33,666 ventas historicas pendientes estan pagadas; no son 33,666 nuevas deudas.
Alex tiene 451 de esos documentos antiguos y Eliseo 6. No se alteraron sus saldos.
Una operacion sobre un documento inconsistente queda bloqueada para no destruir
su evidencia; los documentos normales que cuadran siguen admitiendo pagos.

Restan la conciliacion documentada de esas excepciones, numeracion unica e
idempotencia de recibos, pruebas con conexiones concurrentes reales, auditoria de
ediciones/reversas, modo historico de reportes, automatizacion de controles y
respaldo fuera de este equipo. El diagnostico/reparacion de kardex sigue separado.
El auditor actual tampoco sustituye el historial de auditoria de cada operacion.

### Pruebas de esta entrega

Regresion focalizada: 31 pruebas, 135 aserciones, todas correctas en SQLite en
memoria. Incluye los casos existentes de reportes y rectificaciones, conservacion
del historico en ambos dominios, reasignacion, sobrepago, aplicaciones repetidas,
documentos nuevos, bloqueo sin escrituras, simulacion, idempotencia tras pagos
posteriores, rollback de lote y auditoria sin reparacion.

SQLite no prueba concurrencia de MySQL. Los ensayos adicionales en la copia MySQL
fueron secuenciales y no justifican afirmar que la fase de concurrencia termino.

## 8. Proteccion de recibos: continuacion del 2026-10-05

Se implemento la parte preventiva de la fase 4 para ventas y compras:

- Numeracion con contador persistido, bloqueado antes de cabeceras/documentos.
  Crear, editar y eliminar toman el mismo bloqueo por modulo. Las operaciones
  de ese modulo se serializan; ventas y compras tienen contadores independientes.
  El numero no se reutiliza si se elimina el ultimo recibo. Las transacciones
  reintentan hasta tres veces ante un deadlock reconocido por Laravel.
- Indices unicos de `numero_recibo` en ambas tablas. La migracion rechaza datos
  duplicados antes de crear las estructuras; no renumera recibos historicos.
- Clave UUID por formulario nuevo. Un reintento devuelve el recibo existente;
  la misma clave con otro pago se rechaza. La clave sobrevive a una eliminacion
  para que un reintento tardio no cree otra cobranza. Las claves estan separadas
  por modulo. Interfaces antiguas y llamadas directas sin clave no tienen esta
  garantia; deben enviar `idempotencia_clave` para aprovecharla.
- Huella del recibo y sus aplicaciones en los formularios de edicion. Si otro
  usuario modifica el registro, se rechaza la version obsoleta con mensaje 422.
  Los clientes externos deben reenviar `version_recibo` obtenido del endpoint
  de consulta. La numeracion y los limites de saldo se protegen igualmente
  aunque un cliente antiguo no envie esa huella.
- Validacion de la cabecera original, su distribucion y medios de pago antes de
  editar/eliminar. Entradas negativas o pagos cero se rechazan desde el servicio.
- Creacion, modificacion y eliminacion se registran en `auditoria_eventos` dentro
  de la misma transaccion. Quedan titular, fecha, medios, montos y aplicaciones
  anteriores/nuevas. La auditoria mantiene su consulta protegida por permisos y
  permite filtrar los nuevos eventos. Si no puede guardarse, se revierte todo.
- El formulario agrupa las aplicaciones historicas repetidas del mismo documento
  al editarlo y muestra los mensajes de conciliacion/version, incluidos los que
  corresponden a campos internos. La UUID usa `crypto.getRandomValues`, compatible
  con los navegadores que no ofrecen `randomUUID` en conexiones HTTP locales.

### Despliegue y evidencia

Migracion especifica: `2026_10_05_000001_protect_provisional_receipts.php`, ensayada
primero en la copia y aplicada en `03/10/2026`. Respaldo adicional previo:
`antes_proteger_recibos_20261005_101144.sql`, en la misma carpeta privada, SHA256
`af6d5e4c4be81b54535fdf7a3cfee18dd58e0c8711215feca5a15e558164ab69`.
No se cambiaron importes historicos durante este despliegue. Contadores iniciales:
ventas 44099 y compras 478. Se comprobaron los indices unicos en la base operativa.

Pruebas completas: **105 pruebas, 549 aserciones**, correctas en SQLite en memoria.
Se verificaron reintentos, clave conflictiva/eliminada, numeracion sin reutilizacion,
version obsoleta, snapshots de auditoria y rollback de recibo/saldo/contador/clave
ante un fallo de auditoria. Tambien se probaron los filtros y permisos de auditoria.

Ensayo adicional con dos procesos y conexiones MySQL reales en la copia:

| Escenario simultaneo | Resultado en ventas y compras |
|---|---|
| Misma clave y pago | Un recibo y una aplicacion; ambas respuestas refieren al mismo ID |
| Dos pagos que juntos exceden el saldo | Primero aceptado; segundo 422, sin sobrepago |
| Dos adelantos diferentes | Dos numeros consecutivos y unicos |
| Dos ediciones de la misma version | Primera aceptada; segunda 422, conserva la primera |

En cada caso el segundo proceso espero efectivamente el bloqueo del primero.
Se eliminaron solo los recibos generados por el ensayo y se comprobaron los
saldos originales; los contadores/claves/auditorias del ensayo permanecen en
la copia para demostrar que no se reutilizan numeros ni claves.
Evidencia: `storage/app/private/concurrencia_recibos_20261005_101752.json`.

```bash
# Solo admite copias con nombre codex_saldos_*; nunca la base operativa
php tests/Support/ProvisionalMySqlConcurrency.php codex_saldos_20261004_223905
```

Las dos vistas Blade se compilaron usando la copia. Se verificaron sus scripts con
Node: 1000 UUIDs validas distintas, agrupacion de aplicaciones y mensajes 422.
Recargar las pestanas abiertas de cobranzas/pagos para cargar los nuevos campos.
Cualquier otra base a la que se cambie `.env` debe recibir estas migraciones antes
de registrar o modificar recibos; los reportes de lectura no sustituyen el esquema.

### Trabajo restante del plan

Las seis sobreaplicaciones, las doce aplicaciones de otro titular, los negativos
y los historicos antiguos conservan sus importes originales. Necesitan evidencia
documental antes de decidir que recibo o documento corregir. La auditoria nueva
empieza con este despliegue; no reconstruye ediciones previas desconocidas.

Quedan el modo historico verificable de reportes, el tratamiento de periodos
cerrados mediante reversas, los controles y respaldos periodicos fuera del equipo,
y la conciliacion independiente de kardex. La serializacion de recibos no corrige
operaciones efectuadas por importaciones o SQL externos que evadan los servicios.
