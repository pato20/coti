<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

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
        $total_row = ['Total', $total_creadas, $total_monto_cotizado, $total_aceptadas, $total_monto_aceptado, $total_tasa_conversion];

        // Format data for display
        foreach ($raw_report_data as $row) {
            $report_data[] = [
                'vendedor' => $row['vendedor'],
                'cotizaciones_creadas' => $row['cotizaciones_creadas'],
                'monto_total_cotizado' => $row['monto_total_cotizado'],
                'cotizaciones_aceptadas' => $row['cotizaciones_aceptadas'],
                'monto_aceptado' => $row['monto_aceptado'],
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
        $total_row = ['Total', $total_asignadas, $total_completadas, $total_en_proceso, $total_pausadas, $total_canceladas, $total_monto_completado];

        // Format data for display
        foreach ($raw_report_data as $row) {
            $report_data[] = [
                'tecnico' => $row['tecnico'],
                'ordenes_asignadas' => $row['ordenes_asignadas'],
                'ordenes_completadas' => $row['ordenes_completadas'],
                'ordenes_en_proceso' => $row['ordenes_en_proceso'],
                'ordenes_pausadas' => $row['ordenes_pausadas'],
                'ordenes_canceladas' => $row['ordenes_canceladas'],
                'monto_total_completado' => $row['monto_total_completado']
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
            $row['fecha_pago'] = date('d/m/Y', strtotime($row['fecha_pago']));
        }
        unset($row);
        break;

    default:
        $report_title = 'Reporte Desconocido';
        break;
}

// Set headers for Excel download
$filename = "Reporte_Detallado_" . str_replace(' ', '_', $report_title) . "_" . $date_from . "_a_" . $date_to . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// Output to Excel
$output = fopen("php://output", "w");

// Header Row
fputcsv($output, $columns, "\t");

// Data Rows
foreach ($report_data as $row) {
    // Convert numeric values to string for Excel to avoid scientific notation
    foreach ($row as $key => &$value) {
        if (is_numeric($value)) {
            $value = (string)$value;
        }
    }
    fputcsv($output, array_values($row), "\t");
}

// Totals Row
if (!empty($total_row)) {
    fputcsv($output, [], "\t"); // Empty row for separation
    // Convert numeric values in total_row to string for Excel
    foreach ($total_row as $key => &$value) {
        if (is_numeric($value)) {
            $value = (string)$value;
        }
    }
    fputcsv($output, $total_row, "\t");
}

fclose($output);
exit();