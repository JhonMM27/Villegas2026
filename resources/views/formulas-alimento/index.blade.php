{{-- ============================================================
    Fórmulas de Alimento — Index
    - DataTable con listado de fórmulas
    - Modal fullscreen para crear/editar con ingredientes inline
    - Modal de solo lectura para ver
    - Recálculo en tiempo real de costos y nutrientes
============================================================ --}}
@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">
                        <i class="bi bi-calculator me-2"></i>Fórmulas de Alimento
                    </h3>
                    <a href="{{ route('ration-formulation.datos') }}" class="btn btn-secondary btn-sm me-2">
                        <i class="bi bi-bar-chart me-1"></i>Datos Nutricionales
                    </a>
                    @can('formulas_alimento_create')
                    <button type="button" class="btn btn-primary" id="btnCreate">
                        <i class="bi bi-plus-circle me-1"></i>Nueva Fórmula
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="formulasTable" class="table table-striped table-hover table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 90px;">Opciones</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Kg/Saco</th>
                                    <th class="text-end">Precio Venta</th>
                                    <th class="text-center"># Ingredientes</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Incluir modales --}}
@canany(['formulas_alimento_create', 'formulas_alimento_edit'])
    @include('formulas-alimento.formula-action')
@endcanany

@include('formulas-alimento.formula-view')
@include('formulas-alimento.export-modal')

<div id="modalContainer"></div>
@endsection

@push('scripts')
<script>
/**
 * FormulaAlimentoManager
 * Gestiona CRUD de fórmulas + ingredientes inline con recálculo en tiempo real.
 * Extiende CrudManager para reusar fetch, notificaciones, etc.
 */
class FormulaAlimentoManager extends CrudManager {
    constructor() {
        super();
        this.formulaId = null;
        this.baseUrl = '{{ url("formulas-alimento") }}';

        this.ingredientesCache = {};
        this.ingredientesEnTabla = [];
        this.searchResultsElements = [];
        this.searchHighlightedIndex = -1;

        this.init();
    }

    /* =====================================================
       INICIALIZACIÓN
    ====================================================== */
    init() {
        this.initDataTable();
        this.setupEventListeners();
        this.setupModalFocusFix();
    }

