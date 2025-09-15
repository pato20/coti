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
    $nueva_fecha = $data['fecha_hora_inicio'] ?? null;
    $motivo = $data['motivo'] ?? '';

    if (empty($evento_id) || empty($nueva_fecha) || empty($motivo)) {
        $response['message'] = 'Faltan datos requeridos (ID, nueva fecha, motivo).';
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

        // 2. Actualizar la fecha y el estado (si estaba cancelada) de la visita
        $stmt = $pdo->prepare("UPDATE agenda SET fecha_hora_inicio = ?, estado = 'pendiente' WHERE id = ?");
        $stmt->execute([$nueva_fecha, $evento_id]);

        // 3. Registrar el cambio en el log
        $stmt = $pdo->prepare(
            "INSERT INTO agenda_log (evento_id, usuario_id, tipo_cambio, motivo, datos_anteriores) VALUES (?, ?, ?, ?, ?)"
        );
        $datos_anteriores_json = json_encode(['fecha_hora_inicio' => $evento_antiguo['fecha_hora_inicio']]);
        $stmt->execute([$evento_id, $user_id, 'reagendado', $motivo, $datos_anteriores_json]);

        $pdo->commit();

        $response['success'] = true;
        $response['message'] = 'Visita reagendada exitosamente.';

    } catch (Exception $e) {
        $pdo->rollBack();
        $response['message'] = 'Error al reagendar: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>