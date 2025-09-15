<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php'; // Añadir esta línea

verificarAuth();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Petición inválida.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $orden_id = $data['orden_id'] ?? null;

    if (empty($orden_id)) {
        $response['message'] = 'ID de orden no proporcionado.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT estado, cotizacion_id FROM ordenes_trabajo WHERE id = ?");
        $stmt->execute([$orden_id]);
        $orden = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch como array asociativo para acceder a cotizacion_id

        if (!$orden) {
            throw new Exception("La orden de trabajo no existe.");
        }

        if ($orden['estado'] !== 'cancelada') {
            throw new Exception("Solo se pueden eliminar órdenes en estado 'Cancelada'.");
        }

        $cotizacion_id = $orden['cotizacion_id'];

        // --- Lógica para reponer stock antes de eliminar la orden ---
        if ($cotizacion_id) {
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
                        throw new Exception("No se pudo reponer el stock para el producto ID: {$productId} al eliminar la orden.");
                    }
                }
            }
        }
        // --- Fin lógica para reponer stock ---

        // 2. Eliminar de la agenda (mantenciones)
        $stmt = $pdo->prepare("DELETE FROM agenda WHERE orden_id = ?");
        $stmt->execute([$orden_id]);

        // 3. Eliminar seguimientos
        $stmt = $pdo->prepare("DELETE FROM orden_seguimiento WHERE orden_id = ?");
        $stmt->execute([$orden_id]);

        // 4. Eliminar pagos
        $stmt = $pdo->prepare("DELETE FROM pagos WHERE orden_id = ?");
        $stmt->execute([$orden_id]);

        // 5. Eliminar la orden principal
        $stmt = $pdo->prepare("DELETE FROM ordenes_trabajo WHERE id = ?");
        $stmt->execute([$orden_id]);

        $pdo->commit();

        $response['success'] = true;
        $response['message'] = 'Orden de trabajo eliminada exitosamente.';

    } catch (Exception $e) {
        $pdo->rollBack();
        // Devolver el mensaje de error completo para depuración
        $response['message'] = 'Error al eliminar la orden: ' . $e->getMessage() . ' (Código: ' . $e->getCode() . ')';
        // Opcional: loguear el error en el servidor para producción
        error_log('Error al eliminar orden: ' . $e->getMessage() . ' en ' . $e->getFile() . ' línea ' . $e->getLine());
    }
}

echo json_encode($response);
?>