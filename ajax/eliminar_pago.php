<?php
session_start();

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/auth.php';

verificarAuth();
// Permitir a admin y vendedor eliminar pagos, o ajustar según sea necesario
verificarRol(['admin', 'vendedor'], true);

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$pago_id = $input['pago_id'] ?? null;
$orden_id = $input['orden_id'] ?? null;
$monto = $input['monto'] ?? 0;

if (!$pago_id || !$orden_id || !is_numeric($monto)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Eliminar el pago de la tabla de pagos
    $stmt = $pdo->prepare("DELETE FROM pagos WHERE id = ?");
    $stmt->execute([$pago_id]);

    // 2. Actualizar el monto pagado en la orden de trabajo
    $stmt = $pdo->prepare("UPDATE ordenes_trabajo SET monto_pagado = monto_pagado - ? WHERE id = ?");
    $stmt->execute([$monto, $orden_id]);

    // 3. Obtener datos actualizados de la orden para recalcular el estado de pago
    $stmt = $pdo->prepare("SELECT monto_total, monto_pagado FROM ordenes_trabajo WHERE id = ?");
    $stmt->execute([$orden_id]);
    $orden = $stmt->fetch();

    $nuevo_estado_pago = 'pendiente';
    if ($orden['monto_pagado'] >= $orden['monto_total']) {
        $nuevo_estado_pago = 'pagado';
    } elseif ($orden['monto_pagado'] > 0) {
        $nuevo_estado_pago = 'abonado';
    }

    // 4. Actualizar el estado de pago en la orden de trabajo
    $stmt = $pdo->prepare("UPDATE ordenes_trabajo SET estado_pago = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado_pago, $orden_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Pago eliminado y saldo de la orden actualizado exitosamente.']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el pago: ' . $e->getMessage()]);
}
?>