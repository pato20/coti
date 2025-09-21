document.addEventListener('DOMContentLoaded', function() {
    const eventoModal = new bootstrap.Modal(document.getElementById('eventoModal'));
    const calendarEl = document.getElementById('calendar');
    let calendar;

    window.abrirModalEvento = function(evento = null) {
        const form = document.getElementById('eventoForm');
        form.reset();
        document.getElementById('delete-section').style.display = 'none';

        // Resetear el estado del checkbox y campos de cliente/referencia
        const sinClienteCheck = document.getElementById('sinClienteCheck');
        const clienteSelectDiv = document.getElementById('cliente-select-div');
        const nombreReferenciaDiv = document.getElementById('nombre-referencia-div');
        const clienteSelect = document.getElementById('eventoCliente');
        const nombreReferenciaInput = document.getElementById('nombreReferencia');
        const eventoTituloInput = document.getElementById('eventoTitulo');
        const eventoEstadoSelect = document.getElementById('eventoEstado');

        if (sinClienteCheck) {
            sinClienteCheck.checked = false;
        }
        clienteSelectDiv.classList.remove('d-none');
        nombreReferenciaDiv.classList.add('d-none');
        clienteSelect.disabled = false;
        nombreReferenciaInput.disabled = true;
        nombreReferenciaInput.value = '';
        eventoEstadoSelect.value = 'pendiente'; // Estado por defecto

        if (evento) {
            document.getElementById('modalTitulo').innerText = 'Editar Visita';
            document.getElementById('eventoId').value = evento.id;
            
            // Manejo del título y nombre_referencia
            if (evento.cliente_id === null || evento.cliente_id === '') {
                sinClienteCheck.checked = true;
                clienteSelectDiv.classList.add('d-none');
                nombreReferenciaDiv.classList.remove('d-none');
                clienteSelect.disabled = true;
                nombreReferenciaInput.disabled = false;
                nombreReferenciaInput.value = evento.nombre_referencia || '';
                // Extraer el título original si se usó nombre_referencia
                if (evento.nombre_referencia && evento.titulo.startsWith(evento.nombre_referencia + ' - ')) {
                    eventoTituloInput.value = evento.titulo.substring(evento.nombre_referencia.length + 3);
                } else {
                    eventoTituloInput.value = evento.titulo;
                }
            } else {
                clienteSelect.value = evento.cliente_id || '';
                eventoTituloInput.value = evento.titulo;
            }

            document.getElementById('eventoDescripcion').value = evento.descripcion || '';
            const fecha = evento.fecha_hora_inicio;
            document.getElementById('eventoFecha').value = fecha ? fecha.slice(0, 16).replace(' ', 'T') : '';
            document.getElementById('eventoTipo').value = evento.tipo || 'visita';
            eventoEstadoSelect.value = evento.estado || 'pendiente'; // Cargar estado
            const userSelect = document.getElementById('eventoUsuario');
            if (userSelect) {
                userSelect.value = evento.usuario_id || '';
            }
            document.getElementById('delete-section').style.display = 'block';
        } else {
            document.getElementById('modalTitulo').innerText = 'Nueva Visita';
            document.getElementById('eventoId').value = '';
            eventoTituloInput.value = ''; // Asegurar que el título esté vacío para nuevas visitas
        }
        eventoModal.show();
    }

    window.eliminarEvento = function() {
        const eventoId = document.getElementById('eventoId').value;
        if (eventoId && confirm('¿Eliminar esta visita?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'agenda.php';
            form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${eventoId}">`;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function initializeCalendar() {
        if (calendar) calendar.destroy();
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
            events: {
                url: 'agenda.php?action=get_events',
                extraParams: function() {
                    return {
                        // Aquí puedes añadir parámetros adicionales si es necesario
                    };
                }
            },
            locale: 'es',
            eventClick: function(info) {
                abrirModalEvento(info.event);
            }
        });
        calendar.render();
    }

    document.getElementById('btn-list-view').addEventListener('click', function() {
        document.getElementById('list-view').style.display = 'block';
        document.getElementById('calendar-view').style.display = 'none';
        this.classList.add('active');
        document.getElementById('btn-calendar-view').classList.remove('active');
    });

    document.getElementById('btn-calendar-view').addEventListener('click', function() {
        document.getElementById('list-view').style.display = 'none';
        document.getElementById('calendar-view').style.display = 'block';
        this.classList.add('active');
        document.getElementById('btn-list-view').classList.remove('active');
        if (!calendar) {
            initializeCalendar();
        } else {
            calendar.render();
        }
    });
});