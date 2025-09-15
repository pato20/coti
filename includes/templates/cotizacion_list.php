<?php
// Obtener filtros de la URL
$filter_cliente_nombre = $_GET['filter_cliente_nombre'] ?? '';
$filter_numero_cotizacion = $_GET['filter_numero_cotizacion'] ?? '';
$filter_fecha_inicio = $_GET['filter_fecha_inicio'] ?? '';
$filter_fecha_fin = $_GET['filter_fecha_fin'] ?? '';
$filter_estado = $_GET['filter_estado'] ?? '';

// Construir la consulta con filtros
$sql = "SELECT c.*, cl.nombre as cliente_nombre 
        FROM cotizaciones c 
        LEFT JOIN clientes cl ON c.cliente_id = cl.id";
$where = [];
$params = [];

if (!empty($filter_cliente_nombre)) {
    $where[] = "cl.nombre LIKE ?";
    $params[] = "%{$filter_cliente_nombre}%";
}
if (!empty($filter_numero_cotizacion)) {
    $where[] = "c.numero_cotizacion LIKE ?";
    $params[] = "%{$filter_numero_cotizacion}%";
}
if (!empty($filter_fecha_inicio)) {
    $where[] = "c.fecha_cotizacion >= ?";
    $params[] = $filter_fecha_inicio;
}
if (!empty($filter_fecha_fin)) {
    $where[] = "c.fecha_cotizacion <= ?";
    $params[] = $filter_fecha_fin;
}
if (!empty($filter_estado)) {
    $where[] = "c.estado = ?";
    $params[] = $filter_estado;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header slide-in-up">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i> Cotizaciones</h1>
            <p class="text-muted mb-0">Gestiona todas las cotizaciones de tu negocio</p>
        </div>
        <a href="cotizaciones.php?action=new" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Nueva Cotización</a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    Cotización guardada exitosamente.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card mb-4 slide-in-up">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3"><input type="text" name="filter_cliente_nombre" class="form-control" placeholder="Filtrar por Cliente..." value="<?= htmlspecialchars($filter_cliente_nombre) ?>"></div>
            <div class="col-md-2"><input type="text" name="filter_numero_cotizacion" class="form-control" placeholder="Filtrar por N°..." value="<?= htmlspecialchars($filter_numero_cotizacion) ?>"></div>
            <div class="col-md-2"><input type="date" name="filter_fecha_inicio" class="form-control" value="<?= htmlspecialchars($filter_fecha_inicio) ?>"></div>
            <div class="col-md-2"><input type="date" name="filter_fecha_fin" class="form-control" value="<?= htmlspecialchars($filter_fecha_fin) ?>"></div>
            <div class="col-md-3">
                <select name="filter_estado" class="form-select">
                    <option value="">Todos los Estados</option>
                    <option value="pendiente" <?= ($filter_estado == 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                    <option value="enviada" <?= ($filter_estado == 'enviada') ? 'selected' : '' ?>>Enviada</option>
                    <option value="aceptada" <?= ($filter_estado == 'aceptada') ? 'selected' : '' ?>>Aceptada</option>
                    <option value="rechazada" <?= ($filter_estado == 'rechazada') ? 'selected' : '' ?>>Rechazada</option>
                </select>
            </div>
            <div class="col-12 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-2"></i> Aplicar</button>
                <a href="cotizaciones.php" class="btn btn-outline-secondary ms-2"><i class="fas fa-times me-2"></i> Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card slide-in-up">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($cotizaciones as $cot): ?>
                    <tr>
                        <td><span class="badge bg-light text-dark"><?= $cot['numero_cotizacion'] ?></span></td>
                        <td><strong><?= htmlspecialchars($cot['cliente_nombre']) ?></strong></td>
                        <td><small class="text-muted"><?= date('d/m/Y', strtotime($cot['fecha_cotizacion'])) ?></small></td>
                        <td><strong class="text-success"><?= formatCurrency($cot['total']) ?></strong></td>
                        <td><span class="badge bg-<?= getEstadoColor($cot['estado']) ?>"><?= ucfirst($cot['estado']) ?></span></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="cotizacion_pdf.php?id=<?= $cot['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Ver PDF"><i class="fas fa-eye"></i></a>
                                <a href="#" onclick="enviarPorWhatsapp(<?= $cot['id'] ?>, '<?= $cot['numero_cotizacion'] ?>', '<?= htmlspecialchars(addslashes($cot['cliente_nombre'])) ?>', '<?= formatCurrency($cot['total']) ?>')" class="btn btn-sm btn-outline-success" title="Enviar por WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                <a href="cotizaciones.php?action=edit&id=<?= $cot['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="fas fa-edit"></i></a>
                                <?php if ($cot['estado'] == 'pendiente' || $cot['estado'] == 'enviada'): ?>
                                <button class="btn btn-sm btn-success" title="Aprobar y Crear Orden" onclick="aprobarCotizacion(<?= $cot['id'] ?>)"><i class="fas fa-check"></i></button>
                                <?php endif; ?>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" title="Más acciones"><i class="fas fa-ellipsis-h"></i></button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $cot['id'] ?>, 'pendiente')">Marcar como Pendiente</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $cot['id'] ?>, 'enviada')">Marcar como Enviada</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $cot['id'] ?>, 'rechazada')">Marcar como Rechazada</a></li>
                                        <?php if ($cot['estado'] != 'aceptada'): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="eliminarCotizacion(<?= $cot['id'] ?>)">Eliminar</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
