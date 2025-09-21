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
    $authHeader = null;
    if (isset($_SERVER['Authorization'])) {
        $authHeader = $_SERVER['Authorization'];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx o FastCGI
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
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

// El ID del producto, si se proporciona en la URL (ej: /api/productos/123)
$productId = isset($path[2]) && is_numeric($path[2]) ? intval($path[2]) : null;


try {
    switch ($method) {
        case 'GET':
            if ($productId) {
                // Obtener un solo producto por ID
                $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = :id");
                $stmt->bindParam(':id', $productId);
                $stmt->execute();
                $producto = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($producto) {
                    http_response_code(200);
                    echo json_encode($producto);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Producto no encontrado."]);
                }
            } else {
                // Obtener todos los productos
                $stmt = $pdo->query("SELECT * FROM productos ORDER BY nombre");
                $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                http_response_code(200);
                echo json_encode($productos);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));

            if (empty($data->nombre) || !isset($data->precio) || empty($data->unidad)) {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos. Nombre, precio y unidad son requeridos."]);
                break;
            }

            $sql = "INSERT INTO productos (nombre, descripcion, precio, unidad, categoria_id, proveedor, codigo_proveedor) VALUES (:nombre, :descripcion, :precio, :unidad, :categoria_id, :proveedor, :codigo_proveedor)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':nombre', $data->nombre);
            $stmt->bindParam(':descripcion', $data->descripcion);
            $stmt->bindParam(':precio', $data->precio);
            $stmt->bindParam(':unidad', $data->unidad);
            $stmt->bindParam(':categoria_id', $data->categoria_id);
            $stmt->bindParam(':proveedor', $data->proveedor);
            $stmt->bindParam(':codigo_proveedor', $data->codigo_proveedor);

            if ($stmt->execute()) {
                $lastId = $pdo->lastInsertId();
                http_response_code(201);
                echo json_encode(["message" => "Producto creado exitosamente.", "id" => $lastId]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo crear el producto."]);
            }
            break;

        case 'PUT':
            if (!$productId) {
                http_response_code(400);
                echo json_encode(["message" => "ID de producto no proporcionado."]);
                break;
            }

            $data = json_decode(file_get_contents("php://input"));

            $sql = "UPDATE productos SET nombre = :nombre, descripcion = :descripcion, precio = :precio, unidad = :unidad, categoria_id = :categoria_id, proveedor = :proveedor, codigo_proveedor = :codigo_proveedor WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':id', $productId);
            $stmt->bindParam(':nombre', $data->nombre);
            $stmt->bindParam(':descripcion', $data->descripcion);
            $stmt->bindParam(':precio', $data->precio);
            $stmt->bindParam(':unidad', $data->unidad);
            $stmt->bindParam(':categoria_id', $data->categoria_id);
            $stmt->bindParam(':proveedor', $data->proveedor);
            $stmt->bindParam(':codigo_proveedor', $data->codigo_proveedor);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "Producto actualizado exitosamente."]);
                } else {
                    http_response_code(404); // O 200 si la operación es idempotente pero no cambió nada
                    echo json_encode(["message" => "No se encontró el producto o no hubo cambios."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo actualizar el producto."]);
            }
            break;

        case 'DELETE':
            if (!$productId) {
                http_response_code(400);
                echo json_encode(["message" => "ID de producto no proporcionado."]);
                break;
            }

            $sql = "DELETE FROM productos WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $productId);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "Producto eliminado exitosamente."]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Producto no encontrado."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo eliminar el producto."]);
            }
            break;

        default:
            http_response_code(405); // Method Not Allowed
            echo json_encode(["message" => "Método no permitido."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error en el servidor.", "error" => $e->getMessage()]);
}
?>