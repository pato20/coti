<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/auth.php';

verificarRol(['admin', 'vendedor'], true);

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$orden_id = $input['orden_id'] ?? null;
$monto = $input['monto'] ?? 0;
$fecha_pago = $input['fecha_pago'] ?? date('Y-m-d');
$metodo_pago = $input['metodo_pago'] ?? '';
$observaciones = $input['observaciones'] ?? '';

if (!$orden_id || !is_numeric($orden_id) || !is_numeric($monto) || $monto <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos de pago inválidos.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Insertar el nuevo pago en la tabla de pagos
    $stmt = $pdo->prepare("INSERT INTO pagos (orden_id, monto, fecha_pago, metodo_pago, observaciones) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$orden_id, $monto, $fecha_pago, $metodo_pago, $observaciones]);

    // 2. Actualizar los montos y el estado en la orden de trabajo
    $stmt = $pdo->prepare("SELECT monto_total, monto_pagado FROM ordenes_trabajo WHERE id = ?");
    $stmt->execute([$orden_id]);
    $orden = $stmt->fetch();

    $nuevo_monto_pagado = $orden['monto_pagado'] + $monto;
    $estado_pago = 'abonado';
    if ($nuevo_monto_pagado >= $orden['monto_total']) {
        $estado_pago = 'pagado';
    }

    $stmt = $pdo->prepare("UPDATE ordenes_trabajo SET monto_pagado = ?, estado_pago = ? WHERE id = ?");
    $stmt->execute([$nuevo_monto_pagado, $estado_pago, $orden_id]);

    $pdo->commit();

    // Intentar enviar email de notificación ANTES de la respuesta JSON
    try {
        require_once ROOT_PATH . '/includes/email.php';
        
        // Re-obtener la información actualizada de la orden para el email
        $stmt = $pdo->prepare("SELECT ot.numero_orden, ot.monto_total, ot.monto_pagado, ot.estado_pago, cl.nombre as cliente_nombre, cl.email as cliente_email FROM ordenes_trabajo ot JOIN clientes cl ON ot.cliente_id = cl.id WHERE ot.id = ?");
        $stmt->execute([$orden_id]);
        $orden_info = $stmt->fetch();

        if ($orden_info && !empty($orden_info['cliente_email'])) {
            $email_data = [
                'numero_orden' => $orden_info['numero_orden'],
                'cliente_nombre' => $orden_info['cliente_nombre'],
                'monto_pago' => $monto,
                'total_pagado' => $nuevo_monto_pagado, // Usar la variable ya calculada
                'saldo_pendiente' => $orden_info['monto_total'] - $nuevo_monto_pagado,
                'estado_pago' => $estado_pago // Usar la variable ya calculada
            ];
            $asunto = '¡Pago Registrado para su Orden #' . $orden_info['numero_orden'] . '!';
            $mensaje = generarPlantillaEmail('pago_registrado', $email_data);
            enviarEmail($orden_info['cliente_email'], $asunto, $mensaje);
        }
    } catch (Exception $email_error) {
        // Si el email falla, no detener el proceso. Registrar el error.
        error_log("Error al enviar email de confirmación de pago: " . $email_error->getMessage());
    }

    echo json_encode(['success' => true, 'message' => 'Pago registrado exitosamente.']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>