<!-- Variables requeridas: $clientes, $usuarios, $user_rol -->

<!-- Modal para Evento (Crear/Editar) -->
<div class="modal fade" id="eventoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="agenda.php" id="eventoForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Nueva Visita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="eventoId">
                    
                    <div class="mb-3"><label class="form-label">Título *</label><input type="text" name="titulo" id="eventoTitulo" class="form-control" required></div>
                    <div class="mb-3" id="cliente-select-div">
                        <label class="form-label">Cliente</label>
                        <select name="cliente_id" id="eventoCliente" class="form-select">
                            <option value="">Seleccionar...</option>
                            <?php foreach($clientes as $cliente): ?><option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nombre']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="sinClienteCheck" name="sin_cliente_registrado" value="true">
                        <label class="form-check-label" for="sinClienteCheck">
                            Visita sin cliente registrado
                        </label>
                    </div>
                    <div class="mb-3 d-none" id="nombre-referencia-div">
                        <label class="form-label">Nombre de Referencia</label>
                        <input type="text" name="nombre_referencia" id="nombreReferencia" class="form-control" disabled>
                    </div>

                    <?php if ($user_rol == 'admin'): ?>
                    <div class="mb-3">
                        <label class="form-label">Asignar a *</label>
                        <select name="usuario_id" id="eventoUsuario" class="form-select" required>
                            <?php foreach($usuarios as $usuario): ?><option value="<?= $usuario['id'] ?>"><?= htmlspecialchars($usuario['nombre_completo']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3"><label class="form-label">Fecha y Hora *</label><input type="datetime-local" name="fecha_hora_inicio" id="eventoFecha" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Descripción</label><textarea name="descripcion" id="eventoDescripcion" class="form-control" rows="3"></textarea></div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" id="eventoTipo" class="form-select">
                            <option value="visita">Visita</option>
                            <option value="mantencion">Mantención</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" id="eventoEstado" class="form-select">
                            <option value="pendiente">Pendiente</option>
                            <option value="completada">Completada</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>

                    <div id="delete-section" style="display: none;"><hr><button type="button" class="btn btn-danger" onclick="eliminarEvento()"><i class="fas fa-trash"></i> Eliminar</button></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sinClienteCheck = document.getElementById('sinClienteCheck');
    const clienteSelectDiv = document.getElementById('cliente-select-div');
    const nombreReferenciaDiv = document.getElementById('nombre-referencia-div');
    const clienteSelect = document.getElementById('eventoCliente');
    const nombreReferenciaInput = document.getElementById('nombreReferencia');
    const eventoModal = document.getElementById('eventoModal');
    const eventoTituloInput = document.getElementById('eventoTitulo');
    const eventoIdInput = document.getElementById('eventoId');
    const eventoFechaInput = document.getElementById('eventoFecha');
    const eventoDescripcionInput = document.getElementById('eventoDescripcion');
    const eventoTipoSelect = document.getElementById('eventoTipo');
    const eventoEstadoSelect = document.getElementById('eventoEstado');
    const eventoUsuarioSelect = document.getElementById('eventoUsuario');
    const deleteSection = document.getElementById('delete-section');

    function toggleClienteFields(isSinCliente) {
        if (isSinCliente) {
            clienteSelectDiv.classList.add('d-none');
            nombreReferenciaDiv.classList.remove('d-none');
            clienteSelect.disabled = true;
            nombreReferenciaInput.disabled = false;
            clienteSelect.value = '';
        } else {
            clienteSelectDiv.classList.remove('d-none');
            nombreReferenciaDiv.classList.add('d-none');
            clienteSelect.disabled = false;
            nombreReferenciaInput.disabled = true;
            nombreReferenciaInput.value = '';
        }
    }

    if (sinClienteCheck) {
        sinClienteCheck.addEventListener('change', function() {
            toggleClienteFields(this.checked);
        });
    }

    if (eventoModal) {
        eventoModal.addEventListener('show.bs.modal', function (event) {
            // Resetear formulario y campos
            document.getElementById('eventoForm').reset();
            eventoIdInput.value = '';
            deleteSection.style.display = 'none';
            if (sinClienteCheck) {
                sinClienteCheck.checked = false;
            }
            toggleClienteFields(false); // Resetear a estado por defecto
            eventoEstadoSelect.value = 'pendiente'; // Estado por defecto

            // The data population and title setting is handled by abrirModalEvento in agenda.js
            // This listener only performs a reset and initial state setup.
            
            // For new visit, ensure title is empty and status is pending
            const button = event.relatedTarget; // Botón que disparó el modal
            const isEdit = button && button.dataset.evento; // Si viene de un botón de edición
            if (!isEdit) {
                document.getElementById('modalTitulo').innerText = 'Nueva Visita'; // Set title for new visit
                eventoTituloInput.value = '';
                eventoEstadoSelect.value = 'pendiente';
            }
        });
    }
});