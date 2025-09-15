<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

verificarRol(['admin', 'vendedor', 'tecnico']); // Ajustar roles según sea necesario

$report_type = $_GET['report_type'] ?? 'vendedor';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');

$report_title = '';
$report_data = [];
$columns = [];
$total_row = [];

// Replicar la lógica de reporte de reporte_detallado.php
switch ($report_type) {
    case 'vendedor':
        $report_title = 'Ventas por Vendedor';
        $columns = ['Vendedor', 'Cotizaciones Creadas', 'Monto Total Cotizado', 'Cotizaciones Aceptadas', 'Monto Aceptado', 'Tasa de Conversión'];
        $stmt = $pdo->prepare("
            SELECT u.nombre_completo as vendedor,
                   COUNT(c.id) as cotizaciones_creadas,
                   SUM(c.total) as monto_total_cotizado,
                   SUM(CASE WHEN c.estado = 'aceptada' THEN 1 ELSE 0 END) as cotizaciones_aceptadas,
                   SUM(CASE WHEN c.estado = 'aceptada' THEN c.total ELSE 0 END) as monto_aceptado
            FROM cotizaciones c
            JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.fecha_cotizacion BETWEEN :date_from AND :date_to
            GROUP BY u.nombre_completo
            ORDER BY monto_aceptado DESC
        ");
        $stmt->execute([
            ':date_from' => $date_from,
            ':date_to' => $date_to
        ]);
        $raw_report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate totals from raw data
        $total_creadas = array_sum(array_column($raw_report_data, 'cotizaciones_creadas'));
        $total_monto_cotizado = array_sum(array_column($raw_report_data, 'monto_total_cotizado'));
        $total_aceptadas = array_sum(array_column($raw_report_data, 'cotizaciones_aceptadas'));
        $total_monto_aceptado = array_sum(array_column($raw_report_data, 'monto_aceptado'));
        $total_tasa_conversion = ($total_creadas > 0) ? round(($total_aceptadas / $total_creadas) * 100, 2) . '%' : '0%';
        $total_row = ['Total', $total_creadas, formatCurrency($total_monto_cotizado), $total_aceptadas, formatCurrency($total_monto_aceptado), $total_tasa_conversion];

        // Format data for display
        foreach ($raw_report_data as $row) {
            $report_data[] = [
                'vendedor' => $row['vendedor'],
                'cotizaciones_creadas' => $row['cotizaciones_creadas'],
                'monto_total_cotizado' => formatCurrency($row['monto_total_cotizado']),
                'cotizaciones_aceptadas' => $row['cotizaciones_aceptadas'],
                'monto_aceptado' => formatCurrency($row['monto_aceptado']),
                'tasa_conversion' => ($row['cotizaciones_creadas'] > 0) ? round(($row['cotizaciones_aceptadas'] / $row['cotizaciones_creadas']) * 100, 2) . '%' : '0%'
            ];
        }
        break;

    case 'tecnico':
        $report_title = 'Órdenes por Técnico';
        $columns = ['Técnico', 'Órdenes Asignadas', 'Órdenes Completadas', 'Órdenes en Proceso', 'Órdenes Pausadas', 'Órdenes Canceladas', 'Monto Total Completado'];
        $stmt = $pdo->prepare("
            SELECT u.nombre_completo as tecnico,
                   COUNT(ot.id) as ordenes_asignadas,
                   SUM(CASE WHEN ot.estado = 'completada' THEN 1 ELSE 0 END) as ordenes_completadas,
                   SUM(CASE WHEN ot.estado = 'en_proceso' THEN 1 ELSE 0 END) as ordenes_en_proceso,
                   SUM(CASE WHEN ot.estado = 'pausada' THEN 1 ELSE 0 END) as ordenes_pausadas,
                   SUM(CASE WHEN ot.estado = 'cancelada' THEN 1 ELSE 0 END) as ordenes_canceladas,
                   SUM(CASE WHEN ot.estado = 'completada' THEN ot.monto_total ELSE 0 END) as monto_total_completado
            FROM ordenes_trabajo ot
            JOIN usuarios u ON ot.tecnico_id = u.id
            WHERE ot.fecha_inicio BETWEEN :date_from AND :date_to
            GROUP BY u.nombre_completo
            ORDER BY ordenes_completadas DESC
        ");
        $stmt->execute([
            ':date_from' => $date_from,
            ':date_to' => $date_to
        ]);
        $raw_report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate totals from raw data
        $total_asignadas = array_sum(array_column($raw_report_data, 'ordenes_asignadas'));
        $total_completadas = array_sum(array_column($raw_report_data, 'ordenes_completadas'));
        $total_en_proceso = array_sum(array_column($raw_report_data, 'ordenes_en_proceso'));
        $total_pausadas = array_sum(array_column($raw_report_data, 'ordenes_pausadas'));
        $total_canceladas = array_sum(array_column($raw_report_data, 'ordenes_canceladas'));
        $total_monto_completado = array_sum(array_column($raw_report_data, 'monto_total_completado'));
        $total_row = ['Total', $total_asignadas, $total_completadas, $total_en_proceso, $total_pausadas, $total_canceladas, formatCurrency($total_monto_completado)];

        // Format data for display
        foreach ($raw_report_data as $row) {
            $report_data[] = [
                'tecnico' => $row['tecnico'],
                'ordenes_asignadas' => $row['ordenes_asignadas'],
                'ordenes_completadas' => $row['ordenes_completadas'],
                'ordenes_en_proceso' => $row['ordenes_en_proceso'],
                'ordenes_pausadas' => $row['ordenes_pausadas'],
                'ordenes_canceladas' => $row['ordenes_canceladas'],
                'monto_total_completado' => formatCurrency($row['monto_total_completado'])
            ];
        }
        break;

    case 'ordenes_estado':
        $report_title = 'Detalle de Órdenes de Trabajo por Estado';
        $columns = ['Número Orden', 'Cliente', 'Estado', 'Fecha Inicio', 'Fecha Fin Real', 'Monto Total'];
        $stmt = $pdo->prepare("
            SELECT ot.numero_orden, cl.nombre as cliente_nombre, ot.estado, ot.fecha_inicio, ot.fecha_real_fin, ot.monto_total
            FROM ordenes_trabajo ot
            JOIN cotizaciones c ON ot.cotizacion_id = c.id
            JOIN clientes cl ON c.cliente_id = cl.id
            WHERE ot.fecha_inicio BETWEEN :date_from AND :date_to
            ORDER BY ot.estado, ot.fecha_inicio DESC
        ");
        $stmt->execute([
            ':date_from' => $date_from,
            ':date_to' => $date_to
        ]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($report_data as &$row) {
            $row['monto_total'] = formatCurrency($row['monto_total']);
            $row['fecha_inicio'] = date('d/m/Y', strtotime($row['fecha_inicio']));
            $row['fecha_real_fin'] = $row['fecha_real_fin'] ? date('d/m/Y', strtotime($row['fecha_real_fin'])) : 'N/A';
        }
        unset($row);
        break;

    case 'cotizaciones_estado':
        $report_title = 'Detalle de Cotizaciones por Estado';
        $columns = ['Número Cotización', 'Cliente', 'Estado', 'Fecha Cotización', 'Fecha Vencimiento', 'Monto Total'];
        $stmt = $pdo->prepare("
            SELECT c.numero_cotizacion, cl.nombre as cliente_nombre, c.estado, c.fecha_cotizacion, c.fecha_vencimiento, c.total as monto_total
            FROM cotizaciones c
            JOIN clientes cl ON c.cliente_id = cl.id
            WHERE c.fecha_cotizacion BETWEEN :date_from AND :date_to
            GROUP BY c.id, cl.nombre, c.estado, c.fecha_cotizacion, c.fecha_vencimiento, c.total
            ORDER BY c.estado, c.fecha_cotizacion DESC
        ");
        $stmt->execute([
            ':date_from' => $date_from,
            ':date_to' => $date_to
        ]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($report_data as &$row) {
            $row['monto_total'] = formatCurrency($row['monto_total']);
            $row['fecha_cotizacion'] = date('d/m/Y', strtotime($row['fecha_cotizacion']));
            $row['fecha_vencimiento'] = date('d/m/Y', strtotime($row['fecha_vencimiento']));
        }
        unset($row);
        break;

    case 'clientes_actividad':
        $report_title = 'Actividad por Cliente';
        $columns = ['Cliente', 'Cotizaciones', 'Monto Cotizado', 'Órdenes', 'Monto Órdenes', 'Pagos Recibidos'];
        $stmt = $pdo->prepare("
            SELECT cl.nombre as cliente_nombre,
                   COUNT(DISTINCT c.id) as total_cotizaciones,
                   SUM(DISTINCT c.total) as monto_total_cotizado,
                   COUNT(DISTINCT ot.id) as total_ordenes,
                   SUM(DISTINCT ot.monto_total) as monto_total_ordenes,
                   SUM(p.monto) as pagos_recibidos
            FROM clientes cl
            LEFT JOIN cotizaciones c ON cl.id = c.cliente_id AND c.fecha_cotizacion BETWEEN :date_from AND :date_to
            LEFT JOIN ordenes_trabajo ot ON c.id = ot.cotizacion_id AND ot.fecha_inicio BETWEEN :date_from AND :date_to
            LEFT JOIN pagos p ON cl.id = p.cliente_id AND p.fecha_pago BETWEEN :date_from AND :date_to
            GROUP BY cl.nombre
            ORDER BY cl.nombre ASC
        ");
        $stmt->execute([
            ':date_from' => $date_from,
            ':date_to' => $date_to
        ]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($report_data as &$row) {
            $row['monto_total_cotizado'] = formatCurrency($row['monto_total_cotizado'] ?? 0);
            $row['monto_total_ordenes'] = formatCurrency($row['monto_total_ordenes'] ?? 0);
            $row['pagos_recibidos'] = formatCurrency($row['pagos_recibidos'] ?? 0);
        }
        unset($row);
        break;

    case 'productos_vendidos':
        $report_title = 'Productos/Servicios Vendidos';
        $columns = ['Producto/Servicio', 'Cantidad Vendida', 'Monto Total'];
        $stmt = $pdo->prepare("
            SELECT COALESCE(ps.nombre, cd.descripcion_adicional) as item_nombre,
                   SUM(cd.cantidad) as cantidad_vendida,
                   SUM(cd.subtotal) as monto_total
            FROM cotizacion_detalles cd
            JOIN cotizaciones c ON cd.cotizacion_id = c.id
            LEFT JOIN productos_servicios ps ON cd.producto_servicio_id = ps.id
            WHERE c.estado = 'aceptada' AND c.fecha_cotizacion BETWEEN :date_from AND :date_to
            GROUP BY item_nombre
            ORDER BY monto_total DESC
        ");
        $stmt->execute([
            ':date_from' => $date_from,
            ':date_to' => $date_to
        ]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($report_data as &$row) {
            $row['monto_total'] = formatCurrency($row['monto_total']);
        }
        unset($row);
        break;

    case 'pagos_recibidos':
        $report_title = 'Pagos Recibidos';
        $columns = ['Fecha Pago', 'Cliente', 'Monto', 'Método Pago', 'Orden/Cotización Relacionada'];
        $stmt = $pdo->prepare("
            SELECT p.fecha_pago, cl.nombre as cliente_nombre, p.monto, p.metodo_pago,
                   COALESCE(ot.numero_orden, c.numero_cotizacion) as referencia
            FROM pagos p
            JOIN clientes cl ON p.cliente_id = cl.id
            LEFT JOIN ordenes_trabajo ot ON p.orden_id = ot.id
            LEFT JOIN cotizaciones c ON p.cotizacion_id = c.id
            WHERE p.fecha_pago BETWEEN :date_from AND :date_to
            ORDER BY p.fecha_pago DESC
        ");
        $stmt->execute([
            ':date_from' => $date_from,
            ':date_to' => $date_to
        ]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($report_data as &$row) {
            $row['monto'] = formatCurrency($row['monto']);
            $row['fecha_pago'] = date('d/m/Y', strtotime($row['fecha_pago']));
        }
        unset($row);
        break;

    default:
        $report_title = 'Reporte Desconocido';
        break;
}

// Obtener información de la empresa para el PDF
$stmt_empresa = $pdo->query("SELECT * FROM empresa WHERE id = 1");
$empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);

header('Content-Type: text/html; charset=UTF-8');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Detallado - <?= htmlspecialchars($report_title) ?></title>
    <style>
        @page {
            size: letter;
            margin: 1cm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #333;
            font-size: 10pt;
            line-height: 1.5;
        }
        .container {
            width: 100%;
        }
        .header {
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
            color: #2c3e50;
        }
        .header p {
            margin: 0;
            font-size: 10pt;
            color: #555;
        }
        .report-info {
            margin-bottom: 20px;
            text-align: center;
        }
        .report-info h2 {
            font-size: 14pt;
            color: #34495e;
            margin-bottom: 5px;
        }
        .report-info p {
            font-size: 9pt;
            color: #777;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f4f7f9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            color: #555;
        }
        .table tfoot tr {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 1cm;
            right: 1cm;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            text-align: center;
            font-size: 8pt;
            color: #777;
        }
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
        }
        @media print {
            .no-print { display: none; }
            body, .container { margin: 0; padding: 0; box-shadow: none; }
            .footer { position: fixed; bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">📄 Imprimir PDF</button>
        <button onclick="window.close()" class="btn btn-secondary">✖ Cerrar</button>
    </div>

    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($empresa['nombre']) ?></h1>
            <p><?= htmlspecialchars($empresa['direccion']) ?> | Tel: <?= htmlspecialchars($empresa['telefono']) ?> | Email: <?= htmlspecialchars($empresa['email']) ?></p>
        </div>

        <div class="report-info">
            <h2>Reporte: <?= htmlspecialchars($report_title) ?></h2>
            <p>Período: <?= date('d/m/Y', strtotime($date_from)) ?> - <?= date('d/m/Y', strtotime($date_to)) ?></p>
        </div>

        <div class="content">
            <table class="table">
                <thead>
                    <tr>
                        <?php foreach ($columns as $col): ?>
                            <th><?= htmlspecialchars($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report_data)): ?>
                        <tr>
                            <td colspan="<?= count($columns) > 0 ? count($columns) : 1 ?>" class="text-center text-muted p-4">
                                No hay datos para el reporte seleccionado en el rango de fechas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <td><?= htmlspecialchars($cell) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($total_row)): ?>
                <tfoot>
                    <tr>
                        <?php foreach ($total_row as $cell): ?>
                            <td><?= htmlspecialchars($cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <div class="footer">
            <p>Reporte generado el <?= date('d/m/Y H:i') ?> por el sistema de gestión.</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(() => window.print(), 500);
        };
    </script>
</body>
</html>