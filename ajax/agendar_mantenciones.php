<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/auth.php';

verificarRol(['admin', 'vendedor'], true);

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$orden_id = $input['orden_id'] ?? null;

if (!$orden_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de orden no proporcionado.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM ordenes_trabajo WHERE id = ? AND requiere_mantencion = 1");
    $stmt->execute([$orden_id]);
    $orden = $stmt->fetch();

    if (!$orden) {
        throw new Exception('Orden no encontrada o no requiere mantención.');
    }

    $pdo->beginTransaction();

    $fecha_base = new DateTime(); // Hoy
    $intervalos = [3, 6, 9, 12]; // Meses para las mantenciones trimestrales

    foreach ($intervalos as $mes) {
        $fecha_mantencion = (clone $fecha_base)->add(new DateInterval("P{$mes}M"));
        
        $stmt = $pdo->prepare(
            "INSERT INTO agenda (usuario_id, cliente_id, orden_id, titulo, tipo, fecha_hora_inicio, estado) VALUES (?, ?, ?, ?, 'mantencion', ?, 'pendiente')"
        );
        $stmt->execute([
            $_SESSION['usuario_id'], // Usar el ID del usuario logueado
            $orden['cliente_id'],
            $orden_id,
            'Mantención Trimestral (Orden #' . $orden['numero_orden'] . ')',
            $fecha_mantencion->format('Y-m-d H:i:s')
        ]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => '4 visitas de mantención han sido agendadas para el próximo año.']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>