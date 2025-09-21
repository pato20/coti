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

// --- Verificación de Autenticación y Autorización ---
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

    // Autorización: Solo los administradores pueden gestionar usuarios
    if ($userData['role'] !== 'admin') {
        http_response_code(403); // Forbidden
        echo json_encode(["message" => "Acceso prohibido. No tiene permisos de administrador."]);
        exit();
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["message" => "Acceso denegado. Token inválido.", "error" => $e->getMessage()]);
    exit();
}
// --- Fin de Verificación ---

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? explode('/', rtrim($_GET['path'], '/')) : [];

$usuarioId = isset($path[2]) && is_numeric($path[2]) ? intval($path[2]) : null;

try {
    switch ($method) {
        case 'GET':
            $fields = "id, username, email, nombre_completo, rol, activo, ultimo_acceso, created_at, updated_at";
            if ($usuarioId) {
                $stmt = $pdo->prepare("SELECT $fields FROM usuarios WHERE id = :id");
                $stmt->bindParam(':id', $usuarioId);
                $stmt->execute();
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($usuario) {
                    http_response_code(200);
                    echo json_encode($usuario);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Usuario no encontrado."]);
                }
            } else {
                $stmt = $pdo->query("SELECT $fields FROM usuarios ORDER BY nombre_completo");
                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                http_response_code(200);
                echo json_encode($usuarios);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));

            if (empty($data->username) || empty($data->email) || empty($data->password) || empty($data->nombre_completo)) {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos. Username, email, password y nombre_completo son requeridos."]);
                break;
            }

            $password_hash = password_hash($data->password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios (username, email, password, nombre_completo, rol, activo) VALUES (:username, :email, :password, :nombre_completo, :rol, :activo)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':username', $data->username);
            $stmt->bindParam(':email', $data->email);
            $stmt->bindParam(':password', $password_hash);
            $stmt->bindParam(':nombre_completo', $data->nombre_completo);
            $stmt->bindValue(':rol', $data->rol ?? 'vendedor');
            $stmt->bindValue(':activo', $data->activo ?? 1, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $lastId = $pdo->lastInsertId();
                http_response_code(201);
                echo json_encode(["message" => "Usuario creado exitosamente.", "id" => $lastId]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo crear el usuario. El username o email ya podría existir."]);
            }
            break;

        case 'PUT':
            if (!$usuarioId) {
                http_response_code(400);
                echo json_encode(["message" => "ID de usuario no proporcionado."]);
                break;
            }

            $data = json_decode(file_get_contents("php://input"));

            // Construcción dinámica de la consulta para no actualizar la contraseña si no se proporciona
            $sql = "UPDATE usuarios SET username = :username, email = :email, nombre_completo = :nombre_completo, rol = :rol, activo = :activo";
            if (!empty($data->password)) {
                $sql .= ", password = :password";
            }
            $sql .= " WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':id', $usuarioId);
            $stmt->bindParam(':username', $data->username);
            $stmt->bindParam(':email', $data->email);
            $stmt->bindParam(':nombre_completo', $data->nombre_completo);
            $stmt->bindParam(':rol', $data->rol);
            $stmt->bindParam(':activo', $data->activo, PDO::PARAM_INT);

            if (!empty($data->password)) {
                $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
                $stmt->bindParam(':password', $password_hash);
            }

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "Usuario actualizado exitosamente."]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "No se encontró el usuario o no hubo cambios."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo actualizar el usuario. El username o email ya podría existir."]);
            }
            break;

        case 'DELETE':
            if (!$usuarioId) {
                http_response_code(400);
                echo json_encode(["message" => "ID de usuario no proporcionado."]);
                break;
            }
            
            // Evitar que un admin se elimine a sí mismo
            if ($usuarioId == $userData['id']) {
                http_response_code(400);
                echo json_encode(["message" => "No puede eliminar su propia cuenta de administrador."]);
                break;
            }

            $sql = "DELETE FROM usuarios WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $usuarioId);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "Usuario eliminado exitosamente."]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Usuario no encontrado."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "No se pudo eliminar el usuario."]);
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