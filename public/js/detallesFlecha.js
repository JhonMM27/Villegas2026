(function enableArrowNavigationForDetalles() {
    const TABLE_ID = 'tablaDetalles'; // tu tabla de detalles
    const ENABLE_CTRL_TO_MOVE = false; // true => usar Ctrl+Flecha para no interferir al editar

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
        // index real en la fila (incluye columnas sin input)
        const row = cell.parentElement;
        const cells = [...row.children].filter(c => c.tagName === 'TD' || c.tagName === 'TH');
        return cells.indexOf(cell);
    }

    function focusFirstFieldInCell(cell) {
        if (!cell) return false;
        const field = cell.querySelector('input:not([type="hidden"]), select, textarea');
        if (field && isEditableField(field)) {
            field.focus({ preventScroll: true });
            // opcional: seleccionar todo si es input
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

        // Helper: obtener celda por (r,c)
        const getCellAt = (r, c) => {
            const targetRow = rows[r];
            if (!targetRow) return null;
            const cells = [...targetRow.children].filter(x => x.tagName === 'TD' || x.tagName === 'TH');
            return cells[c] || null;
        };

        // UP / DOWN: misma columna, distinta fila
        if (dir === 'up' || dir === 'down') {
            const step = (dir === 'up') ? -1 : 1;
            for (let r = rowIndex + step; r >= 0 && r < rows.length; r += step) {
                const targetCell = getCellAt(r, colIndex);

                // Si esa celda no tiene input, intentamos buscar en esa fila la más cercana:
                if (focusFirstFieldInCell(targetCell)) return true;

                // Buscar hacia la derecha y luego izquierda en esa fila
                const targetRow = rows[r];
                if (findNextFocusableInRow(targetRow, colIndex + 1, +1)) return true;
                if (findNextFocusableInRow(targetRow, colIndex - 1, -1)) return true;
            }
            return false;
        }

        // LEFT / RIGHT: misma fila, distinta columna
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
        // si quieres que SIEMPRE navegue con flechas, pon ENABLE_CTRL_TO_MOVE = false
        if (!ENABLE_CTRL_TO_MOVE) return true;

        // con Ctrl sí navegamos
        if (e.ctrlKey) return true;

        // sin Ctrl, dejamos el comportamiento normal de edición
        // (mover cursor en input number/text)
        const tag = el?.tagName?.toLowerCase();
        if (tag === 'input' || tag === 'textarea') return false;

        // en select sí podemos navegar
        if (tag === 'select') return true;

        return false;
    }

    document.addEventListener('keydown', function(e) {
        const table = getTable();
        if (!table) return;

        const active = document.activeElement;
        if (!active) return;

        // solo dentro de la tabla de detalles
        if (!table.contains(active)) return;

        // solo si es un campo editable
        if (!isEditableField(active)) return;

        // flechas
        const key = e.key;
        const map = {
            ArrowUp: 'up',
            ArrowDown: 'down',
            ArrowLeft: 'left',
            ArrowRight: 'right'
        };
        const dir = map[key];
        if (!dir) return;

        if (!shouldHijackArrow(e, active)) return;

        e.preventDefault();
        moveFocus(active, dir);
    }, true);
})();