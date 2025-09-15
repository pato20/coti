<?php
// Obtener filtros de la URL
$filter_cliente_nombre = $_GET['filter_cliente_nombre'] ?? '';
$filter_numero_orden = $_GET['filter_numero_orden'] ?? '';
$filter_fecha_inicio = $_GET['filter_fecha_inicio'] ?? '';
$filter_fecha_fin = $_GET['filter_fecha_fin'] ?? '';
$filter_estado = $_GET['filter_estado'] ?? '';
$filter_estado_pago = $_GET['filter_estado_pago'] ?? '';

// Construir la consulta de órdenes con filtros
$query_ordenes = "SELECT ot.*, cl.nombre as cliente_nombre 
                FROM ordenes_trabajo ot 
                LEFT JOIN clientes cl ON ot.cliente_id = cl.id 
                WHERE 1=1";
$params_ordenes = [];

if ($filter_cliente_nombre) {
    $query_ordenes .= " AND cl.nombre LIKE ?";
    $params_ordenes[] = '%' . $filter_cliente_nombre . '%';
}
if ($filter_numero_orden) {
    $query_ordenes .= " AND ot.numero_orden LIKE ?";
    $params_ordenes[] = '%' . $filter_numero_orden . '%';
}
if ($filter_fecha_inicio) {
    $query_ordenes .= " AND ot.fecha_inicio >= ?";
    $params_ordenes[] = $filter_fecha_inicio;
}
if ($filter_fecha_fin) {
    $query_ordenes .= " AND ot.fecha_inicio <= ?";
    $params_ordenes[] = $filter_fecha_fin;
}
if ($filter_estado) {
    $query_ordenes .= " AND ot.estado = ?";
    $params_ordenes[] = $filter_estado;
}
if ($filter_estado_pago) {
    $query_ordenes .= " AND ot.estado_pago = ?";
    $params_ordenes[] = $filter_estado_pago;
}

$query_ordenes .= " ORDER BY ot.created_at DESC";

$stmt = $pdo->prepare($query_ordenes);
$stmt->execute($params_ordenes);
$ordenes = $stmt->fetchAll();
?>

<div class="page-header">
    <h1 class="h2"><i class="fas fa-clipboard-list me-2"></i>Órdenes de Trabajo</h1>
</div>

<div class="card mb-4">
    <div class="card-header">Filtros de Búsqueda</div>
    <div class="card-body">
        <form method="GET" action="ordenes.php" class="row g-3">
            <div class="col-md-3"><input type="text" name="filter_cliente_nombre" class="form-control" value="<?= htmlspecialchars($filter_cliente_nombre) ?>" placeholder="Nombre del cliente"></div>
            <div class="col-md-2"><input type="text" name="filter_numero_orden" class="form-control" value="<?= htmlspecialchars($filter_numero_orden) ?>" placeholder="N° Orden"></div>
            <div class="col-md-2"><input type="date" name="filter_fecha_inicio" class="form-control" value="<?= htmlspecialchars($filter_fecha_inicio) ?>"></div>
            <div class="col-md-2"><input type="date" name="filter_fecha_fin" class="form-control" value="<?= htmlspecialchars($filter_fecha_fin) ?>"></div>
            <div class="col-md-3">
                <select name="filter_estado" class="form-select">
                    <option value="">Estado Trabajo</option>
                    <option value="pendiente" <?= $filter_estado == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="en_proceso" <?= $filter_estado == 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                    <option value="pausada" <?= $filter_estado == 'pausada' ? 'selected' : '' ?>>Pausada</option>
                    <option value="completada" <?= $filter_estado == 'completada' ? 'selected' : '' ?>>Completada</option>
                    <option value="cancelada" <?= $filter_estado == 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="filter_estado_pago" class="form-select">
                    <option value="">Estado Pago</option>
                    <option value="pendiente" <?= $filter_estado_pago == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="abonado" <?= $filter_estado_pago == 'abonado' ? 'selected' : '' ?>>Abonado</option>
                    <option value="pagado" <?= $filter_estado_pago == 'pagado' ? 'selected' : '' ?>>Pagado</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i> Aplicar</button>
                <a href="ordenes.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Número</th><th>Cliente</th><th>Fecha Inicio</th><th>Monto</th><th>Progreso</th><th>Estado</th><th>Pago</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach($ordenes as $orden): ?>
                    <tr>
                        <td><?= htmlspecialchars($orden['numero_orden']) ?></td>
                        <td><?= htmlspecialchars($orden['cliente_nombre']) ?></td>
                        <td><?= date('d/m/Y', strtotime($orden['fecha_inicio'])) ?></td>
                        <td><?= formatCurrency($orden['monto_total']) ?></td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" role="progressbar" style="width: <?= $orden['porcentaje_avance'] ?>%;" aria-valuenow="<?= $orden['porcentaje_avance'] ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?= $orden['porcentaje_avance'] ?>%
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-<?= getEstadoColor($orden['estado']) ?>"><?= ucfirst($orden['estado']) ?></span></td>
                        <td><span class="badge bg-<?= getEstadoColor($orden['estado_pago']) ?>"><?= ucfirst($orden['estado_pago']) ?></span></td>
                        <td>
                            <a href="ordenes.php?action=view&id=<?= $orden['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver Detalles"><i class="fas fa-eye"></i></a>
                            <a href="orden_pdf.php?id=<?= $orden['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
                            <?php if ($orden['estado'] == 'cancelada'): ?>
                            <button class="btn btn-sm btn-danger ms-1" title="Eliminar Orden" onclick="eliminarOrden(<?= $orden['id'] ?>)"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
