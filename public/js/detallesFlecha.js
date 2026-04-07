(function enableArrowNavigationForDetalles() {
    const TABLE_ID = 'tablaDetalles';
    const ENABLE_CTRL_TO_MOVE = false;

    const COBRANZA_FIELDS = ['principal', 'deposito', 'consorcio'];
    const TABLE_ENTER_SEQUENCE = {
        'inputCantidad': 'inputPrecioUnitario',
        'inputPrecioUnitario': 'producto_nombre'
    };
    const ENTER_SEQUENCE = {
        'cliente_razon_social': 'comprobante_tipo_codigo',
        'comprobante_tipo_codigo': 'serie',
        'serie': 'correlativo',
        'correlativo': 'docpagoi',
        'docpagoi': 'cobranza_tipo_id',
        'cobranza_tipo_id': 'pago_forma_codigo',
        'pago_forma_codigo': 'fecha_venta',
        'fecha_venta': 'fecha_vencimiento',
        'fecha_vencimiento': 'principal',
        'principal': 'deposito',
        'deposito': 'consorcio',
        'consorcio': 'producto_nombre',
        'producto_nombre': 'cantidad'
    };

    function getTableFieldClass(el) {
        if (!el) return null;
        const classes = el.className?.split(' ') || [];
        if (classes.includes('inputCantidad')) return 'inputCantidad';
        if (classes.includes('inputPrecioUnitario')) return 'inputPrecioUnitario';
        return null;
    }

    function isEditableField(el) {
        if (!el) return false;
        const tag = el.tagName?.toLowerCase();
        if (tag === 'input') return !el.disabled && !el.readOnly && el.type !== 'hidden';
        if (tag === 'select' || tag === 'textarea') return !el.disabled && !el.readOnly;
        return false;
    }

    function getCell(el) {
        return el?.closest?.('td,th') || null;
    }

    function getRow(el) {
        return el?.closest?.('tr') || null;
    }

    function getTable() {
        return document.getElementById(TABLE_ID);
    }

    function getTbodyRows(table) {
        const tbody = table?.querySelector('tbody');
        if (!tbody) return [];
        return [...tbody.querySelectorAll('tr')].filter(r => !r.classList.contains('d-none'));
    }

    function getColIndex(cell) {
        const row = cell.parentElement;
        const cells = [...row.children].filter(c => c.tagName === 'TD' || c.tagName === 'TH');
        return cells.indexOf(cell);
    }

    function focusFirstFieldInCell(cell) {
        if (!cell) return false;
        const field = cell.querySelector('input:not([type="hidden"]), select, textarea');
        if (field && isEditableField(field)) {
            field.focus({ preventScroll: true });
            if (field.tagName.toLowerCase() === 'input') field.select?.();
            field.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            return true;
        }
        return false;
    }

    function findNextFocusableInRow(row, startCol, step) {
        if (!row) return null;
        const cells = [...row.children].filter(c => c.tagName === 'TD' || c.tagName === 'TH');
        for (let c = startCol; c >= 0 && c < cells.length; c += step) {
            if (focusFirstFieldInCell(cells[c])) return true;
        }
        return false;
    }

    function moveFocus(activeEl, dir) {
        const table = getTable();
        if (!table) return;

        const cell = getCell(activeEl);
        const row = getRow(activeEl);
        if (!cell || !row) return;

        const rows = getTbodyRows(table);
        const rowIndex = rows.indexOf(row);
        if (rowIndex === -1) return;

        const colIndex = getColIndex(cell);

        const getCellAt = (r, c) => {
            const targetRow = rows[r];
            if (!targetRow) return null;
            const cells = [...targetRow.children].filter(x => x.tagName === 'TD' || x.tagName === 'TH');
            return cells[c] || null;
        };

        if (dir === 'up' || dir === 'down') {
            const step = (dir === 'up') ? -1 : 1;
            for (let r = rowIndex + step; r >= 0 && r < rows.length; r += step) {
                const targetCell = getCellAt(r, colIndex);
                if (focusFirstFieldInCell(targetCell)) return true;
                const targetRow = rows[r];
                if (findNextFocusableInRow(targetRow, colIndex + 1, +1)) return true;
                if (findNextFocusableInRow(targetRow, colIndex - 1, -1)) return true;
            }
            return false;
        }

        if (dir === 'left' || dir === 'right') {
            const step = (dir === 'left') ? -1 : 1;
            const cells = [...row.children].filter(c => c.tagName === 'TD' || c.tagName === 'TH');
            for (let c = colIndex + step; c >= 0 && c < cells.length; c += step) {
                if (focusFirstFieldInCell(cells[c])) return true;
            }
            return false;
        }
    }

    function shouldHijackArrow(e, el) {
        if (!ENABLE_CTRL_TO_MOVE) return true;
        if (e.ctrlKey) return true;
        const tag = el?.tagName?.toLowerCase();
        if (tag === 'input' || tag === 'textarea') return false;
        if (tag === 'select') return true;
        return false;
    }

    function moveToCobranza(currentField, direction) {
        const currentIndex = COBRANZA_FIELDS.indexOf(currentField);
        if (currentIndex === -1) {
            focusField('principal');
            return;
        }
        let nextIndex;
        if (direction === 'down') {
            nextIndex = (currentIndex + 1) % COBRANZA_FIELDS.length;
        } else {
            nextIndex = (currentIndex - 1 + COBRANZA_FIELDS.length) % COBRANZA_FIELDS.length;
        }
        focusField(COBRANZA_FIELDS[nextIndex]);
    }

    function focusField(id) {
        const field = document.getElementById(id);
        if (field) {
            field.focus({ preventScroll: true });
            if (field.tagName.toLowerCase() === 'input') {
                field.select?.();
            }
            field.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
    }

    function focusFieldInLastRow(className) {
        const table = getTable();
        if (!table) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        const rows = [...tbody.querySelectorAll('tr')].filter(r => !r.classList.contains('d-none'));
        if (rows.length === 0) return;
        const lastRow = rows[rows.length - 1];
        const input = lastRow.querySelector('.' + className);
        if (input) {
            input.focus({ preventScroll: true });
            input.select?.();
            input.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
    }

    function focusFirstCantidadInTable() {
        focusFieldInLastRow('inputCantidad');
    }

    document.addEventListener('keydown', function(e) {
        const active = document.activeElement;
        if (!active) return;

        const fieldId = active.id;
        const key = e.key;

        const map = {
            ArrowUp: 'up',
            ArrowDown: 'down',
            ArrowLeft: 'left',
            ArrowRight: 'right'
        };

        if (key === 'Enter') {
            const table = getTable();
            if (table && table.contains(active)) {
                const fieldClass = getTableFieldClass(active);
                if (fieldClass) {
                    e.preventDefault();
                    const nextClass = TABLE_ENTER_SEQUENCE[fieldClass];
                    if (nextClass === 'inputPrecioUnitario') {
                        focusFieldInLastRow('inputPrecioUnitario');
                    } else if (nextClass === 'producto_nombre') {
                        focusField('producto_nombre');
                    } else if (nextClass === 'inputCantidad') {
                        focusFirstCantidadInTable();
                    }
                    return;
                }
            }
            if (fieldId === 'producto_nombre') {
                e.preventDefault();
                focusFirstCantidadInTable();
                return;
            }
            const nextField = ENTER_SEQUENCE[fieldId];
            if (nextField) {
                e.preventDefault();
                if (nextField === 'cantidad') {
                    focusFirstCantidadInTable();
                } else {
                    focusField(nextField);
                }
                return;
            }
        }

        if (key === 'ArrowDown' || key === 'ArrowUp') {
            if (COBRANZA_FIELDS.includes(fieldId)) {
                e.preventDefault();
                const dir = map[key];
                moveToCobranza(fieldId, dir);
                return;
            }
        }

        const table = getTable();
        if (!table) return;
        if (!table.contains(active)) return;
        if (!isEditableField(active)) return;

        const dir = map[key];
        if (!dir) return;
        if (!shouldHijackArrow(e, active)) return;

        e.preventDefault();
        moveFocus(active, dir);
    }, true);

    window.focusFirstCantidadInTable = focusFirstCantidadInTable;
})();