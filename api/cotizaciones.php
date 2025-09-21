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

$cotizacionId = isset($path[2]) && is_numeric($path[2]) ? intval($path[2]) : null;

try {
    switch ($method) {
        case 'GET':
            if ($cotizacionId) {
                $stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = :id");
                $stmt->bindParam(':id', $cotizacionId);
                $stmt->execute();
                $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($cotizacion) {
                    http_response_code(200);
                    echo json_encode($cotizacion);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Cotización no encontrada."]);
                }
            } else {
                $stmt = $pdo->query("SELECT * FROM cotizaciones ORDER BY fecha_cotizacion DESC");
                $cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                http_response_code(200);
                echo json_encode($cotizaciones);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));

            if (empty($data->cliente_id) || empty($data->fecha_cotizacion)) {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos. cliente_id y fecha_cotizacion son requeridos."]);
                break;
            }
            
            // Generar un número de cotización único
            $numero_cotizacion = "COT-" . date("Y") . "-" . rand(1000, 9999);

            $sql = "INSERT INTO cotizaciones (numero_cotizacion, cliente_id, fecha_cotizacion, fecha_vencimiento, subtotal, iva, total, estado, observaciones, requiere_mantencion, con_iva, usuario_id, descuento_general) VALUES (:numero_cotizacion, :cliente_id, :fecha_cotizacion, :fecha_vencimiento, :subtotal, :iva, :total, :estado, :observaciones, :requiere_mantencion, :con_iva, :usuario_id, :descuento_general)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':numero_cotizacion', $numero_cotizacion);
            $stmt->bindParam(':cliente_id', $data->cliente_id);
            $stmt->bindParam(':fecha_cotizacion', $data->fecha_cotizacion);
            $stmt->bindParam(':fecha_vencimiento', $data->fecha_vencimiento);
            $stmt->bindParam(':subtotal', $data->subtotal);
            $stmt->bindParam(':iva', $data->iva);
            $stmt->bindParam(':total', $data->total);
            $stmt->bindParam(':estado', $data->estado);
            $stmt->bindParam(':observaciones', $data->observaciones);
            $stmt->bindParam(':requiere_mantencion', $data->requiere_mantencion, PDO::PARAM_INT);
            $stmt->bindParam(':con_iva', $data->con_iva, PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $userData['id']); // Asignar el ID del usuario autenticado
            $stmt->bindParam(':descuento_general', $data->descuento_general);

            if ($stmt->execute()) {
                $lastId = $pdo->lastInsertId();
                http_response_code(201);
                echo json_encode(["message" => "Cotización creada exitosamente.", "id" => $lastId, "numero_cotizacion" => $numero_cotizacion]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo crear la cotización."]);
            }
            break;

        case 'PUT':
            if (!$cotizacionId) {
                http_response_code(400);
                echo json_encode(["message" => "ID de cotización no proporcionado."]);
                break;
            }

            $data = json_decode(file_get_contents("php://input"));

            $sql = "UPDATE cotizaciones SET cliente_id = :cliente_id, fecha_cotizacion = :fecha_cotizacion, fecha_vencimiento = :fecha_vencimiento, subtotal = :subtotal, iva = :iva, total = :total, estado = :estado, observaciones = :observaciones, requiere_mantencion = :requiere_mantencion, con_iva = :con_iva, descuento_general = :descuento_general WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':id', $cotizacionId);
            $stmt->bindParam(':cliente_id', $data->cliente_id);
            $stmt->bindParam(':fecha_cotizacion', $data->fecha_cotizacion);
            $stmt->bindParam(':fecha_vencimiento', $data->fecha_vencimiento);
            $stmt->bindParam(':subtotal', $data->subtotal);
            $stmt->bindParam(':iva', $data->iva);
            $stmt->bindParam(':total', $data->total);
            $stmt->bindParam(':estado', $data->estado);
            $stmt->bindParam(':observaciones', $data->observaciones);
            $stmt->bindParam(':requiere_mantencion', $data->requiere_mantencion, PDO::PARAM_INT);
            $stmt->bindParam(':con_iva', $data->con_iva, PDO::PARAM_INT);
            $stmt->bindParam(':descuento_general', $data->descuento_general);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "Cotización actualizada exitosamente."]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "No se encontró la cotización o no hubo cambios."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo actualizar la cotización."]);
            }
            break;

        case 'DELETE':
            if (!$cotizacionId) {
                http_response_code(400);
                echo json_encode(["message" => "ID de cotización no proporcionado."]);
                break;
            }

            $sql = "DELETE FROM cotizaciones WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $cotizacionId);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "Cotización eliminada exitosamente."]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Cotización no encontrada."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo eliminar la cotización."]);
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