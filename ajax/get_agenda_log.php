<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

verificarAuth();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Petición inválida.', 'log' => []];
$evento_id = $_GET['evento_id'] ?? null;

if (empty($evento_id)) {
    $response['message'] = 'ID de evento no proporcionado.';
    echo json_encode($response);
    exit;
}

try {
    $query = "SELECT al.*, u.nombre_completo as usuario_nombre 
              FROM agenda_log al
              JOIN usuarios u ON al.usuario_id = u.id
              WHERE al.evento_id = ? 
              ORDER BY al.fecha_cambio DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$evento_id]);
    $log_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['log'] = $log_entries;

} catch (Exception $e) {
    $response['message'] = 'Error al obtener el historial: ' . $e->getMessage();
}

echo json_encode($response);
?>