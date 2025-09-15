<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

verificarAuth();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Petición inválida.'];
$user_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $evento_id = $data['id'] ?? null;
    $motivo = $data['motivo'] ?? '';

    if (empty($evento_id) || empty($motivo)) {
        $response['message'] = 'Faltan datos requeridos (ID, motivo).';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Obtener datos antiguos para el log
        $stmt = $pdo->prepare("SELECT * FROM agenda WHERE id = ?");
        $stmt->execute([$evento_id]);
        $evento_antiguo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$evento_antiguo) {
            throw new Exception("La visita no existe.");
        }

        // 2. Actualizar el estado de la visita a 'cancelada'
        $stmt = $pdo->prepare("UPDATE agenda SET estado = 'cancelada' WHERE id = ?");
        $stmt->execute([$evento_id]);

        // 3. Registrar el cambio en el log
        $stmt = $pdo->prepare(
            "INSERT INTO agenda_log (evento_id, usuario_id, tipo_cambio, motivo, datos_anteriores) VALUES (?, ?, ?, ?, ?)"
        );
        $datos_anteriores_json = json_encode(['estado' => $evento_antiguo['estado']]);
        $stmt->execute([$evento_id, $user_id, 'cancelado', $motivo, $datos_anteriores_json]);

        $pdo->commit();

        $response['success'] = true;
        $response['message'] = 'Visita cancelada exitosamente.';

    } catch (Exception $e) {
        $pdo->rollBack();
        $response['message'] = 'Error al cancelar: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>