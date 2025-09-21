<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cotizacion_id = $input['cotizacion_id'] ?? null;
$estado = $input['estado'] ?? null;

if (!$cotizacion_id || !$estado) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$estados_validos = ['pendiente', 'enviada', 'aceptada', 'rechazada', 'vencida'];
if (!in_array($estado, $estados_validos)) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE cotizaciones SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $cotizacion_id]);
    
    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar estado: ' . $e->getMessage()]);
}
?>
