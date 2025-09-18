// --- FUNCIONES GLOBALES (necesitan estar aquí para ser llamadas desde el HTML onclick) ---
function addProductRow() {
    const template = document.getElementById('producto-fila-template');
    if (!template) return;
    const container = document.getElementById('productos-container');
    const newRow = template.content.cloneNode(true);
    container.appendChild(newRow);
}

function addGenericRow() {
    const template = document.getElementById('generico-fila-template');
    if (!template) return;
    const container = document.getElementById('productos-container');
    const newRow = template.content.cloneNode(true);
    container.appendChild(newRow);
}

function showToast(message, type = 'success') {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
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

// --- LÓGICA PRINCIPAL (cuando el DOM esté listo) ---
document.addEventListener('DOMContentLoaded', function() {

    function recalcularTotales() {
        let subtotalCotizacion = 0;
        const ivaPorcentaje = (window.IVA_PORCENTAJE || 19) / 100;

        document.querySelectorAll('.producto-row').forEach(row => {
            const cantidad = parseFloat(row.querySelector('input[name^="cantidades"], input[name^="generic_quantity"]')?.value) || 0;
            const precio = parseFloat(row.querySelector('input[name^="precios"], input[name^="generic_price"]')?.value) || 0;
            const descuentoItem = parseFloat(row.querySelector('input[name^="descuentos_item"], input[name^="generic_descuentos_item"]')?.value) || 0;

            let subtotalItem = cantidad * precio;
            if (descuentoItem > 0) {
                subtotalItem *= (1 - (descuentoItem / 100));
            }
            subtotalCotizacion += subtotalItem;
        });

        let totalCotizacion = subtotalCotizacion;
        const descuentoGeneralCheckbox = document.getElementById('activar_descuento_general');
        const descuentoGeneralInput = document.querySelector('input[name="descuento_general"]');
        if (descuentoGeneralCheckbox?.checked && descuentoGeneralInput) {
            const descuentoGeneralValor = parseFloat(descuentoGeneralInput.value) || 0;
            if (descuentoGeneralValor > 0) {
                totalCotizacion *= (1 - (descuentoGeneralValor / 100));
            }
        }

        let ivaCalculado = 0;
        const activarIvaCheckbox = document.getElementById('activar_iva');
        if (activarIvaCheckbox?.checked) {
            ivaCalculado = totalCotizacion * ivaPorcentaje;
            totalCotizacion += ivaCalculado;
        }

        document.getElementById('cotizacion_subtotal').textContent = subtotalCotizacion.toFixed(2);
        document.getElementById('cotizacion_iva').textContent = ivaCalculado.toFixed(2);
        document.getElementById('cotizacion_total').textContent = totalCotizacion.toFixed(2);
    }

    // --- LISTENERS DE EVENTOS ---
    const container = document.getElementById('productos-container');
    if(container) {
        container.addEventListener('input', function(e) {
            if (e.target.matches('input[name^="cantidades"], input[name^="generic_quantity"], input[name^="precios"], input[name^="generic_price"], input[name^="descuentos_item"], input[name^="generic_descuentos_item"]')) {
                recalcularTotales();
            }
        });
        container.addEventListener('change', function(e) {
            if (e.target.matches('select[name="productos[]"]')) {
                const option = e.target.selectedOptions[0];
                const precio = option.dataset.precio;
                const row = e.target.closest('.producto-row');
                row.querySelector('input[name="precios[]"]').value = precio || '';
                recalcularTotales();
            }
        });
    }

    document.getElementById('activar_iva')?.addEventListener('change', recalcularTotales);
    document.querySelector('input[name="descuento_general"]')?.addEventListener('input', recalcularTotales);
    document.getElementById('activar_descuento_general')?.addEventListener('change', function() {
        const fields = document.getElementById('descuento_general_fields');
        if(fields) fields.classList.toggle('d-none', !this.checked);
        if(!this.checked) {
            document.querySelector('input[name="descuento_general"]').value = '0';
        }
        recalcularTotales();
    });

    const cercoCheckbox = document.getElementById('es_cerco_electrico');
    if (cercoCheckbox) {
        cercoCheckbox.addEventListener('change', function() {
            const fields = document.getElementById('cerco-electrico-fields');
            if(fields) fields.classList.toggle('d-none', !this.checked);
        });
    }

    const calcularCercoBtn = document.getElementById('calcular_cerco_btn');
    if (calcularCercoBtn) {
        calcularCercoBtn.addEventListener('click', function() {
            const metros = parseFloat(document.getElementById('metros_lineales')?.value) || 0;
            const hilos = document.getElementById('numero_hilos')?.value;
            const instalacion = document.getElementById('tipo_instalacion')?.value;

            if (metros <= 0) {
                alert('Por favor, ingrese los metros lineales para calcular.');
                return;
            }

            // Preparamos los datos para enviar
            const formData = new FormData();
            formData.append('metros', metros);
            formData.append('hilos', hilos);
            formData.append('instalacion', instalacion);

            // Llamada AJAX al servidor para obtener el precio
            fetch('ajax/calcular_mano_obra_cerco.php', { 
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addGenericRow();
                    const nuevasFilas = document.querySelectorAll('#productos-container .producto-row');
                    const laNuevaFila = nuevasFilas[nuevasFilas.length - 1];
                    
                    if(laNuevaFila) {
                        laNuevaFila.querySelector('input[name="generic_description[]"]').value = 'Mano de Obra - Instalación Cerco Eléctrico';
                        laNuevaFila.querySelector('input[name="generic_quantity[]"]').value = 1;
                        laNuevaFila.querySelector('input[name="generic_price[]"]').value = data.precio.toFixed(2);
                    }

                    recalcularTotales();
                    showToast('Mano de obra del cerco añadida a la cotización.', 'success');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error en la llamada AJAX:', error);
                alert('No se pudo conectar con el servidor para calcular el precio.');
            });
        });
    }

    recalcularTotales();
});