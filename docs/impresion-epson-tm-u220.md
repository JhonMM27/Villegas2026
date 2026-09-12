# Impresión de tickets Epson TM-U220

La TM-U220 es matricial de impacto y utiliza cinta ERC-38. El perfil inicial es
rollo de **76 mm**, contenido de **63 mm**, margen izquierdo **6,8 mm**. Confirme
el rollo físico y la configuración de la impresora antes de calibrar.

## Prueba física inicial

1. Con papel y tapa cerrada, apague el equipo.
2. Mantenga FEED presionado mientras lo enciende.
3. Cuando imprima su configuración y espere, presione FEED para imprimir los caracteres.
4. Si también salen deformados, revise la colocación/estado de la cinta ERC-38.
   Si persiste con cinta en buen estado, solicite revisión técnica. No desmonte el cabezal.
5. Compare original y copia si utiliza papel autocopiativo.

La autoprueba no depende de Laravel. Si se ve bien y el PDF no, revise el
controlador, las dimensiones y el escalado antes de modificar nuevamente fuentes.

## Configuración de Windows y navegador

- Seleccione el controlador Epson TM-U220 compatible con su Windows y conexión.
- Seleccione rollo de 76 mm y tamaño real / 100 %, una página por hoja.
- Desactive ajustes automáticos de escala. No use un formulario A4 ni 80 mm.
- Los márgenes físicos ya están incluidos en el PDF; no agregue un segundo margen.
- Verifique en el controlador cuándo se corta el papel. El modelo D no tiene autocortador.
- No configure densidad térmica: este equipo imprime con cinta.

## Calibración reproducible

Abra `/tickets/calibracion` con una sesión iniciada, o ejecute:

```sh
php artisan tickets:calibracion
```

El comando guarda `output/pdf/calibracion-epson-tm-u220.pdf` sin consultar ni
modificar registros. Se puede elegir destino con `--output=...`.
La regla del PDF debe medir **60 mm** en papel. Si no coincide, corrija escala o
formulario del controlador; no compense el error reduciendo el CSS.

El perfil compacto de las 18 plantillas usa DejaVu Sans de 10 pt para texto y
cifras, fecha/hora de 9 pt y totales de 12 pt, con interlineado de 1,1. Sus estilos
están en `tickets/compact-styles.blade.php`; la muestra de calibración mantiene
las variantes Sans y Mono para comparación. Confirme especialmente 6/8, 0/9,
16.00/18.00 y 938.00 en papel.
El PDF usa fuentes incrustadas; no activa las fuentes internas ESC/POS.

## Configuración de la aplicación

`config/tickets.php` centraliza dimensiones, márgenes y tamaños base. El ancho derecho
se calcula como papel menos margen izquierdo menos contenido. El perfil solo
afecta tickets: conserva los PDF A4, datos, rutas y permisos de negocio.

El máximo inicial es **297 mm por página**, un valor conservador pendiente de
verificar contra el controlador instalado. Los tickets cortos se recortan a la
altura medida; los largos se paginan con esa altura máxima. La última página de
un ticket largo conserva el tamaño máximo. Cada bloque de producto permanece
unido siempre que quepa en una página. No se reduce automáticamente la fuente.

Si el controlador admite formularios más largos, establezca su límite comprobado
en `max_height_mm` y repita las pruebas de 1, 10 y 50 productos. No use una altura
arbitrariamente grande sin verificar el controlador. Si usa configuración cacheada,
regenere la caché durante el despliegue con el procedimiento habitual del proyecto.

`TicketPdfService` mide el marcador final del documento y usa una instancia nueva
de Dompdf para la salida. Las plantillas comparten estilos y bloques de producto.
Los insumos de preparadas conservan sus cantidades y cálculos existentes; no se
modifica stock ni se deducen unidades nuevas a partir de nombres de columnas.

Ventas, cotizaciones, compras, preparadas, núcleos preparados y préstamos muestran
el nombre del producto seguido de una fila con cantidad, P.Unit e importe. Los
encabezados aparecen una vez y la moneda se indica sobre el detalle. Cantidad y
unidad se mantienen juntas; las columnas ocupan 40 %, 27 % y 33 %. Se miden los
textos con las métricas de DejaVu Sans: solo los valores que no caben pasan a una
fila adicional identificada, sin reducir la fuente. Todos los tickets comparten
resúmenes compactos y sus datos propios; los de pagos, gastos, planilla y saldos
también usan este perfil.

La muestra de MEDIA (15 × 77), CRECIMIENTO CERDOS I (3 × 115) y CRECIMIENTO
CERDOS II (2 × 110), total S/ 1,720.00, pasó de 720,30 a 460,65 puntos de altura
PDF con los mismos datos: aproximadamente 36 % menos papel. La comparación
física se hace a tamaño real, nunca con ajuste automático de escala.

## Aceptación pendiente en el puesto de impresión

- Regla de 60 mm correcta, sin recortes laterales.
- Números 6/8 y 0/9 inequívocos en original y, si corresponde, copia.
- Cantidades, precios, totales y cobranza completos.
- Ticket largo sin productos separados entre páginas ni totales duplicados.
- Registrar versión del controlador, conexión, ancho del rollo y resultado.

Si las fuentes internas de la autoprueba son claras pero el PDF sigue sin cumplir,
se requiere una integración ESC/POS adicional; no está incluida en este cambio.

## Fuentes oficiales

- [TM-U220: manuales y especificaciones](https://support.epson.net/publist/bsmanual.php?lang=EN&model=TM-U220)
- [Guía técnica: autoprueba, papel y cinta](https://files.support.epson.com/pdf/pos/bulk/tm-u220_trg_en_std_revh.pdf)
- [Soporte y controladores](https://epson.com/Support/Point-of-Sale/Impact-Printers-%28Dot-Matrix%29/Epson-TM-U220/s/SPT_C31C514103)
