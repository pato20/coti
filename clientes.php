<?php
require_once 'includes/init.php'; // Inicializa sesión, DB, etc.

$page_title = "Gestión de Clientes";
$current_page = "clientes";

// Procesar formulario
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO clientes (nombre, email, telefono, direccion, rut) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['nombre'],
                    $_POST['email'],
                    $_POST['telefono'],
                    $_POST['direccion'],
                    $_POST['rut']
                ]);
                $success = "Cliente creado exitosamente";
                break;
            
            case 'update':
                $stmt = $pdo->prepare("UPDATE clientes SET nombre=?, email=?, telefono=?, direccion=?, rut=? WHERE id=?");
                $stmt->execute([
                    $_POST['nombre'],
                    $_POST['email'],
                    $_POST['telefono'],
                    $_POST['direccion'],
                    $_POST['rut'],
                    $_POST['id']
                ]);
                $success = "Cliente actualizado exitosamente";
                break;
            
            case 'delete':
                try {
                    // Check if client has quotations
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM cotizaciones WHERE cliente_id = ?");
                    $check_stmt->execute([$_POST['id']]);
                    $count = $check_stmt->fetchColumn();
                    
                    if ($count > 0) {
                        $error = "No se puede eliminar el cliente porque tiene cotizaciones asociadas. Elimine primero las cotizaciones.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id=?");
                        $stmt->execute([$_POST['id']]);
                        $success = "Cliente eliminado exitosamente";
                    }
                } catch (PDOException $e) {
                    $error = "Error al eliminar cliente: " . $e->getMessage();
                }
                break;
        }
    }
}

// Obtener clientes
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();

require_once 'includes/header.php'; // Dibuja el header, sidebar, etc.
?>

<!-- Enhanced professional page header -->
<div class="page-header slide-in-up">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2 mb-1">
                <i class="fas fa-users me-2 text-primary"></i>
                Gestión de Clientes
            </h1>
            <p class="text-muted mb-0">Administra la información de tus clientes de manera eficiente</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clienteModal">
            <i class="fas fa-plus me-2"></i> Nuevo Cliente
        </button>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show slide-in-up" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show slide-in-up" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Enhanced search and filter section -->
<div class="card mb-4 slide-in-up">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" placeholder="Buscar clientes..." id="searchInput">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterSelect">
                    <option value="">Todos los clientes</option>
                    <option value="activos">Clientes activos</option>
                    <option value="inactivos">Clientes inactivos</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-primary w-100" onclick="exportarClientes()">
                    <i class="fas fa-download me-2"></i> Exportar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced table with professional styling -->
