<!-- Variables requeridas: $orden, $pagos, $seguimientos, $id -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2"><i class="fas fa-clipboard-list me-2"></i>Orden de Trabajo: <?= htmlspecialchars($orden['numero_orden']) ?></h1>
            <p class="text-muted">Gestión y seguimiento detallado de la orden.</p>
        </div>
        <div class="btn-toolbar">
            <div class="btn-group me-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateProgressModal"><i class="fas fa-edit"></i> Actualizar Progreso</button>
                <?php if ($orden['requiere_mantencion']): ?>
                <button class="btn btn-secondary" onclick="agendarMantenciones(<?= $id ?>)"><i class="fas fa-calendar-plus"></i> Agendar Mantenciones</button>
                <?php endif; ?>
            </div>
            <a href="ordenes.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Información General</h5></div>
            <div class="card-body">
                <p><strong>Cliente:</strong> <?= htmlspecialchars($orden['cliente_nombre']) ?></p>
                <p><strong>Cotización:</strong> <?= htmlspecialchars($orden['numero_cotizacion']) ?></p>
                <p><strong>Fecha Inicio:</strong> <?= date('d/m/Y', strtotime($orden['fecha_inicio'])) ?></p>
                <p><strong>Estado:</strong> <span class="badge bg-<?= getEstadoColor($orden['estado']) ?>"><?= ucfirst($orden['estado']) ?></span></p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Gestión de Pagos</h5></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col"><strong>Monto Total:</strong> <?= formatCurrency($orden['monto_total']) ?></div>
                    <div class="col"><strong>Monto Pagado:</strong> <span class="text-success"><?= formatCurrency($orden['monto_pagado']) ?></span></div>
                    <div class="col"><strong>Saldo Pendiente:</strong> <span class="text-danger"><?= formatCurrency($orden['monto_total'] - $orden['monto_pagado']) ?></span></div>
                    <div class="col"><strong>Estado Pago:</strong> <span class="badge bg-<?= getEstadoColor($orden['estado_pago']) ?>"><?= ucfirst($orden['estado_pago']) ?></span></div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pagoModal"><i class="fas fa-plus"></i> Registrar Pago</button>
                <h6 class="mt-4">Historial de Pagos</h6>
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach($pagos as $pago): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></td>
                            <td><?= formatCurrency($pago['monto']) ?></td>
                            <td><?= htmlspecialchars($pago['metodo_pago']) ?></td>
                            <td><button class="btn btn-sm btn-outline-danger" onclick="eliminarPago(<?= $pago['id'] ?>, <?= $orden['id'] ?>, <?= $pago['monto'] ?>)"><i class="fas fa-trash"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5 class="card-title mb-0">Historial de Seguimiento</h5></div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach($seguimientos as $seg): ?>
                    <div class="timeline-item">
                        <h6 class="mb-1"><?= htmlspecialchars($seg['descripcion']) ?></h6>
                        <p class="mb-1 text-muted">Progreso: <span class="badge bg-light text-dark"><?= $seg['porcentaje_anterior'] ?>%</span> → <span class="badge bg-primary"><?= $seg['porcentaje_actual'] ?>%</span></p>
                        <small class="text-muted"><i class="fas fa-calendar me-1"></i><?= date('d/m/Y', strtotime($seg['fecha'])) ?> - <i class="fas fa-user me-1"></i><?= htmlspecialchars($seg['usuario']) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Visual Progress, Contact, Modals etc. -->
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="updateProgressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="ordenes.php?action=update_progress">
                <div class="modal-header"><h5 class="modal-title">Actualizar Progreso</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="orden_id" value="<?= $id ?>">
                    <div class="mb-3">
                        <label class="form-label">Porcentaje de Avance: <span id="porcentaje-display"><?= $orden['porcentaje_avance'] ?>%</span></label>
                        <input type="range" name="porcentaje" class="form-range" min="0" max="100" value="<?= $orden['porcentaje_avance'] ?>" oninput="document.getElementById('porcentaje-display').textContent = this.value + '%'">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="en_proceso" <?= $orden['estado'] == 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                            <option value="pausada" <?= $orden['estado'] == 'pausada' ? 'selected' : '' ?>>Pausada</option>
                            <option value="completada" <?= $orden['estado'] == 'completada' ? 'selected' : '' ?>>Completada</option>
                            <option value="cancelada" <?= $orden['estado'] == 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción del Avance</label>
                        <textarea name="descripcion" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Registrar Pago -->
<div class="modal fade" id="pagoModal" tabindex="-1" aria-labelledby="pagoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pagoModalLabel">Registrar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="pagoForm">
                    <input type="hidden" name="orden_id" id="orden_id_pago" value="<?= $id ?>">
                    <div class="mb-3">
                        <label for="monto" class="form-label">Monto</label>
                        <input type="number" class="form-control" id="monto" name="monto" required>
                    </div>
                    <div class="mb-3">
                        <label for="metodo_pago" class="form-label">Método de Pago</label>
                        <select class="form-select" id="metodo_pago" name="metodo_pago">
                            <option value="Transferencia">Transferencia</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                            <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_pago" class="form-label">Fecha del Pago</label>
                        <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Referencia (Opcional)</label>
                        <input type="text" class="form-control" id="observaciones" name="observaciones">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
