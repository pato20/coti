<?php
// Permitir peticiones desde cualquier origen para desarrollo. En producción, restringir a dominios específicos.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Manejar petición OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir el autoloader de Composer y la configuración de la base de datos
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use \Firebase\JWT\JWT;

// Clave secreta para firmar el token. ¡CAMBIAR ESTO POR UNA CADENA LARGA Y SEGURA!
// Puedes generar una en: https://randomkeygen.com/
$secret_key = "TU_CLAVE_SECRETA_SUPER_SEGURA_AQUI";

// Leer el cuerpo de la petición
$data = json_decode(file_get_contents("php://input"));

// Validar que se recibieron los datos necesarios
if (!isset($data->username) || !isset($data->password)) {
    http_response_code(400); // Bad Request
    echo json_encode(["message" => "Usuario y contraseña son requeridos."]);
    exit();
}

$username = $data->username;
$password = $data->password;

// Conectar a la base de datos
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["message" => "Error de conexión a la base de datos."]);
    exit();
}

// Buscar al usuario en la base de datos
$stmt = $conn->prepare("SELECT id, username, password, role FROM usuarios WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Verificar si el usuario existe y la contraseña es correcta
if ($user && password_verify($password, $user['password'])) {
    
    $issuer_claim = "CERCO_APP_SERVER"; // puede ser el dominio de tu servidor
    $audience_claim = "CERCO_APP";
    $issuedat_claim = time(); // hora en que se emitió el token
    $notbefore_claim = $issuedat_claim; // token válido desde ahora
    $expire_claim = $issuedat_claim + (3600 * 24); // expira en 24 horas

    $token = array(
        "iss" => $issuer_claim,
        "aud" => $audience_claim,
        "iat" => $issuedat_claim,
        "nbf" => $notbefore_claim,
        "exp" => $expire_claim,
        "data" => array(
            "id" => $user['id'],
            "username" => $user['username'],
            "role" => $user['role']
        )
    );

    http_response_code(200); // OK

    // Generar el JWT
    $jwt = JWT::encode($token, $secret_key, 'HS256');
    echo json_encode(
        array(
            "message" => "Login exitoso.",
            "token" => $jwt,
            "expiresIn" => $expire_claim
        )
    );

} else {
    http_response_code(401); // Unauthorized
    echo json_encode(["message" => "Login fallido. Credenciales incorrectas."]);
}

$stmt->close();
$conn->close();
?>
