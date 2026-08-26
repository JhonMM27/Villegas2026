(function () {
    'use strict';

    window.solicitarMotivoAuditoria = async function (titulo, descripcion) {
        const result = await Swal.fire({
            title: titulo,
            text: descripcion,
            input: 'textarea',
            inputLabel: 'Motivo (opcional)',
            inputPlaceholder: 'Puede describir el motivo o dejar este campo vacío',
            inputAttributes: { maxlength: 500, 'aria-label': 'Motivo de la operación' },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d97706',
        });

        return result.isConfirmed ? String(result.value).trim() : null;
    };

    window.asignarMotivoAuditoria = function (form, motivo) {
        let input = form.querySelector('input[name="rectificacion_motivo"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'rectificacion_motivo';
            form.appendChild(input);
        }
        input.value = motivo;
    };
}());