    setupModalFocusFix() {
        const modalEl = document.getElementById('modalFormula');
        if (!modalEl) return;

        modalEl.addEventListener('hide.bs.modal', () => {
            if (modalEl.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        });
    }

    /**
     * DataTable principal del listado de fórmulas
     */
    initDataTable() {
        this.tabla = $('#formulasTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: '{{ route("formulas-alimento.datatable") }}', type: 'GET' },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'nombre', name: 'nombre' },
                { data: 'descripcion', name: 'descripcion' },
                { data: 'fecha', name: 'fecha' },
                { data: 'kg_saco', name: 'kg_saco', className: 'text-end' },
                { data: 'precio_venta', name: 'precio_venta', className: 'text-end' },
                { data: 'detalles_count', name: 'detalles_count', className: 'text-center', searchable: false },
                { data: 'activo', name: 'activo', searchable: false }
            ],
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[1, 'asc']],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
            responsive: true
        });
    }

    /* =====================================================
       EVENT LISTENERS
    ====================================================== */
    setupEventListeners() {
        const self = this;

        // Botón crear
        document.getElementById('btnCreate')?.addEventListener('click', () => self.showCreateModal());

        // Delegación de eventos para botones dinámicos del DataTable
        document.body.addEventListener('click', async (e) => {
            if (e.target.closest('.btn-view-formula')) {
                const id = e.target.closest('.btn-view-formula').dataset.id;
                self.verFormula(id);
            }
            if (e.target.closest('.btn-edit-formula')) {
                const id = e.target.closest('.btn-edit-formula').dataset.id;
                self.editFormula(id);
            }
            if (e.target.closest('.btn-delete-formula')) {
                const id = e.target.closest('.btn-delete-formula').dataset.id;
                self.confirmDeleteFormula(id);
            }
            if (e.target.closest('.btn-export-formula')) {
                const btn = e.target.closest('.btn-export-formula');
                const id = btn.dataset.id;
                const nombre = btn.dataset.nombre || '';
                self.openExportModal(id, nombre);
            }
            // Eliminar ingrediente de la tabla inline
            if (e.target.closest('.btn-remove-ingrediente')) {
                const ingId = e.target.closest('.btn-remove-ingrediente').dataset.id;
                self.removeIngredienteFromTable(ingId);
            }
        });

        const btnGuardar = document.getElementById('btnGuardarFormula');
        if (btnGuardar) {
            btnGuardar.addEventListener('click', () => self.saveFormula());
        }

        // Búsqueda de ingredientes (con debounce)
        let searchTimeout;
        const searchInputEl = document.getElementById('formula_ingrediente_search');
        searchInputEl?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => self.buscarIngredientes(e.target.value), 300);
        });
        searchInputEl?.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                self.moveSearchHighlight(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                self.moveSearchHighlight(-1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                self.selectHighlightedIngredient();
            }
        });

        // Recálculo en tiempo real cuando cambian los parámetros de costo
        document.querySelectorAll('.param-costo').forEach(input => {
            input.addEventListener('input', () => self.recalcular());
        });

        // === EXPORTAR ===
        document.getElementById('btnExportExcel')?.addEventListener('click', () => self.exportarExcel());
        document.getElementById('btnExportPdf')?.addEventListener('click', () => self.exportarPdf());
    }

    /* =====================================================
       EXPORTAR FÓRMULA
    ====================================================== */
    openExportModal(id, nombre) {
        const modalEl = document.getElementById('modalExportarFormula');
        if (!modalEl) return;

        document.getElementById('export_formula_nombre').textContent = nombre || '—';
        modalEl.dataset.formulaId = id;
        modalEl.dataset.formulaNombre = nombre || '';

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    async exportarExcel() {
        const id = document.getElementById('modalExportarFormula').dataset.formulaId;
        if (!id) return;

        const url = "{{ url('formulas-alimento') }}/" + id + "/export/excel";
        try {
            const response = await fetch(url, { method: 'GET' });
            if (!response.ok) throw new Error('Error ' + response.status);

            const blob = await response.blob();
            const nombre = document.getElementById('modalExportarFormula').dataset.formulaNombre || 'formula';
            const downloadUrl = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = 'formula_vacuno_' + nombre.replace(/\s+/g, '_') + '.xlsx';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(downloadUrl);

            bootstrap.Modal.getInstance(document.getElementById('modalExportarFormula')).hide();
            this.showNotification('success', 'Excel generado correctamente');
        } catch (error) {
            console.error(error);
            this.showNotification('error', 'No se pudo generar el Excel');
        }
    }

    async exportarPdf() {
        const id = document.getElementById('modalExportarFormula').dataset.formulaId;
        const nombre = document.getElementById('modalExportarFormula').dataset.formulaNombre || 'formula';
        if (!id) return;

        const url = "{{ url('formulas-alimento') }}/" + id + "/export/pdf";
        window.open(url, '_blank');
        bootstrap.Modal.getInstance(document.getElementById('modalExportarFormula')).hide();
    }

    /* =====================================================
       CREAR FÓRMULA
    ====================================================== */
    showCreateModal() {
        const modalEl = document.getElementById('modalFormula');
        const modal = new bootstrap.Modal(modalEl);

        document.getElementById('formulaTitle').textContent = 'Nueva Fórmula';
        document.getElementById('method_field_formula').value = '';
        document.getElementById('formFormula').action = '{{ route("formulas-alimento.store") }}';
        document.getElementById('formFormula').reset();
        document.getElementById('formula_activo').checked = true;

        // Fecha por defecto: hoy
        const today = new Date();
        const todayStr = today.getFullYear() + '-' +
            String(today.getMonth() + 1).padStart(2, '0') + '-' +
            String(today.getDate()).padStart(2, '0');
        const fechaInput = document.getElementById('formula_fecha');
        fechaInput.value = todayStr;
        if (fechaInput._flatpickr) {
            fechaInput._flatpickr.setDate(todayStr, false);
        }

        // Valores por defecto (del Excel)
        document.getElementById('formula_kg_saco').value = 50;
        document.getElementById('formula_saco_vacio').value = 1.50;
        document.getElementById('formula_mano_obra').value = 0.50;
        document.getElementById('formula_energia').value = 1.00;
        document.getElementById('formula_merma').value = 0.50;
        document.getElementById('formula_precio_venta').value = 0;

        // Limpiar tabla de ingredientes
        this.ingredientesEnTabla = [];
        this.renderIngredientesTable();
        this.recalcular();

        this.formulaId = null;
        modal.show();
    }

    /* =====================================================
       EDITAR FÓRMULA
    ====================================================== */
    async editFormula(id) {
        try {
            const result = await this.fetchData(`${this.baseUrl}/${id}`);
            if (!result.success) return;

            const f = result.data.formula;
            const ingredientes = result.data.ingredientes || [];

            const modal = new bootstrap.Modal(document.getElementById('modalFormula'));
            document.getElementById('formulaTitle').textContent = 'Editar Fórmula: ' + f.nombre;
            document.getElementById('method_field_formula').value = 'PUT';
            document.getElementById('formFormula').action = `${this.baseUrl}/${id}`;

            // Llenar campos de la fórmula
            document.getElementById('formula_nombre').value = f.nombre || '';
            document.getElementById('formula_descripcion').value = f.descripcion || '';
            document.getElementById('formula_fecha').value = f.fecha || '';
            document.getElementById('formula_kg_saco').value = f.kg_saco || 50;
            document.getElementById('formula_saco_vacio').value = f.saco_vacio || 0;
            document.getElementById('formula_mano_obra').value = f.mano_obra || 0;
            document.getElementById('formula_energia').value = f.energia || 0;
            document.getElementById('formula_merma').value = f.merma || 0;
            document.getElementById('formula_precio_venta').value = f.precio_venta || 0;
            document.getElementById('formula_activo').checked = !!f.activo;

            // Llenar tabla de ingredientes desde los datos calculados
            this.ingredientesEnTabla = ingredientes.map(ing => ({
                ingrediente_id: ing.id,
                ingrediente: ing.ingrediente,
                procedencia: ing.procedencia || '',
                clasificacion: ing.clasificacion || '',
                nutriente: ing.nutriente || '',
                aporte: ing.aporte || 0,
                precio_kg: ing.precio_kg,
                cantidad_kg: ing.cantidad_kg,
            }));

            // Cachear datos nutricionales
            ingredientes.forEach(ing => {
                this.ingredientesCache[ing.id] = ing;
            });

            this.renderIngredientesTable();
            this.recalcular();

            this.formulaId = id;
            modal.show();
        } catch (error) {
            this.showNotification('error', 'Error al cargar la fórmula');
            console.error('Error editando fórmula:', error);
        }
    }

    /* =====================================================
       GUARDAR FÓRMULA (fórmula + ingredientes)
    ====================================================== */
    async saveFormula() {
        const form = document.getElementById('formFormula');
        const formData = new FormData(form);

        // Agregar los ingredientes como array de detalles
        this.ingredientesEnTabla.forEach((ing, index) => {
            formData.append(`detalles[${index}][ingrediente_id]`, ing.ingrediente_id);
            formData.append(`detalles[${index}][cantidad_kg]`, ing.cantidad_kg);
            formData.append(`detalles[${index}][precio_kg]`, ing.precio_kg);
        });

        // Si no hay ingredientes, enviar array vacío para que el backend sepa que debe limpiar
        if (this.ingredientesEnTabla.length === 0) {
            formData.append('detalles', '');
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();

            if (response.status === 422 && result.errors) {
                // Mostrar errores de validación
                let msg = Object.values(result.errors).flat().join('<br>');
                Swal.fire({ icon: 'error', title: 'Errores de validación', html: msg });
                return;
            }

            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalFormula')).hide();
                this.tabla.ajax.reload();
                this.showNotification('success', 'Fórmula guardada correctamente');
            } else {
                this.showNotification('error', result.message || 'No se pudo guardar');
            }
        } catch (error) {
            this.showNotification('error', 'Error al guardar la fórmula');
            console.error('Error guardando fórmula:', error);
        }
    }

    /* =====================================================
       ELIMINAR FÓRMULA
    ====================================================== */
    confirmDeleteFormula(id) {
        Swal.fire({
            title: '¿Eliminar fórmula?',
            text: 'Se eliminarán también todos los ingredientes asociados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (!result.isConfirmed) return;
            try {
                const data = await this.submitFormDirect(`${this.baseUrl}/${id}`, {}, 'DELETE');
                if (data.success) {
                    this.tabla.ajax.reload();
                    this.showNotification('success', 'La fórmula fue eliminada');
                } else {
                    this.showNotification('error', data.message || 'No se pudo eliminar');
                }
            } catch (error) {
                this.showNotification('error', 'Error al eliminar');
                console.error('Error eliminando fórmula:', error);
            }
        });
    }

    /* =====================================================
       VER FÓRMULA (solo lectura)
    ====================================================== */
    async verFormula(id) {
        try {
            const result = await this.fetchData(`${this.baseUrl}/${id}`);
            if (!result.success) return;

            const data = result.data;
            const f = data.formula;
            const c = data.resumen_costos;
            const n = data.resumen_nutricional;

            // Info general
            document.getElementById('view_nombre').textContent = f.nombre;
            document.getElementById('view_descripcion').textContent = f.descripcion || '—';
            document.getElementById('view_fecha').textContent = f.fecha || '—';
            document.getElementById('view_kg_saco').textContent = f.kg_saco.toFixed(2);
            document.getElementById('view_precio_venta').textContent = f.precio_venta.toFixed(2);

            // Costos
            document.getElementById('view_costo_tonelada').textContent = c.costo_tonelada.toFixed(2);
            document.getElementById('view_costo_kg').textContent = c.costo_kg.toFixed(4);
            document.getElementById('view_saco_vacio').textContent = f.saco_vacio.toFixed(2);
            document.getElementById('view_mano_obra').textContent = f.mano_obra.toFixed(2);
            document.getElementById('view_energia').textContent = f.energia.toFixed(2);
            document.getElementById('view_merma').textContent = f.merma.toFixed(2);
            document.getElementById('view_costo_saco').textContent = c.costo_saco.toFixed(2);
            document.getElementById('view_ganancia_saco').textContent = c.ganancia_saco.toFixed(2);
            document.getElementById('view_margen').textContent = c.margen_porcentaje.toFixed(2) + ' %';

            // Nutrientes
            document.getElementById('view_ms').textContent = n.materia_seca.toFixed(4);
            document.getElementById('view_pc').textContent = n.proteina_cruda.toFixed(4);
            document.getElementById('view_enl').textContent = n.enl.toFixed(4);
            document.getElementById('view_fdn').textContent = n.fdn.toFixed(4);
            document.getElementById('view_grasa').textContent = n.grasa.toFixed(4);
            document.getElementById('view_almidon').textContent = n.almidon.toFixed(4);
            document.getElementById('view_azucar').textContent = n.azucar.toFixed(4);

            // Tabla de ingredientes
            const tbody = document.getElementById('view_ingredientes_body');
            tbody.innerHTML = '';
            let totalKg = 0, totalCosto = 0;

            data.ingredientes.forEach(ing => {
                totalKg += parseFloat(ing.cantidad_kg);
                totalCosto += parseFloat(ing.costo);
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${ing.ingrediente}</strong></td>
                    <td>${ing.clasificacion || '—'}</td>
                    <td><span class="badge bg-secondary">${ing.procedencia || '—'}</span></td>
                    <td>${ing.nutriente || '—'}</td>
                    <td class="text-end">${parseFloat(ing.aporte || 0).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(ing.precio_kg).toFixed(4)}</td>
                    <td class="text-end fw-bold">${parseFloat(ing.cantidad_kg).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(ing.costo).toFixed(2)}</td>
                `;
                tbody.appendChild(tr);
            });

            // Totales del footer
            document.getElementById('view_total_kg_ft').textContent = totalKg.toFixed(2);
            document.getElementById('view_total_costo_ft').textContent = totalCosto.toFixed(2);

            const modal = new bootstrap.Modal(document.getElementById('modalViewFormula'));
            modal.show();
        } catch (error) {
            this.showNotification('error', 'Error al cargar la fórmula');
            console.error('Error viendo fórmula:', error);
        }
    }

    /* =====================================================
       BÚSQUEDA DE INGREDIENTES (para agregar a la tabla)
    ====================================================== */
    async buscarIngredientes(q) {
        const cont = document.getElementById('formula_search_results');
        this.searchResultsElements = [];
        this.searchHighlightedIndex = -1;

        if (!q || q.length < 1) {
            cont.innerHTML = '';
            return;
        }

        try {
            const items = await this.fetchData(`{{ route('formulas-alimento.ingredientes.buscar') }}?q=${encodeURIComponent(q)}`);

            cont.innerHTML = '';
            if (!items.length) {
                cont.innerHTML = '<small class="text-muted">Sin resultados</small>';
                return;
            }

            const ul = document.createElement('ul');
            ul.className = 'list-group position-absolute w-100';
            ul.style.zIndex = '1050';
            ul.style.maxHeight = '200px';
            ul.style.overflowY = 'auto';

            items.forEach((it, index) => {
                const yaExiste = this.ingredientesEnTabla.some(x => x.ingrediente_id == it.id);

                const li = document.createElement('li');
                li.className = `list-group-item ${yaExiste ? 'disabled text-muted' : ''}`;
                li.style.cursor = yaExiste ? 'not-allowed' : 'pointer';
                li.style.backgroundColor = '#fff';
                li.style.transition = 'background-color 0.15s ease-in-out';
                li.tabIndex = -1;
                li.innerHTML = `
                    <strong>${it.ingrediente}</strong>
                    <span class="badge bg-secondary">${it.procedencia || ''}</span>
                    <small class="text-muted">${it.clasificacion || ''}</small>
                    ${yaExiste ? '<span class="badge bg-warning text-dark ms-2">Ya agregado</span>' : ''}
                `;

                if (!yaExiste) {
                    li.addEventListener('mouseenter', () => {
                        if (this.searchHighlightedIndex !== index) {
                            li.style.backgroundColor = '#cfe2ff';
                        }
                    });
                    li.addEventListener('mouseleave', () => {
                        if (this.searchHighlightedIndex !== index) {
                            li.style.backgroundColor = '#fff';
                        }
                    });
                    li.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.agregarIngrediente(it);
                    });
                }

                this.searchResultsElements.push(li);
                ul.appendChild(li);
            });
            cont.appendChild(ul);
        } catch (error) {
            console.error('Error buscando ingredientes:', error);
        }
    }

    moveSearchHighlight(direction) {
        const items = this.searchResultsElements;
        const selectable = items.filter(el => !el.classList.contains('disabled'));
        if (selectable.length === 0) return;

        const currentEl = this.searchHighlightedIndex >= 0 ? items[this.searchHighlightedIndex] : null;
        const currentSelectableIdx = currentEl ? selectable.indexOf(currentEl) : -1;

        let nextSelectableIdx = currentSelectableIdx + direction;
        if (nextSelectableIdx < 0) nextSelectableIdx = selectable.length - 1;
        if (nextSelectableIdx >= selectable.length) nextSelectableIdx = 0;

        const nextEl = selectable[nextSelectableIdx];
        this.searchHighlightedIndex = items.indexOf(nextEl);
        this.updateSearchHighlight();
    }

    updateSearchHighlight() {
        this.searchResultsElements.forEach((el, i) => {
            if (i === this.searchHighlightedIndex) {
                el.style.backgroundColor = '#0d6efd';
                el.style.color = '#fff';
                el.querySelectorAll('strong, .badge, small').forEach(child => {
                    child.style.color = '#fff';
                });
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.style.backgroundColor = '#fff';
                el.style.color = '';
                el.querySelectorAll('strong, .badge, small').forEach(child => {
                    child.style.color = '';
                });
            }
        });
    }

    selectHighlightedIngredient() {
        const items = this.searchResultsElements;
        if (items.length === 0) return;

        let idx = this.searchHighlightedIndex;
        if (idx < 0) {
            const firstSelectable = items.findIndex(el => !el.classList.contains('disabled'));
            if (firstSelectable < 0) return;
            idx = firstSelectable;
        }
        const item = items[idx];
        if (item && !item.classList.contains('disabled')) {
            item.click();
        }
    }

    /* =====================================================
       AGREGAR INGREDIENTE A LA TABLA
    ====================================================== */
    agregarIngrediente(ingrediente) {
        this.ingredientesCache[ingrediente.id] = ingrediente;

        this.ingredientesEnTabla.push({
            ingrediente_id: ingrediente.id,
            ingrediente: ingrediente.ingrediente,
            procedencia: ingrediente.procedencia || '',
            clasificacion: ingrediente.clasificacion || '',
            nutriente: ingrediente.nutriente || '',
            aporte: ingrediente.aporte || 0,
            precio_kg: 0,
            cantidad_kg: 0,
        });

        this.renderIngredientesTable();
        this.recalcular();

        const cont = document.getElementById('formula_search_results');
        cont.innerHTML = '';
        this.searchResultsElements = [];
        this.searchHighlightedIndex = -1;

        const searchInput = document.getElementById('formula_ingrediente_search');
        searchInput.value = '';

        const newIndex = this.ingredientesEnTabla.length - 1;
        const precioInput = document.querySelector(`#ingredientes_tbody .ing-precio[data-index="${newIndex}"]`);
        if (precioInput) {
            setTimeout(() => {
                precioInput.focus();
                precioInput.select();
            }, 50);
        }
    }

    /* =====================================================
       ELIMINAR INGREDIENTE DE LA TABLA
    ====================================================== */
    removeIngredienteFromTable(ingredienteId) {
        this.ingredientesEnTabla = this.ingredientesEnTabla.filter(
            x => x.ingrediente_id != ingredienteId
        );
        this.renderIngredientesTable();
        this.recalcular();
    }

    /* =====================================================
       RENDER TABLA DE INGREDIENTES
    ====================================================== */
    renderIngredientesTable() {
        const tbody = document.getElementById('ingredientes_tbody');
        tbody.innerHTML = '';

        if (this.ingredientesEnTabla.length === 0) {
            tbody.innerHTML = `
                <tr id="ingredientes_empty_row">
                    <td colspan="9" class="text-center text-muted py-3">
                        <i class="bi bi-search me-1"></i>Busque y agregue ingredientes usando el buscador de arriba
                    </td>
                </tr>
            `;
            return;
        }

        this.ingredientesEnTabla.forEach((ing, index) => {
            const costo = (parseFloat(ing.cantidad_kg) * parseFloat(ing.precio_kg)).toFixed(2);

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-ingrediente"
                            data-id="${ing.ingrediente_id}" title="Quitar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
                <td><strong>${ing.ingrediente}</strong></td>
                <td><span class="badge bg-secondary">${ing.procedencia}</span></td>
                <td><small>${ing.clasificacion}</small></td>
                <td><small>${ing.nutriente}</small></td>
                <td class="text-end">${parseFloat(ing.aporte || 0).toFixed(2)}</td>
                <td>
                    <input type="number" step="0.0001" min="0"
                           class="form-control form-control-sm text-end ing-precio"
                           data-index="${index}"
                           value="${parseFloat(ing.precio_kg).toFixed(4)}">
                </td>
                <td>
                    <input type="number" step="0.01" min="0"
                           class="form-control form-control-sm text-end ing-cantidad"
                           data-index="${index}"
                           value="${parseFloat(ing.cantidad_kg).toFixed(2)}">
                </td>
                <td class="text-end fw-bold ing-costo">${costo}</td>
            `;
            tbody.appendChild(tr);
        });

        // Eventos para inputs editables
        tbody.querySelectorAll('.ing-precio').forEach(input => {
            input.addEventListener('input', (e) => {
                const idx = parseInt(e.target.dataset.index);
                this.ingredientesEnTabla[idx].precio_kg = parseFloat(e.target.value) || 0;
                this.updateRowCosto(idx);
                this.recalcular();
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const idx = parseInt(e.target.dataset.index);
                    const cantInput = document.querySelector(`#ingredientes_tbody .ing-cantidad[data-index="${idx}"]`);
                    if (cantInput) {
                        cantInput.focus();
                        cantInput.select();
                    }
                }
            });
        });

        tbody.querySelectorAll('.ing-cantidad').forEach(input => {
            input.addEventListener('input', (e) => {
                const idx = parseInt(e.target.dataset.index);
                this.ingredientesEnTabla[idx].cantidad_kg = parseFloat(e.target.value) || 0;
                this.updateRowCosto(idx);
                this.recalcular();
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const searchInput = document.getElementById('formula_ingrediente_search');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }
            });
        });
    }

    /**
     * Actualiza la celda de costo de una fila específica
     */
    updateRowCosto(index) {
        const ing = this.ingredientesEnTabla[index];
        const costo = (parseFloat(ing.cantidad_kg) * parseFloat(ing.precio_kg)).toFixed(2);
        const costoCells = document.querySelectorAll('#ingredientes_tbody .ing-costo');
        if (costoCells[index]) {
            costoCells[index].textContent = costo;
        }
    }

    /* =====================================================
       RECÁLCULO EN TIEMPO REAL
       (costos + nutrientes, replica la lógica del Excel)
    ====================================================== */
    recalcular() {
        let totalKg = 0;
        let costoTonelada = 0;

        // Nutrientes acumulados
        let nutrientes = {
            materia_seca: 0, proteina_cruda: 0, enl: 0,
            fdn: 0, grasa: 0, almidon: 0, azucar: 0
        };

        this.ingredientesEnTabla.forEach(ing => {
            const cantKg = parseFloat(ing.cantidad_kg) || 0;
            const precioKg = parseFloat(ing.precio_kg) || 0;

            if (cantKg <= 0) return;

            totalKg += cantKg;
            costoTonelada += cantKg * precioKg;

            // Buscar datos nutricionales en el cache
            const nutData = this.ingredientesCache[ing.ingrediente_id];
            if (nutData) {
                nutrientes.materia_seca   += (cantKg * parseFloat(nutData.materia_seca || 0)) / 1000;
                nutrientes.proteina_cruda += (cantKg * parseFloat(nutData.proteina_cruda || 0)) / 1000;
                nutrientes.enl            += (cantKg * parseFloat(nutData.enl || 0)) / 1000;
                nutrientes.fdn            += (cantKg * parseFloat(nutData.fdn || 0)) / 1000;
                nutrientes.grasa          += (cantKg * parseFloat(nutData.grasa || 0)) / 1000;
                nutrientes.almidon        += (cantKg * parseFloat(nutData.almidon || 0)) / 1000;
                nutrientes.azucar         += (cantKg * parseFloat(nutData.azucar || 0)) / 1000;
            }
        });

        const costoKg = costoTonelada / 1000;
        const kgSaco = parseFloat(document.getElementById('formula_kg_saco')?.value) || 0;
        const sacoVacio = parseFloat(document.getElementById('formula_saco_vacio')?.value) || 0;
        const manoObra = parseFloat(document.getElementById('formula_mano_obra')?.value) || 0;
        const energia = parseFloat(document.getElementById('formula_energia')?.value) || 0;
        const merma = parseFloat(document.getElementById('formula_merma')?.value) || 0;
        const precioVenta = parseFloat(document.getElementById('formula_precio_venta')?.value) || 0;

        const costoSaco = kgSaco > 0
            ? (costoKg * kgSaco) + sacoVacio + manoObra + energia + merma
            : sacoVacio + manoObra + energia + merma;

        const gananciaSaco = precioVenta - costoSaco;
        const margen = precioVenta > 0 ? (gananciaSaco / precioVenta) * 100 : 0;

        // ====== Actualizar footer de la tabla ======
        const ftTotalKg = document.getElementById('ft_total_kg');
        const ftTotalCosto = document.getElementById('ft_total_costo');
        if (ftTotalKg) ftTotalKg.textContent = totalKg.toFixed(2);
        if (ftTotalCosto) ftTotalCosto.textContent = costoTonelada.toFixed(2);

        // ====== Actualizar panel de costos ======
        this.setText('res_costo_tn', costoTonelada.toFixed(2));
        this.setText('res_costo_kg', costoKg.toFixed(4));
        this.setText('res_saco_vacio', sacoVacio.toFixed(2));
        this.setText('res_mano_obra', manoObra.toFixed(2));
        this.setText('res_energia', energia.toFixed(2));
        this.setText('res_merma', merma.toFixed(2));
        this.setText('res_costo_saco', costoSaco.toFixed(2));
        this.setText('res_ganancia', gananciaSaco.toFixed(2));
        this.setText('res_margen', margen.toFixed(2) + ' %');

        // Color de ganancia
        const gananciaEl = document.getElementById('res_ganancia');
        if (gananciaEl) {
            gananciaEl.style.color = gananciaSaco >= 0 ? '#198754' : '#dc3545';
        }

        // ====== Actualizar panel nutricional ======
        this.setText('nut_ms', nutrientes.materia_seca.toFixed(4));
        this.setText('nut_pc', nutrientes.proteina_cruda.toFixed(4));
        this.setText('nut_enl', nutrientes.enl.toFixed(4));
        this.setText('nut_fdn', nutrientes.fdn.toFixed(4));
        this.setText('nut_grasa', nutrientes.grasa.toFixed(4));
        this.setText('nut_almidon', nutrientes.almidon.toFixed(4));
        this.setText('nut_azucar', nutrientes.azucar.toFixed(4));
    }

    /**
     * Helper: setea textContent de un elemento por ID
     */
    setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    /* =====================================================
       UTILIDADES
    ====================================================== */
    async submitFormDirect(url, data, method = 'POST') {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const formData = new FormData();
        if (method !== 'POST') {
            formData.append('_method', method);
        }
        for (const key in data) {
            formData.append(key, data[key]);
        }
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
        });
        return await response.json();
    }
}

/* =====================================================
   INICIALIZACIÓN
====================================================== */
document.addEventListener('DOMContentLoaded', () => {
    window.formulaAlimentoManager = new FormulaAlimentoManager();
    document.getElementById('mnuNutricion')?.classList.add('menu-open');
    document.getElementById('itemFormulasAlimento')?.classList.add('active');
});
</script>
@endpush
