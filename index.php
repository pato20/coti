<?php
require_once 'includes/init.php';

$page_title = 'Dashboard';
$current_page = 'index';

// --- KPIs y Estadísticas ---
$stats = [];
$stats['cotizaciones_pendientes'] = $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'pendiente'")->fetchColumn();
$stats['cotizaciones_aceptadas_mes'] = $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'aceptada' AND MONTH(fecha_cotizacion) = MONTH(CURDATE()) AND YEAR(fecha_cotizacion) = YEAR(CURDATE())")->fetchColumn();
$stats['ordenes_proceso'] = $pdo->query("SELECT COUNT(*) FROM ordenes_trabajo WHERE estado = 'en_proceso'")->fetchColumn();
$stats['total_clientes'] = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$stats['ventas_mes'] = $pdo->query("SELECT SUM(total) FROM cotizaciones WHERE estado = 'aceptada' AND MONTH(fecha_cotizacion) = MONTH(CURDATE()) AND YEAR(fecha_cotizacion) = YEAR(CURDATE())")->fetchColumn() ?? 0;
$stats['productos_stock_bajo'] = $pdo->query("SELECT COUNT(*) FROM productos_servicios WHERE tipo = 'producto' AND stock <= 5")->fetchColumn(); // Stock bajo <= 5

// --- Datos para las listas ---

