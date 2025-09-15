document.addEventListener('DOMContentLoaded', function() {
    const eventoModal = new bootstrap.Modal(document.getElementById('eventoModal'));
    const calendarEl = document.getElementById('calendar');
    let calendar;

    window.abrirModalEvento = function(evento = null) {
        const form = document.getElementById('eventoForm');
        form.reset();
        document.getElementById('delete-section').style.display = 'none';

        if (evento) {
            document.getElementById('modalTitulo').innerText = 'Editar Visita';
            document.getElementById('eventoId').value = evento.id;
            document.getElementById('eventoTitulo').value = evento.title || evento.titulo;
            document.getElementById('eventoCliente').value = evento.extendedProps?.cliente_id || evento.cliente_id || '';
            document.getElementById('eventoDescripcion').value = evento.extendedProps?.descripcion || evento.descripcion || '';
            const fecha = evento.start || evento.fecha_hora_inicio;
            document.getElementById('eventoFecha').value = fecha ? fecha.slice(0, 16) : '';
            document.getElementById('eventoTipo').value = evento.extendedProps?.tipo || evento.tipo || 'visita';
            const userSelect = document.getElementById('eventoUsuario');
            if (userSelect) {
                userSelect.value = evento.extendedProps?.usuario_id || evento.usuario_id;
            }
            document.getElementById('delete-section').style.display = 'block';
        } else {
            document.getElementById('modalTitulo').innerText = 'Nueva Visita';
            document.getElementById('eventoId').value = '';
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
            events: 'agenda.php?action=get_events',
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
