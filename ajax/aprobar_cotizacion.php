<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';

// Usar el nuevo verificador de rol para AJAX
verificarRol(['admin', 'vendedor'], true);

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$cotizacion_id = $input['cotizacion_id'] ?? null;

if (!$cotizacion_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Error: No se proporcionó el ID de la cotización.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT estado, cliente_id, requiere_mantencion, total FROM cotizaciones WHERE id = ?");
    $stmt->execute([$cotizacion_id]);
    $cotizacion = $stmt->fetch();

    if (!$cotizacion) {
        throw new Exception('La cotización no existe.');
    }
    if ($cotizacion['estado'] == 'aceptada') {
        throw new Exception('Esta cotización ya ha sido aceptada previamente.');
    }

    $stmt = $pdo->prepare("SELECT id FROM ordenes_trabajo WHERE cotizacion_id = ?");
    $stmt->execute([$cotizacion_id]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe una orden de trabajo para esta cotización.');
    }

    $update_stmt = $pdo->prepare("UPDATE cotizaciones SET estado = 'aceptada' WHERE id = ?");
    $update_stmt->execute([$cotizacion_id]);

    $numero_orden = generateWorkOrderNumber();
    $cliente_id = $cotizacion['cliente_id'];
    $requiere_mantencion = $cotizacion['requiere_mantencion'];
    $monto_total = $cotizacion['total'];
    $fecha_inicio = date('Y-m-d');
    $estado_inicial = 'pendiente';

    $insert_stmt = $pdo->prepare(
        "INSERT INTO ordenes_trabajo (numero_orden, cotizacion_id, cliente_id, fecha_inicio, estado, requiere_mantencion, monto_total) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $insert_stmt->execute([$numero_orden, $cotizacion_id, $cliente_id, $fecha_inicio, $estado_inicial, $requiere_mantencion, $monto_total]);
    $orden_id = $pdo->lastInsertId();

    // --- Lógica para descontar stock ---
    $stmt_detalles = $pdo->prepare("SELECT producto_servicio_id, cantidad FROM cotizacion_detalles WHERE cotizacion_id = ? AND producto_servicio_id IS NOT NULL");
    $stmt_detalles->execute([$cotizacion_id]);
    $detalles_cotizacion = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

    foreach ($detalles_cotizacion as $detalle) {
        $productId = $detalle['producto_servicio_id'];
        $cantidad = $detalle['cantidad'];

        // Verificar si es un producto (no un servicio) antes de intentar descontar stock
        $productType = getProductType($productId);
        if ($productType === 'producto') {
            // Descontar stock
            $stockUpdated = updateProductStock($productId, -$cantidad); // Restar la cantidad
            if (!$stockUpdated) {
                // Si la actualización de stock falla, puedes decidir si revertir la transacción
                // o simplemente loguear un error. Por ahora, lanzaré una excepción.
                throw new Exception("No se pudo actualizar el stock para el producto ID: {$productId}.");
            }
        }
    }
    // --- Fin lógica para descontar stock ---

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Cotización aprobada y Orden de Trabajo #' . $numero_orden . ' creada exitosamente.',
        'orden_id' => $orden_id
    ]);

    // Enviar email al cliente
    require_once ROOT_PATH . '/includes/email.php';
    $cliente_email = $cotizacion['cliente_email'] ?? null; // Asumiendo que cliente_email está en la cotización
    if ($cliente_email) {
        $email_data = [
            'numero' => $cotizacion['numero_cotizacion'],
            'cliente_nombre' => $cotizacion['cliente_nombre'] ?? 'Cliente',
            'estado' => 'aceptada',
            'numero_orden' => $numero_orden,
            'fecha_inicio' => $fecha_inicio,
            'progreso' => 0
        ];
        $asunto = '¡Cotización Aprobada y Orden de Trabajo Creada!';
        $mensaje = generarPlantillaEmail('orden_trabajo', $email_data);
        enviarEmail($cliente_email, $asunto, $mensaje);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>