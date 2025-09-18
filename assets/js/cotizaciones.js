document.addEventListener('DOMContentLoaded', function() {

    // --- Event Listeners --- //

    // Listener para autocompletar precio al seleccionar un producto
    document.getElementById('productos-container').addEventListener('change', function(e) {
        if (e.target.name === 'productos[]') {
            const option = e.target.selectedOptions[0];
            const precio = option.dataset.precio;
            const row = e.target.closest('.producto-row');
            const precioInput = row.querySelector('input[name="precios[]"]');
            if (precio) {
                precioInput.value = precio;
            }
            recalcularTotales();
        }
    });

    // Listener para dropdowns en tablas responsivas
    document.querySelectorAll('.table-responsive .dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('show.bs.dropdown', event => {
            event.target.closest('.table-responsive').classList.add('overflow-visible');
        });
        toggle.addEventListener('hide.bs.dropdown', event => {
            event.target.closest('.table-responsive').classList.remove('overflow-visible');
        });
    });

    // Listener para el checkbox de cerco eléctrico
    const cercoCheckbox = document.getElementById('es_cerco_electrico');
    if (cercoCheckbox) {
        cercoCheckbox.addEventListener('change', function() {
            const fields = document.getElementById('cerco-electrico-fields');
            if (this.checked) {
                fields.classList.remove('d-none');
            } else {
                fields.classList.add('d-none');
            }
        });
    }

    // Listener para el checkbox de descuento general
    const descuentoGeneralCheckbox = document.getElementById('activar_descuento_general');
    if (descuentoGeneralCheckbox) {
        descuentoGeneralCheckbox.addEventListener('change', function() {
            const fields = document.getElementById('descuento_general_fields');
            if (this.checked) {
                fields.classList.remove('d-none');
            } else {
                fields.classList.add('d-none');
                // Opcional: resetear el valor del descuento a 0 cuando se desactiva
                fields.querySelector('input[name="descuento_general"]').value = '0';
            }
            recalcularTotales(); // Recalcular totales al cambiar el estado del descuento general
        });
    }

    // Eventos para recalcular totales
    recalcularTotales(); // Calcular al cargar la página

    document.getElementById('productos-container').addEventListener('input', function(e) {
        if (e.target.name === 'cantidades[]' || e.target.name === 'precios[]' || e.target.name === 'descuentos_item[]' ||
            e.target.name === 'generic_quantity[]' || e.target.name === 'generic_price[]' || e.target.name === 'generic_descuentos_item[]') {
            recalcularTotales();
        }
    });

    const activarIvaCheckbox = document.getElementById('activar_iva');
    if (activarIvaCheckbox) {
        activarIvaCheckbox.addEventListener('change', recalcularTotales);
    }

    const descuentoGeneralInput = document.querySelector('input[name="descuento_general"]');
    if (descuentoGeneralInput) {
        descuentoGeneralInput.addEventListener('input', recalcularTotales);
    }

});

// --- Funciones para añadir filas de productos ---

function addProductRow() {
    const template = document.getElementById('producto-fila-template');
    const container = document.getElementById('productos-container');
    const newRow = template.content.cloneNode(true);
    container.appendChild(newRow);
    recalcularTotales(); // Recalcular totales al añadir una fila
}

function addGenericRow() {
    const template = document.getElementById('generico-fila-template');
    const container = document.getElementById('productos-container');
    const newRow = template.content.cloneNode(true);
    container.appendChild(newRow);
    recalcularTotales(); // Recalcular totales al añadir una fila
}

// --- Función para recalcular totales ---
function recalcularTotales() {
    let subtotalCotizacion = 0;
    const ivaPorcentaje = 0.19; // Asumiendo 19% de IVA

    document.querySelectorAll('.producto-row').forEach(row => {
        const cantidadInput = row.querySelector('input[name^="cantidades[]"], input[name^="generic_quantity[]"]');
        const precioInput = row.querySelector('input[name^="precios[]"], input[name^="generic_price[]"]');
        const descuentoItemInput = row.querySelector('input[name^="descuentos_item[]"], input[name^="generic_descuentos_item[]"]');

        const cantidad = parseFloat(cantidadInput ? cantidadInput.value : 0) || 0;
        const precio = parseFloat(precioInput ? precioInput.value : 0) || 0;
        const descuentoItem = parseFloat(descuentoItemInput ? descuentoItemInput.value : 0) || 0;

        let subtotalItem = cantidad * precio;
        if (descuentoItem > 0) {
            subtotalItem = subtotalItem * (1 - (descuentoItem / 100));
        }
        subtotalCotizacion += subtotalItem;
    });

    let totalCotizacion = subtotalCotizacion;
    const descuentoGeneralCheckbox = document.getElementById('activar_descuento_general');
    const descuentoGeneralInput = document.querySelector('input[name="descuento_general"]');
    let descuentoGeneralValor = 0;

    if (descuentoGeneralCheckbox && descuentoGeneralCheckbox.checked && descuentoGeneralInput) {
        descuentoGeneralValor = parseFloat(descuentoGeneralInput.value) || 0;
        if (descuentoGeneralValor > 0) {
            totalCotizacion = totalCotizacion * (1 - (descuentoGeneralValor / 100));
        }
    }

    let ivaCalculado = 0;
    const activarIvaCheckbox = document.getElementById('activar_iva');
    if (activarIvaCheckbox && activarIvaCheckbox.checked) {
        ivaCalculado = totalCotizacion * ivaPorcentaje;
        totalCotizacion += ivaCalculado;
    }

    // Actualizar los elementos HTML que muestran el subtotal, IVA y total
    document.getElementById('cotizacion_subtotal').textContent = subtotalCotizacion.toFixed(2);
    document.getElementById('cotizacion_iva').textContent = ivaCalculado.toFixed(2);
    document.getElementById('cotizacion_total').textContent = totalCotizacion.toFixed(2);
}

// --- Funciones de Notificación (Toast) ---

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

// --- Funciones de Acciones de Cotización (AJAX) ---

function cambiarEstado(id, estado) {
    if (!confirm(`¿Seguro que quieres cambiar el estado a "${estado}"?`)) return;

    fetch('ajax/cambiar_estado.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ cotizacion_id: id, estado: estado })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(error => showToast('Error de comunicación.', 'danger'));
}

function aprobarCotizacion(id) {
    if (!confirm('¿Aprobar esta cotización y generar una Orden de Trabajo? Esta acción no se puede deshacer.')) return;

    fetch('ajax/aprobar_cotizacion.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ cotizacion_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(error => showToast('Error de comunicación.', 'danger'));
}

function eliminarCotizacion(id) {
    if (!confirm('¿Eliminar esta cotización? Esta acción no se puede deshacer.')) return;

    fetch('ajax/eliminar_cotizacion.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ cotizacion_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(error => showToast('Error de comunicación.', 'danger'));
}

function enviarPorWhatsapp(id, numero, cliente, total) {
    const pdfUrl = `${window.location.origin}/cerco/cotizacion_pdf.php?id=${id}`;
    const mensaje = `¡Hola ${cliente}! Te envío la cotización N° ${numero} por un total de ${total}. Puedes ver el detalle aquí: ${pdfUrl}`;
    
    window.open(whatsappUrl, '_blank');
    
    showToast('Abriendo WhatsApp...', 'info');
    setTimeout(() => {
        if (confirm('¿Deseas marcar esta cotización como "Enviada"?')) {
            cambiarEstado(id, 'enviada');
        }
    }, 2000);
}