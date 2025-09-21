<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// --- Verificación de Autenticación ---
try {
    $authHeader = $_SERVER['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $authHeader = $requestHeaders['Authorization'];
        }
    }

    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(["message" => "Acceso denegado. Token no proporcionado."]);
        exit();
    }

    list($jwt) = sscanf($authHeader, 'Bearer %s');

    if (!$jwt) {
        http_response_code(401);
        echo json_encode(["message" => "Acceso denegado. Formato de token inválido."]);
        exit();
    }

    $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));
    $userData = (array) $decoded->data;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["message" => "Acceso denegado. Token inválido.", "error" => $e->getMessage()]);
    exit();
}
// --- Fin de Verificación de Autenticación ---

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? explode('/', rtrim($_GET['path'], '/')) : [];

$clienteId = isset($path[2]) && is_numeric($path[2]) ? intval($path[2]) : null;

try {
    switch ($method) {
        case 'GET':
            if ($clienteId) {
                $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
                $stmt->bindParam(':id', $clienteId);
                $stmt->execute();
                $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($cliente) {
                    http_response_code(200);
                    echo json_encode($cliente);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Cliente no encontrado."]);
                }
            } else {
                $stmt = $pdo->query("SELECT * FROM clientes ORDER BY nombre");
                $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                http_response_code(200);
                echo json_encode($clientes);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));

            if (empty($data->nombre)) {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos. El nombre es requerido."]);
                break;
            }

            $sql = "INSERT INTO clientes (nombre, rut, email, telefono, direccion) VALUES (:nombre, :rut, :email, :telefono, :direccion)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':nombre', $data->nombre);
            $stmt->bindParam(':rut', $data->rut);
            $stmt->bindParam(':email', $data->email);
            $stmt->bindParam(':telefono', $data->telefono);
            $stmt->bindParam(':direccion', $data->direccion);

            if ($stmt->execute()) {
                $lastId = $pdo->lastInsertId();
                http_response_code(201);
                echo json_encode(["message" => "Cliente creado exitosamente.", "id" => $lastId]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo crear el cliente."]);
            }
            break;

        case 'PUT':
            if (!$clienteId) {
                http_response_code(400);
                echo json_encode(["message" => "ID de cliente no proporcionado."]);
                break;
            }

            $data = json_decode(file_get_contents("php://input"));

            $sql = "UPDATE clientes SET nombre = :nombre, rut = :rut, email = :email, telefono = :telefono, direccion = :direccion WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':id', $clienteId);
            $stmt->bindParam(':nombre', $data->nombre);
            $stmt->bindParam(':rut', $data->rut);
            $stmt->bindParam(':email', $data->email);
            $stmt->bindParam(':telefono', $data->telefono);
            $stmt->bindParam(':direccion', $data->direccion);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "Cliente actualizado exitosamente."]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "No se encontró el cliente o no hubo cambios."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo actualizar el cliente."]);
            }
            break;

        case 'DELETE':
            if (!$clienteId) {
                http_response_code(400);
                echo json_encode(["message" => "ID de cliente no proporcionado."]);
                break;
            }

            $sql = "DELETE FROM clientes WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $clienteId);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "Cliente eliminado exitosamente."]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Cliente no encontrado."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo eliminar el cliente."]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(["message" => "Método no permitido."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error en el servidor.", "error" => $e->getMessage()]);
}
?>