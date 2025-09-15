<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

verificarAuth();

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="reporte_cotizaciones_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

$database = new Database();
$db = $database->getConnection();

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');
$cliente_id = $_GET['cliente_id'] ?? '';
$estado = $_GET['estado'] ?? '';

// Consulta principal
$query = "SELECT c.numero_cotizacion, cl.nombre as cliente, c.fecha_cotizacion, 
          c.subtotal, c.iva, c.total, c.estado, c.observaciones
          FROM cotizaciones c 
          LEFT JOIN clientes cl ON c.cliente_id = cl.id 
          WHERE c.fecha_cotizacion BETWEEN ? AND ?";
$params = [$fecha_inicio, $fecha_fin];

if ($cliente_id) {
    $query .= " AND c.cliente_id = ?";
    $params[] = $cliente_id;
}
if ($estado) {
    $query .= " AND c.estado = ?";
    $params[] = $estado;
}

$query .= " ORDER BY c.fecha_cotizacion DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generar tabla HTML para Excel
echo '<table border="1">';
echo '<tr>';
echo '<th>Número Cotización</th>';
echo '<th>Cliente</th>';
echo '<th>Fecha</th>';
echo '<th>Subtotal</th>';
echo '<th>IVA</th>';
echo '<th>Total</th>';
echo '<th>Estado</th>';
echo '<th>Observaciones</th>';
echo '</tr>';

foreach($cotizaciones as $cot) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($cot['numero_cotizacion']) . '</td>';
    echo '<td>' . htmlspecialchars($cot['cliente']) . '</td>';
    echo '<td>' . date('d/m/Y', strtotime($cot['fecha_cotizacion'])) . '</td>';
    echo '<td>' . number_format($cot['subtotal'], 0, ',', '.') . '</td>';
    echo '<td>' . number_format($cot['iva'], 0, ',', '.') . '</td>';
    echo '<td>' . number_format($cot['total'], 0, ',', '.') . '</td>';
    echo '<td>' . ucfirst($cot['estado']) . '</td>';
    echo '<td>' . htmlspecialchars($cot['observaciones']) . '</td>';
    echo '</tr>';
}

echo '</table>';
?>
