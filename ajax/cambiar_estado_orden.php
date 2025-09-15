<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/auth.php';

verificarRol(['admin', 'vendedor'], true);

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$orden_id = $input['orden_id'] ?? null;
$estado = $input['estado'] ?? null;

if (!$orden_id || !$estado) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos para cambiar el estado de la orden.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Obtener el estado actual de la orden antes de actualizar
    $stmt_old_state = $pdo->prepare("SELECT estado, cotizacion_id FROM ordenes_trabajo WHERE id = ?");
    $stmt_old_state->execute([$orden_id]);
    $old_order_info = $stmt_old_state->fetch(PDO::FETCH_ASSOC);
    $old_estado = $old_order_info['estado'] ?? null;
    $cotizacion_id = $old_order_info['cotizacion_id'] ?? null;

    // Si el estado anterior no era 'cancelada' y el nuevo estado es 'cancelada', reponer stock
    if ($old_estado !== 'cancelada' && $estado === 'cancelada' && $cotizacion_id) {
        $stmt_detalles = $pdo->prepare("SELECT producto_servicio_id, cantidad FROM cotizacion_detalles WHERE cotizacion_id = ? AND producto_servicio_id IS NOT NULL");
        $stmt_detalles->execute([$cotizacion_id]);
        $detalles_cotizacion = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

        foreach ($detalles_cotizacion as $detalle) {
            $productId = $detalle['producto_servicio_id'];
            $cantidad = $detalle['cantidad'];

            $productType = getProductType($productId);
            if ($productType === 'producto') {
                // Reponer stock
                $stockUpdated = updateProductStock($productId, $cantidad); // Sumar la cantidad
                if (!$stockUpdated) {
                    throw new Exception("No se pudo reponer el stock para el producto ID: {$productId}.");
                }
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE ordenes_trabajo SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $orden_id]);

    $pdo->commit(); // Confirmar la transacción

    echo json_encode(['success' => true, 'message' => 'Estado de la orden actualizado exitosamente.']);

    // Enviar email al cliente
    require_once ROOT_PATH . '/includes/email.php';
    $stmt = $pdo->prepare("SELECT ot.numero_orden, ot.porcentaje_avance, ot.estado, cl.nombre as cliente_nombre, cl.email as cliente_email FROM ordenes_trabajo ot JOIN clientes cl ON ot.cliente_id = cl.id WHERE ot.id = ?");
    $stmt->execute([$orden_id]);
    $orden_info = $stmt->fetch();

    if ($orden_info && $orden_info['cliente_email']) {
        $email_data = [
            'numero' => $orden_info['numero_orden'],
            'cliente_nombre' => $orden_info['cliente_nombre'],
            'estado' => $orden_info['estado'],
            'progreso' => $orden_info['porcentaje_avance'],
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
            'comentarios' => 'El estado de su orden ha sido actualizado.'
        ];
        $asunto = 'Actualización de Estado para su Orden #' . $orden_info['numero_orden'];
        $mensaje = generarPlantillaEmail('actualizacion_orden', $email_data);
        enviarEmail($orden_info['cliente_email'], $asunto, $mensaje);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado de la orden: ' . $e->getMessage()]);
}
?>