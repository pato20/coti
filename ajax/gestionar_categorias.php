<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

verificarAuth();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Acción no válida.'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre");
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = ['success' => true, 'categorias' => $categorias];
    } catch (Exception $e) {
        $response['message'] = 'Error al obtener categorías: ' . $e->getMessage();
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    try {
        switch ($action) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO categorias (nombre, tipo) VALUES (?, ?)");
                $stmt->execute([$data['nombre'], $data['tipo']]);
                $response = ['success' => true, 'message' => 'Categoría creada.'];
                break;

            case 'update':
                $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, tipo = ? WHERE id = ?");
                $stmt->execute([$data['nombre'], $data['tipo'], $data['id']]);
                $response = ['success' => true, 'message' => 'Categoría actualizada.'];
                break;

            case 'delete':
                // Opcional: Verificar si la categoría está en uso antes de eliminar
                $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
                $stmt->execute([$data['id']]);
                $response = ['success' => true, 'message' => 'Categoría eliminada.'];
                break;
            
            default:
                $response['message'] = 'Acción POST no reconocida.';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Error en la operación: ' . $e->getMessage();
        // Código de error para violación de clave foránea (categoría en uso)
        if ($e->getCode() == '23000') {
            $response['message'] = 'Error: No se puede eliminar la categoría porque está siendo utilizada por uno o más productos/servicios.';
        }
    }
}

echo json_encode($response);
?>