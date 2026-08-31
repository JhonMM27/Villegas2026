(function () {
    'use strict';

    const config = window.auditoriaConfig;
    const escapeHtml = (value) => String(value ?? '—').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
    }[character]));

    const formatValue = (value, format) => {
        if (value === null || value === undefined || value === '') return '—';
        if (format === 'moneda') return `S/ ${Number(value).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (format === 'cantidad' || format === 'costo') return Number(value).toLocaleString('es-PE', { maximumFractionDigits: 4 });
        if (format === 'fecha') {
            const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})(.*)$/);
            return match ? `${match[3]}/${match[2]}/${match[1]}${match[4]}` : value;
        }
        return value;
    };

    const renderRow = (change) => {
        const labels = { agregado: 'Agregado', eliminado: 'Eliminado', modificado: 'Modificado' };
        return `<tr><td><span class="fw-semibold">${escapeHtml(change.campo)}</span><span class="rect-change-badge rect-change-${escapeHtml(change.tipo)}">${labels[change.tipo] || 'Cambio'}</span></td><td class="rect-value-old">${escapeHtml(formatValue(change.anterior, change.formato))}</td><td class="rect-value-new">${escapeHtml(formatValue(change.nuevo, change.formato))}</td></tr>`;
    };

    const renderSection = (title, icon, changes) => changes.length ? `<div class="rect-section-title"><i class="bi ${icon}"></i>${title}<span class="rect-section-count">${changes.length}</span></div><div class="table-responsive rect-diff-table"><table class="table table-sm table-bordered align-middle"><thead><tr><th>Campo</th><th>Valor anterior</th><th>Valor nuevo</th></tr></thead><tbody>${changes.map(renderRow).join('')}</tbody></table></div>` : '';

    const renderEvent = (event) => {
        const header = event.cambios?.cabecera || [];
        const details = event.cambios?.detalles || [];
        const title = event.numero_rectificacion ? `${event.accion} ${event.numero_rectificacion}` : event.accion;
        const changes = header.length || details.length
            ? `${renderSection('Cambios de cabecera', 'bi-card-text', header)}${renderSection('Cambios en productos y detalles', 'bi-box-seam', details)}`
            : '<div class="alert alert-secondary mb-0"><i class="bi bi-info-circle me-2"></i>La operación se registró sin diferencias adicionales de datos.</div>';

        return `<div class="rect-summary"><div class="rect-summary-icon"><i class="bi bi-shield-check"></i></div><div><strong>${escapeHtml(title)} protegida</strong><div class="small text-body-secondary">Este evento es inmutable y forma parte del control interno.</div></div></div><div class="card rect-history-card"><div class="card-header rect-card-header d-flex flex-wrap justify-content-between gap-2"><span class="rect-number"><i class="bi bi-clock-history"></i>${escapeHtml(title)}</span><span class="small text-body-secondary"><i class="bi bi-calendar3 me-1"></i>${escapeHtml(event.fecha)} · <i class="bi bi-person me-1"></i>${escapeHtml(event.user_nombre)}</span></div><div class="card-body p-3 p-md-4"><div class="rect-meta-grid"><div class="rect-meta-item"><span class="rect-meta-label">Referencia</span>${escapeHtml(event.registro_referencia)}</div><div class="rect-meta-item"><span class="rect-meta-label">Motivo</span>${escapeHtml(event.motivo || 'Sin motivo registrado')}</div></div>${changes}</div></div>`;
    };

    $(function () {
        let eventoActivoId = null;
        const printButton = document.getElementById('auditoriaImprimirPdf');
        const table = $('#auditoriaTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            order: [],
            ajax: {
                url: config.dataUrl,
                data: (data) => Object.assign(data, {
                    buscar: $('#auditBuscar').val(),
                    user_nombre: $('#auditUsuario').val(),
                    tipo_evento: $('#auditAccion').val(),
                    modulo: $('#auditModulo').val(),
                    desde: $('#auditDesde').val(),
                    hasta: $('#auditHasta').val(),
                }),
            },
            columns: [
                { data: 'fecha', name: 'created_at' },
                { data: 'hora', name: 'created_at', orderable: false },
                { data: 'user_nombre', name: 'user_nombre' },
                { data: 'accion', name: 'tipo_evento' },
                { data: 'modulo_nombre', name: 'modulo' },
                { data: 'registro_referencia', name: 'registro_referencia' },
                { data: 'cambio', orderable: false, searchable: false },
            ],
            language: { url: '/datatables/i18n/es-ES.json' },
            pageLength: 25,
        });

        $('#auditoriaFiltros').on('submit', function (event) { event.preventDefault(); table.ajax.reload(); });
        $('#auditHoy').on('click', function () {
            const now = new Date();
            const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
            ['auditDesde', 'auditHasta'].forEach((id) => {
                const input = document.getElementById(id);
                if (input?._flatpickr) input._flatpickr.setDate(today, false, 'Y-m-d');
                else if (input) input.value = today;
            });
        });
        $('#auditLimpiar').on('click', function () {
            document.getElementById('auditoriaFiltros').reset();
            ['auditDesde', 'auditHasta'].forEach((id) => document.getElementById(id)?._flatpickr?.clear());
            table.ajax.reload();
        });

        document.addEventListener('click', async (clickEvent) => {
            const button = clickEvent.target.closest('.btn-ver-auditoria');
            if (!button) return;
            const modalElement = document.getElementById('rectificacionHistorialModal');
            const content = document.getElementById('rectificacionHistorialContenido');
            const subtitle = document.getElementById('rectificacionHistorialSubtitulo');
            const title = document.getElementById('auditoriaDetalleTitulo');
            const icon = document.getElementById('auditoriaDetalleIcono');
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            eventoActivoId = null;
            printButton.disabled = true;
            modalElement.classList.remove('audit-event-cancel');
            title.textContent = 'Detalle de auditoría';
            icon.className = 'bi bi-shield-check';
            content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
            subtitle.textContent = 'Consultando evento protegido…';
            modal.show();

            try {
                const response = await fetch(`${config.showUrl}/${button.dataset.id}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('No se pudo consultar el evento de auditoría.');
                const data = await response.json();
                const isCancellation = data.tipo_evento === 'anulacion';
                modalElement.classList.toggle('audit-event-cancel', isCancellation);
                title.textContent = `Detalle de ${data.accion.toLowerCase()}`;
                icon.className = isCancellation ? 'bi bi-x-octagon' : 'bi bi-pencil-square';
                subtitle.textContent = `${data.modulo_nombre} · ${data.registro_referencia}`;
                content.innerHTML = renderEvent(data);
                eventoActivoId = data.id;
                printButton.disabled = false;
            } catch (error) {
                content.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error.message)}</div>`;
            }
        });

        printButton.addEventListener('click', () => {
            if (!eventoActivoId) return;
            window.open(`${config.pdfUrl}/${eventoActivoId}/pdf`, '_blank', 'noopener');
        });

        document.getElementById('rectificacionHistorialModal').addEventListener('hidden.bs.modal', () => {
            eventoActivoId = null;
            printButton.disabled = true;
        });
    });
}());