// Últimas Cotizaciones
$ultimas_cotizaciones = $pdo->query("SELECT c.*, cl.nombre as cliente_nombre 
    FROM cotizaciones c 
    JOIN clientes cl ON c.cliente_id = cl.id 
    ORDER BY c.fecha_cotizacion DESC, c.id DESC 
    LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Últimas Órdenes Completadas
$ultimas_ordenes_completadas = $pdo->query("SELECT ot.*, c.nombre as cliente_nombre 
    FROM ordenes_trabajo ot
    JOIN cotizaciones cot ON ot.cotizacion_id = cot.id
    JOIN clientes c ON cot.cliente_id = c.id
    WHERE ot.estado = 'completada' 
    ORDER BY ot.fecha_real_fin DESC, ot.id DESC 
    LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Próximas Visitas Técnicas (sin OT asociada)
$proximas_visitas_sql = "SELECT a.*, c.nombre as cliente_nombre 
    FROM agenda a 
    LEFT JOIN clientes c ON a.cliente_id = c.id 
    WHERE a.orden_id IS NULL";
if ($_SESSION['rol'] != 'admin') {
    $proximas_visitas_sql .= " AND a.usuario_id = :usuario_id";
}
$proximas_visitas_sql .= " ORDER BY a.fecha_hora_inicio ASC LIMIT 5";
$stmt_visitas = $pdo->prepare($proximas_visitas_sql);
if ($_SESSION['rol'] != 'admin') {
    $stmt_visitas->execute(['usuario_id' => $_SESSION['usuario_id']]);
} else {
    $stmt_visitas->execute();
}
$proximas_visitas = $stmt_visitas->fetchAll(PDO::FETCH_ASSOC);




require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2">Dashboard</h1>
            <p class="text-muted">Bienvenido, <?= htmlspecialchars($_SESSION['nombre_completo']) ?>. Aquí tienes un resumen de la actividad reciente.</p>
        </div>
        <a href="cotizaciones.php?action=new" class="btn btn-primary d-none d-md-inline-flex"><i class="fas fa-plus me-2"></i> Nueva Cotización</a>
    </div>
</div>

<!-- KPIs -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3"><div class="card shadow-sm"><div class="card-body"><h5 class="card-title text-muted">Cotiz. Pendientes</h5><p class="h3 fw-bold text-primary"><?= $stats['cotizaciones_pendientes'] ?></p><i class="fas fa-file-invoice-dollar card-icon"></i></div></div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="card shadow-sm"><div class="card-body"><h5 class="card-title text-muted">Cotiz. Aceptadas (Mes)</h5><p class="h3 fw-bold text-success"><?= $stats['cotizaciones_aceptadas_mes'] ?></p><i class="fas fa-check-circle card-icon"></i></div></div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="card shadow-sm"><div class="card-body"><h5 class="card-title text-muted">Órdenes En Proceso</h5><p class="h3 fw-bold text-warning"><?= $stats['ordenes_proceso'] ?></p><i class="fas fa-cogs card-icon"></i></div></div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="card shadow-sm"><div class="card-body"><h5 class="card-title text-muted">Ventas del Mes</h5><p class="h3 fw-bold text-success"><?= formatCurrency($stats['ventas_mes']) ?></p><i class="fas fa-chart-line card-icon"></i></div></div></div>
</div>
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3"><div class="card shadow-sm"><div class="card-body"><h5 class="card-title text-muted">Total Clientes</h5><p class="h3 fw-bold text-info"><?= $stats['total_clientes'] ?></p><i class="fas fa-users card-icon"></i></div></div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="card shadow-sm"><div class="card-body"><h5 class="card-title text-muted">Stock Bajo</h5><p class="h3 fw-bold text-danger"><?= $stats['productos_stock_bajo'] ?></p><i class="fas fa-boxes card-icon"></i></div></div></div>
</div>

<div class="row">
    <!-- Columna Principal (Izquierda) -->
    <div class="col-lg-8">
        <!-- Últimas Cotizaciones -->
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5><i class="fas fa-file-alt me-2"></i>Últimas Cotizaciones Agregadas</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Cliente</th><th class="text-end">Total</th><th class="text-center">Estado</th><th class="text-end">Acción</th></tr></thead>
                        <tbody>
                            <?php if (empty($ultimas_cotizaciones)): ?>
                                <tr><td colspan="5" class="text-center text-muted p-4">No hay cotizaciones recientes.</td></tr>
                            <?php else: ?>
                                <?php foreach ($ultimas_cotizaciones as $cot): ?>
                                    <tr>
                                        <td><a href="cotizaciones.php?action=edit&id=<?= $cot['id'] ?>" class="fw-bold"><?= htmlspecialchars($cot['numero_cotizacion']) ?></a></td>
                                        <td><?= htmlspecialchars($cot['cliente_nombre']) ?></td>
                                        <td class="text-end"><?= formatCurrency($cot['total']) ?></td>
                                        <td class="text-center"><span class="badge bg-<?= getEstadoColor($cot['estado']) ?>"><?= ucfirst($cot['estado']) ?></span></td>
                                        <td class="text-end"><a href="cotizacion_pdf.php?id=<?= $cot['id'] ?>" target="_blank" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Últimas Órdenes Completadas -->
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5><i class="fas fa-check-circle me-2"></i>Últimas Órdenes Completadas</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Cliente</th><th class="text-end">Monto</th><th class="text-center">Estado Pago</th><th class="text-end">Acción</th></tr></thead>
                        <tbody>
                            <?php if (empty($ultimas_ordenes_completadas)): ?>
                                <tr><td colspan="5" class="text-center text-muted p-4">No hay órdenes completadas recientemente.</td></tr>
                            <?php else: ?>
                                <?php foreach ($ultimas_ordenes_completadas as $ot): ?>
                                    <tr>
                                        <td><a href="ordenes.php?action=view&id=<?= $ot['id'] ?>" class="fw-bold"><?= htmlspecialchars($ot['numero_orden']) ?></a></td>
                                        <td><?= htmlspecialchars($ot['cliente_nombre']) ?></td>
                                        <td class="text-end"><?= formatCurrency($ot['monto_total']) ?></td>
                                        <td class="text-center"><span class="badge bg-<?= getEstadoColor($ot['estado_pago']) ?>"><?= ucfirst($ot['estado_pago']) ?></span></td>
                                        <td class="text-end"><a href="orden_pdf.php?id=<?= $ot['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-pdf"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna Lateral (Derecha) -->
    <div class="col-lg-4">
        <!-- Acciones Rápidas -->
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5><i class="fas fa-rocket me-2"></i>Acciones Rápidas</h5></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="cotizaciones.php?action=new" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Nueva Cotización</a>
                    <a href="clientes.php?action=new" class="btn btn-success"><i class="fas fa-user-plus me-2"></i>Nuevo Cliente</a>
                    <a href="agenda.php" class="btn btn-info"><i class="fas fa-calendar-alt me-2"></i>Ir a la Agenda</a>
                    <a href="ordenes.php" class="btn btn-secondary"><i class="fas fa-list-alt me-2"></i>Ver Órdenes</a>
                </div>
            </div>
        </div>

        <!-- Próximas Visitas (Sin OT) -->
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5><i class="fas fa-user-clock me-2"></i>Próximas Visitas Técnicas</h5></div>
            <ul class="list-group list-group-flush">
                <?php if (empty($proximas_visitas)): ?>
                    <li class="list-group-item text-muted">No hay visitas programadas.</li>
                <?php else: ?>
                    <?php foreach ($proximas_visitas as $visita): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">
                                    <a href="agenda.php?id=<?= $visita['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($visita['cliente_nombre'] ?? 'Sin Cliente Registrado') ?>
                                    </a>
                                </span>
                                <span class="text-muted"><?= date('d/m/y H:i', strtotime($visita['fecha_hora_inicio'])) ?></span>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars(isset($visita['titulo']) && is_string($visita['titulo']) ? $visita['titulo'] : '') ?></small>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        
    </div>
</div>

<style>
.card-icon {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 2.5rem;
    opacity: 0.15;
}
</style>

<?php require_once 'includes/footer.php'; ?>