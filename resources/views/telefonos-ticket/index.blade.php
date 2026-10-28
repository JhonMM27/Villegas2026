@extends('plantilla.app')

@section('contenido')
<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title flex-grow-1">Teléfonos Ticket</h3>
            @if(!$settings['installed'])<span class="badge bg-warning text-dark">Tabla pendiente de instalar</span>@endif
        </div>
        <div class="card-body">
            @if(!$settings['installed'])
                <div class="alert alert-warning">Instala la migración <code>telefonos_ticket</code> o ejecuta el SQL entregado para habilitar la edición. Los tickets continúan usando los teléfonos actuales como respaldo.</div>
            @endif
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm align-middle">
                    <thead><tr><th>Teléfono 1</th><th>Teléfono 2</th><th>Vista previa</th><th>Actualizado</th><th>Opciones</th></tr></thead>
                    <tbody><tr>
                        <td>{{ $settings['telefono_1'] }}</td>
                        <td>{{ $settings['telefono_2'] ?: '—' }}</td>
                        <td>CEL: {{ $settings['telefono_1'] }}@if(filled($settings['telefono_2'])) / {{ $settings['telefono_2'] }}@endif</td>
                        <td>{{ $settings['updated_at'] ?: 'Pendiente de instalación' }}</td>
                        <td>@can('configuraciones_edit')<button type="button" class="btn btn-sm btn-info" id="btnEditTelefonos" {{ !$settings['installed'] ? 'disabled' : '' }}><i class="bi bi-pencil"></i> Editar</button>@endcan</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@can('configuraciones_edit')
<div class="modal fade" id="modalTelefonosTicket" tabindex="-1" aria-labelledby="modalTelefonosTicketTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form id="formTelefonosTicket" novalidate>
        @csrf
        <div class="modal-header"><h5 class="modal-title" id="modalTelefonosTicketTitle">Editar teléfonos del ticket</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label for="telefono_1" class="form-label">Teléfono 1 <span class="text-danger">*</span></label><input id="telefono_1" name="telefono_1" class="form-control" maxlength="25" required><div class="invalid-feedback" id="telefono_1_error"></div></div>
            <div class="mb-3"><label for="telefono_2" class="form-label">Teléfono 2</label><input id="telefono_2" name="telefono_2" class="form-control" maxlength="25"><div class="invalid-feedback" id="telefono_2_error"></div></div>
            <div class="form-text">Admite números, espacios, +, guiones y paréntesis.</div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary btn-sm">Guardar</button></div>
    </form></div></div>
</div>
@endcan
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const edit = document.getElementById('btnEditTelefonos');
    const form = document.getElementById('formTelefonosTicket');
    if (!edit || !form) return;
    edit.addEventListener('click', () => {
        document.getElementById('telefono_1').value = @json($settings['telefono_1']);
        document.getElementById('telefono_2').value = @json($settings['telefono_2']);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTelefonosTicket')).show();
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
        const body = { telefono_1: document.getElementById('telefono_1').value, telefono_2: document.getElementById('telefono_2').value };
        const response = await fetch('{{ route('telefonos-ticket.update', 1) }}', { method: 'PUT', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json'}, body: JSON.stringify(body) });
        const result = await response.json();
        if (!response.ok) {
            Object.entries(result.errors || {}).forEach(([field, messages]) => { document.getElementById(field)?.classList.add('is-invalid'); const error = document.getElementById(field + '_error'); if (error) error.textContent = messages[0]; });
            return;
        }
        bootstrap.Modal.getInstance(document.getElementById('modalTelefonosTicket'))?.hide();
        window.location.reload();
    });
});
document.getElementById('mnuConfiguracion')?.classList.add('menu-open');
document.getElementById('itemTelefonosTicket')?.classList.add('active');
</script>
@endpush
