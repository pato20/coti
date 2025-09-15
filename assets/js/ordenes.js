document.addEventListener('DOMContentLoaded', function() {
    const pagoForm = document.getElementById('pagoForm');
    if(pagoForm) {
        const pagoModalEl = document.getElementById('pagoModal');
        const pagoModal = new bootstrap.Modal(pagoModalEl);

        pagoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            pagoModal.hide();
            showToast('Procesando pago...', 'info');

            fetch('ajax/registrar_pago.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(result.message, 'danger');
                }
            })
            .catch(error => showToast('Error de comunicación.', 'danger'));
        });
    }
});

function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;

    const toastId = `toast-${Date.now()}`;
    const toastHTML = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

function agendarMantenciones(ordenId) {
    if (!confirm('¿Agendar 4 visitas de mantención trimestrales para el próximo año?')) return;
    fetch('ajax/agendar_mantenciones.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ orden_id: ordenId })
    })
    .then(res => res.json())
    .then(data => showToast(data.message, data.success ? 'success' : 'danger'))
    .catch(() => showToast('Error de comunicación.', 'danger'));
}

function cambiarEstadoOrden(ordenId, estado) {
    if (!confirm(`¿Cambiar el estado de la orden a ${estado}?`)) return;
    fetch('ajax/cambiar_estado_orden.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ orden_id: ordenId, estado: estado })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(() => showToast('Error de comunicación.', 'danger'));
}

function eliminarOrden(ordenId) {
    if (!confirm('¿Eliminar esta orden de trabajo y todos sus datos asociados? Esta acción no se puede deshacer.')) return;
    fetch('ajax/eliminar_orden.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ orden_id: ordenId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => { window.location.href = 'ordenes.php'; }, 1500);
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(() => showToast('Error de comunicación.', 'danger'));
}

function eliminarPago(pagoId, ordenId, monto) {
    if (!confirm(`¿Eliminar este pago de ${formatCurrency(monto)}?`)) return;
    fetch('ajax/eliminar_pago.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ pago_id: pagoId, orden_id: ordenId, monto: monto })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(() => showToast('Error de comunicación.', 'danger'));
}

function formatCurrency(number) {
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(number);
}
