<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/auth.php';

verificarAuth();

header('Content-Type: application/json');

$cliente_id = $_GET['id'] ?? null;

if (!$cliente_id || !is_numeric($cliente_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de cliente no válido.']);
    exit;
}

try {
    // 1. Get Client Details
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado.']);
        exit;
    }

    // 2. Get Client's Quotations
    $stmt_cotizaciones = $pdo->prepare("
        SELECT id, numero_cotizacion, fecha_cotizacion, total, estado
        FROM cotizaciones
        WHERE cliente_id = ?
        ORDER BY fecha_cotizacion DESC
    ");
    $stmt_cotizaciones->execute([$cliente_id]);
    $cotizaciones = $stmt_cotizaciones->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Client's Orders
    $stmt_ordenes = $pdo->prepare("
        SELECT id, numero_orden, fecha_inicio, monto_total, estado, estado_pago
        FROM ordenes_trabajo
        WHERE cliente_id = ?
        ORDER BY fecha_inicio DESC
    ");
    $stmt_ordenes->execute([$cliente_id]);
    $ordenes = $stmt_ordenes->fetchAll(PDO::FETCH_ASSOC);


    // 4. Return all data
    echo json_encode([
        'success' => true,
        'cliente' => $cliente,
        'cotizaciones' => $cotizaciones,
        'ordenes' => $ordenes
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener detalles del cliente: ' . $e->getMessage()]);
}
?>