<?php
// Funciones de Autenticación y Permisos

/**
 * Verifica si el usuario ha iniciado sesión. Si no, redirige a login.php o devuelve un error JSON.
 * @param bool  $is_ajax          Si es true, devuelve un error JSON en lugar de redirigir.
 */
function verificarAuth(bool $is_ajax = false) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['usuario_id'])) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'No autenticado. Por favor, inicie sesión.']);
        } else {
            header('Location: login.php');
        }
        exit;
    }
}

/**
 * Verifica si el rol del usuario está en la lista de roles permitidos.
 * Maneja tanto redirecciones normales como respuestas JSON para AJAX.
 *
 * @param array $roles_permitidos Array con los roles que tienen acceso.
 * @param bool  $is_ajax          Si es true, devuelve un error JSON en lugar de redirigir.
 */
function verificarRol(array $roles_permitidos, bool $is_ajax = false) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $rol_usuario = $_SESSION['rol'] ?? null;

    if (!$rol_usuario || !in_array($rol_usuario, $roles_permitidos)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'Acceso denegado. No tiene los permisos necesarios.']);
        } else {
            header('Location: index.php?error=sin_permisos');
        }
        exit;
    }
}

/**
 * Cierra la sesión del usuario, la elimina de la BD y redirige al login.
 */
function cerrarSesion() {
    global $pdo;
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['usuario_id'])) {
        // Eliminar sesión de BD
        $stmt = $pdo->prepare("DELETE FROM sesiones WHERE id = ?");
        $stmt->execute([session_id()]);
    }
    
    session_destroy();
    header('Location: login.php');
    exit;
}
?>