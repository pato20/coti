<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

verificarAuth();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Petición inválida.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $cotizacion_id = $data['cotizacion_id'] ?? null;

    if (empty($cotizacion_id)) {
        $response['message'] = 'ID de cotización no proporcionado.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Verificar que la cotización existe y está en estado 'rechazada'
        $stmt = $pdo->prepare("SELECT estado FROM cotizaciones WHERE id = ?");
        $stmt->execute([$cotizacion_id]);
        $cotizacion = $stmt->fetch();

        if (!$cotizacion) {
            throw new Exception("La cotización no existe.");
        }

        if ($cotizacion['estado'] !== 'rechazada') {
            throw new Exception("Solo se pueden eliminar cotizaciones en estado 'Rechazada'.");
        }

        // 2. Eliminar de cerco_electrico_config
        $stmt = $pdo->prepare("DELETE FROM cerco_electrico_config WHERE cotizacion_id = ?");
        $stmt->execute([$cotizacion_id]);

        // 3. Eliminar de cotizacion_detalles
        $stmt = $pdo->prepare("DELETE FROM cotizacion_detalles WHERE cotizacion_id = ?");
        $stmt->execute([$cotizacion_id]);

        // 4. Eliminar de cotizaciones
        $stmt = $pdo->prepare("DELETE FROM cotizaciones WHERE id = ?");
        $stmt->execute([$cotizacion_id]);

        $pdo->commit();

        $response['success'] = true;
        $response['message'] = 'Cotización eliminada exitosamente.';

    } catch (Exception $e) {
        $pdo->rollBack();
        $response['message'] = 'Error al eliminar la cotización: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>