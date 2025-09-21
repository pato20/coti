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

$ordenId = isset($path[2]) && is_numeric($path[2]) ? intval($path[2]) : null;

try {
    switch ($method) {
        case 'GET':
            if ($ordenId) {
                $stmt = $pdo->prepare("SELECT * FROM ordenes_trabajo WHERE id = :id");
                $stmt->bindParam(':id', $ordenId);
                $stmt->execute();
                $orden = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($orden) {
                    http_response_code(200);
                    echo json_encode($orden);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Orden de trabajo no encontrada."]);
                }
            } else {
                $stmt = $pdo->query("SELECT * FROM ordenes_trabajo ORDER BY fecha_inicio DESC");
                $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                http_response_code(200);
                echo json_encode($ordenes);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));

            if (empty($data->cliente_id) || empty($data->fecha_inicio) || !isset($data->monto_total)) {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos. cliente_id, fecha_inicio y monto_total son requeridos."]);
                break;
            }
            
            $numero_orden = "OT-" . date("Y") . "-" . rand(1000, 9999);

            $sql = "INSERT INTO ordenes_trabajo (numero_orden, cotizacion_id, cliente_id, fecha_inicio, fecha_estimada_fin, estado, porcentaje_avance, monto_total, tecnico_id, observaciones) VALUES (:numero_orden, :cotizacion_id, :cliente_id, :fecha_inicio, :fecha_estimada_fin, :estado, :porcentaje_avance, :monto_total, :tecnico_id, :observaciones)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':numero_orden', $numero_orden);
            $stmt->bindParam(':cotizacion_id', $data->cotizacion_id);
            $stmt->bindParam(':cliente_id', $data->cliente_id);
            $stmt->bindParam(':fecha_inicio', $data->fecha_inicio);
            $stmt->bindParam(':fecha_estimada_fin', $data->fecha_estimada_fin);
            $stmt->bindValue(':estado', $data->estado ?? 'pendiente');
            $stmt->bindValue(':porcentaje_avance', $data->porcentaje_avance ?? 0);
            $stmt->bindParam(':monto_total', $data->monto_total);
            $stmt->bindParam(':tecnico_id', $data->tecnico_id);
            $stmt->bindParam(':observaciones', $data->observaciones);

            if ($stmt->execute()) {
                $lastId = $pdo->lastInsertId();
                http_response_code(201);
                echo json_encode(["message" => "Orden de trabajo creada exitosamente.", "id" => $lastId, "numero_orden" => $numero_orden]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo crear la orden de trabajo."]);
            }
            break;

        case 'PUT':
            if (!$ordenId) {
                http_response_code(400);
                echo json_encode(["message" => "ID de orden no proporcionado."]);
                break;
            }

            $data = json_decode(file_get_contents("php://input"));

            $sql = "UPDATE ordenes_trabajo SET cotizacion_id = :cotizacion_id, cliente_id = :cliente_id, fecha_inicio = :fecha_inicio, fecha_estimada_fin = :fecha_estimada_fin, fecha_real_fin = :fecha_real_fin, estado = :estado, porcentaje_avance = :porcentaje_avance, monto_total = :monto_total, monto_pagado = :monto_pagado, estado_pago = :estado_pago, tecnico_id = :tecnico_id, observaciones = :observaciones WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':id', $ordenId);
            $stmt->bindParam(':cotizacion_id', $data->cotizacion_id);
            $stmt->bindParam(':cliente_id', $data->cliente_id);
            $stmt->bindParam(':fecha_inicio', $data->fecha_inicio);
            $stmt->bindParam(':fecha_estimada_fin', $data->fecha_estimada_fin);
            $stmt->bindParam(':fecha_real_fin', $data->fecha_real_fin);
            $stmt->bindParam(':estado', $data->estado);
            $stmt->bindParam(':porcentaje_avance', $data->porcentaje_avance);
            $stmt->bindParam(':monto_total', $data->monto_total);
            $stmt->bindParam(':monto_pagado', $data->monto_pagado);
            $stmt->bindParam(':estado_pago', $data->estado_pago);
            $stmt->bindParam(':tecnico_id', $data->tecnico_id);
            $stmt->bindParam(':observaciones', $data->observaciones);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "Orden de trabajo actualizada exitosamente."]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "No se encontró la orden o no hubo cambios."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo actualizar la orden."]);
            }
            break;

        case 'DELETE':
            if (!$ordenId) {
                http_response_code(400);
                echo json_encode(["message" => "ID de orden no proporcionado."]);
                break;
            }

            $sql = "DELETE FROM ordenes_trabajo WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $ordenId);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "Orden de trabajo eliminada exitosamente."]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Orden de trabajo no encontrada."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo eliminar la orden."]);
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