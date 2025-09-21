<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

verificarAuth();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Acción no válida.'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, nombre, tipo FROM categorias ORDER BY nombre");
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = ['success' => true, 'categorias' => $categorias];
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        if (!isset($data['nombre']) && $action !== 'delete') {
            throw new Exception('El nombre de la categoría es requerido.');
        }

        switch ($action) {
            case 'create':
                $category_type = 'producto'; // Valor por defecto
                $stmt = $pdo->prepare("INSERT INTO categorias (nombre, tipo) VALUES (?, ?)");
                $stmt->execute([$data['nombre'], $category_type]);
                $response = ['success' => true, 'message' => 'Categoría creada exitosamente.'];
                break;

            case 'update':
                if (!isset($data['id'])) {
                    throw new Exception('ID de categoría es requerido para actualizar.');
                }
                $stmt = $pdo->prepare("UPDATE categorias SET nombre = ? WHERE id = ?"); // Solo actualizar nombre
                $stmt->execute([$data['nombre'], $data['id']]);
                $response = ['success' => true, 'message' => 'Categoría actualizada exitosamente.'];
                break;

            case 'delete':
                if (!isset($data['id'])) {
                    throw new Exception('ID de categoría es requerido para eliminar.');
                }
                // Verificar si la categoría está en uso antes de eliminar
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM productos_servicios WHERE categoria_id = ?");
                $stmt_check->execute([$data['id']]);
                if ($stmt_check->fetchColumn() > 0) {
                    throw new Exception('No se puede eliminar la categoría porque está siendo utilizada por uno o más productos/servicios.');
                }

                $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
                $stmt->execute([$data['id']]);
                $response = ['success' => true, 'message' => 'Categoría eliminada exitosamente.'];
                break;

            default:
                throw new Exception('Acción POST no reconocida.');
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    // Log the error for debugging purposes
    error_log('Category API Error: ' . $e->getMessage());
}

echo json_encode($response);
?>