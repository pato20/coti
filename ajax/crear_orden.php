<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cotizacion_id = $input['cotizacion_id'] ?? null;

if (!$cotizacion_id) {
    echo json_encode(['success' => false, 'message' => 'ID de cotización requerido']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    error_log("[v0] Starting order creation for cotizacion_id: $cotizacion_id");
    
    // Verificar que la cotización existe y está aceptada
    $stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ? AND estado = 'aceptada'");
    $stmt->execute([$cotizacion_id]);
    $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cotizacion) {
        error_log("[v0] Cotización not found or not accepted");
        throw new Exception('Cotización no encontrada o no está aceptada');
    }
    
    error_log("[v0] Cotización found: " . json_encode($cotizacion));
    
    // Verificar si ya existe una orden para esta cotización
    $stmt = $pdo->prepare("SELECT id FROM ordenes_trabajo WHERE cotizacion_id = ?");
    $stmt->execute([$cotizacion_id]);
    if ($stmt->fetch()) {
        error_log("[v0] Order already exists for this cotización");
        throw new Exception('Ya existe una orden de trabajo para esta cotización');
    }
    
    // Generar número de orden único
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM ordenes_trabajo WHERE YEAR(created_at) = YEAR(CURDATE())");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'] + 1;
    $numero_orden = 'OT-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    
    error_log("[v0] Generated order number: $numero_orden");
    
    $stmt = $pdo->prepare("
        INSERT INTO ordenes_trabajo 
        (numero_orden, cotizacion_id, cliente_id, fecha_inicio, estado, porcentaje_avance, observaciones, created_at) 
        VALUES (?, ?, ?, CURDATE(), 'pendiente', 0, 'Orden creada automáticamente desde cotización aceptada', NOW())
    ");
    $result = $stmt->execute([$numero_orden, $cotizacion_id, $cotizacion['cliente_id']]);
    
    if (!$result) {
        error_log("[v0] Failed to insert order: " . json_encode($stmt->errorInfo()));
        throw new Exception('Error al crear la orden de trabajo');
    }
    
    $orden_id = $pdo->lastInsertId();
    error_log("[v0] Order created successfully: ID=$orden_id, Number=$numero_orden");
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Orden de trabajo creada exitosamente',
        'orden_id' => $orden_id,
        'numero_orden' => $numero_orden
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("[v0] Error creating order: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