<div class="card slide-in-up">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Lista de Clientes
            </h5>
            <span class="badge bg-primary"><?= count($clientes) ?> clientes</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="clientesTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag me-1"></i> ID</th>
                        <th><i class="fas fa-user me-1"></i> Nombre</th>
                        <th><i class="fas fa-envelope me-1"></i> Email</th>
                        <th><i class="fas fa-phone me-1"></i> Teléfono</th>
                        <th><i class="fas fa-id-card me-1"></i> RUT</th>
                        <th><i class="fas fa-calendar me-1"></i> Registro</th>
                        <th><i class="fas fa-cogs me-1"></i> Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark">#<?php echo $cliente['id']; ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                                <strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong>
                            </div>
                        </td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($cliente['email']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($cliente['email']); ?>
                            </a>
                        </td>
                        <td>
                            <a href="tel:<?php echo htmlspecialchars($cliente['telefono']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($cliente['telefono']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($cliente['rut']); ?></td>
                        <td>
                            <small class="text-muted">
                                <?php echo date('d/m/Y', strtotime($cliente['created_at'])); ?>
                            </small>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" onclick="editarCliente(<?php echo htmlspecialchars(json_encode($cliente)); ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info" onclick="verCliente(<?php echo $cliente['id']; ?>)" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarCliente(<?php echo $cliente['id']; ?>)" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Enhanced modal with professional styling -->
<div class="modal fade" id="clienteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="clienteForm">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        Nuevo Cliente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create" id="clienteAction">
                    <input type="hidden" name="id" id="clienteId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-user me-1"></i> Nombre Completo *
                            </label>
                            <input type="text" class="form-control" name="nombre" id="clienteNombre" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i> Email *
                            </label>
                            <input type="email" class="form-control" name="email" id="clienteEmail" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">
                                <i class="fas fa-phone me-1"></i> Teléfono *
                            </label>
                            <input type="text" class="form-control" name="telefono" id="clienteTelefono" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="rut" class="form-label">
                                <i class="fas fa-id-card me-1"></i> RUT
                            </label>
                            <input type="text" class="form-control" name="rut" id="clienteRut" placeholder="12.345.678-9">
                        </div>
                        
                        <div class="col-12">
                            <label for="direccion" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i> Dirección
                            </label>
                            <textarea class="form-control" name="direccion" id="clienteDireccion" rows="3" placeholder="Ingrese la dirección completa"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cliente Detalle Modal -->
<div class="modal fade" id="clienteDetalleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clienteDetalleTitulo">Detalles del Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="clienteDetalleBody">
                <!-- Contenido cargado por JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos de la página -->
<script>
    function editarCliente(cliente) {
        document.getElementById('clienteAction').value = 'update';
        document.getElementById('clienteId').value = cliente.id;
        document.getElementById('clienteNombre').value = cliente.nombre;
        document.getElementById('clienteEmail').value = cliente.email;
        document.getElementById('clienteTelefono').value = cliente.telefono;
        document.getElementById('clienteRut').value = cliente.rut || '';
        document.getElementById('clienteDireccion').value = cliente.direccion || '';
        
        document.querySelector('#clienteModal .modal-title').textContent = 'Editar Cliente';
        
        const modal = new bootstrap.Modal(document.getElementById('clienteModal'));
        modal.show();
    }

    function eliminarCliente(id) {
        if (confirm('¿Está seguro de que desea eliminar este cliente?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function exportarClientes() {
        window.open('ajax/exportar_clientes.php', '_blank');
    }

    function getEstadoColor(estado) {
        const colores = {
            'pendiente': 'secondary',
            'enviada': 'info',
            'aceptada': 'success',
            'rechazada': 'danger',
            'en_proceso': 'primary',
            'pausada': 'warning',
            'completada': 'success',
            'cancelada': 'danger',
            'pagado': 'success',
            'abonado': 'warning'
        };
        return colores[estado] || 'light';
    }

    function formatCurrency(number) {
        return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(number);
    }

    function buildCotizacionesTable(cotizaciones) {
        if (cotizaciones.length === 0) {
            return '<p class="text-center text-muted">No se encontraron cotizaciones.</p>';
        }
        let table = '<table class="table table-sm table-hover table-striped">';
        table += `
            <thead class="table-light">
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th class="text-end">Monto</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody>`;
        cotizaciones.forEach(cot => {
            table += `
                <tr>
                    <td>${cot.numero_cotizacion}</td>
                    <td>${new Date(cot.fecha_cotizacion).toLocaleDateString()}</td>
                    <td class="text-end">${formatCurrency(cot.total)}</td>
                    <td class="text-center">
                        <span class="badge bg-${getEstadoColor(cot.estado)} text-capitalize">${cot.estado}</span>
                    </td>
                    <td class="text-center">
                        <a href="cotizaciones.php?action=edit&id=${cot.id}" class="btn btn-xs btn-outline-primary" title="Ver Cotización">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>`;
        });
        table += '</tbody></table>';
        return table;
    }

    function buildOrdenesTable(ordenes) {
        if (ordenes.length === 0) {
            return '<p class="text-center text-muted">No se encontraron órdenes de trabajo.</p>';
        }
        let table = '<table class="table table-sm table-hover table-striped">';
        table += `
            <thead class="table-light">
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th class="text-end">Monto</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Pago</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody>`;
        ordenes.forEach(ot => {
            table += `
                <tr>
                    <td>${ot.numero_orden}</td>
                    <td>${new Date(ot.fecha_inicio).toLocaleDateString()}</td>
                    <td class="text-end">${formatCurrency(ot.monto_total)}</td>
                    <td class="text-center">
                        <span class="badge bg-${getEstadoColor(ot.estado)} text-capitalize">${ot.estado}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-${getEstadoColor(ot.estado_pago)} text-capitalize">${ot.estado_pago}</span>
                    </td>
                    <td class="text-center">
                        <a href="ordenes.php?action=view&id=${ot.id}" class="btn btn-xs btn-outline-primary" title="Ver Orden">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>`;
        });
        table += '</tbody></table>';
        return table;
    }

    function verCliente(id) {
        fetch(`ajax/get_cliente_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cliente = data.cliente;
                    const detalleBody = document.getElementById('clienteDetalleBody');
                    
                    let content = `
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-user me-2"></i>Nombre:</strong> ${cliente.nombre}</p>
                                <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> ${cliente.email}</p>
                                <p><strong><i class="fas fa-phone me-2"></i>Teléfono:</strong> ${cliente.telefono}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-id-card me-2"></i>RUT:</strong> ${cliente.rut || 'N/A'}</p>
                                <p><strong><i class="fas fa-map-marker-alt me-2"></i>Dirección:</strong> ${cliente.direccion || 'N/A'}</p>
                                <p><strong><i class="fas fa-calendar-alt me-2"></i>Registrado:</strong> ${new Date(cliente.created_at).toLocaleDateString()}</p>
                            </div>
                        </div>
                        <hr>
                        
                        <h5 class="mt-4"><i class="fas fa-clipboard-list me-2"></i>Órdenes de Trabajo</h5>
                        ${buildOrdenesTable(data.ordenes)}
                        
                        <h5 class="mt-4"><i class="fas fa-file-invoice-dollar me-2"></i>Cotizaciones</h5>
                        ${buildCotizacionesTable(data.cotizaciones)}
                    `;

                    detalleBody.innerHTML = content;
                    document.getElementById('clienteDetalleTitulo').textContent = 'Detalles de ' + cliente.nombre;
                    const detalleModal = new bootstrap.Modal(document.getElementById('clienteDetalleModal'));
                    detalleModal.show();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error de comunicación al obtener detalles del cliente.');
                console.error('Error:', error);
            });
    }

    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#clientesTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    document.getElementById('filterSelect').addEventListener('change', function() {
        // Implementar filtro por estado si es necesario
        alert('Filtro por estado en desarrollo');
    });

    // Reset form when modal is closed
    document.getElementById('clienteModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('clienteAction').value = 'create';
        document.getElementById('clienteId').value = '';
        document.querySelector('#clienteModal .modal-title').textContent = 'Nuevo Cliente';
        document.querySelector('#clienteModal form').reset();
    });
</script>

<?php require_once 'includes/footer.php'; ?>
