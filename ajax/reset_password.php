<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

verificarAuth();
verificarRol(['admin']);

header('Content-Type: application/json');

if ($_POST && isset($_POST['usuario_id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $usuario_id = $_POST['usuario_id'];
        
        // Generar nueva contraseña
        $nueva_password = generarPasswordSegura(10);
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        
        // Actualizar contraseña en BD
        $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->execute([$password_hash, $usuario_id]);
        
        // Obtener datos del usuario para enviar email
        $stmt = $db->prepare("SELECT email, nombre_completo FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            // Enviar email con nueva contraseña (en producción usar un sistema más seguro)
            $subject = "Nueva contraseña - Sistema de Cotización";
            $body = "
                <h2>Nueva Contraseña</h2>
                <p>Hola {$usuario['nombre_completo']},</p>
                <p>Tu contraseña ha sido restablecida. Tu nueva contraseña temporal es:</p>
                <p><strong>{$nueva_password}</strong></p>
                <p>Por favor, cambia esta contraseña después de iniciar sesión.</p>
                <p>Saludos,<br>Equipo de Cercos Eléctricos</p>
            ";
            
            sendEmail($usuario['email'], $subject, $body);
            
            // Registrar actividad
            registrarActividad($_SESSION['usuario_id'], 'reset_password', "Contraseña restablecida para usuario ID: $usuario_id");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Contraseña restablecida exitosamente. Se ha enviado un email al usuario.',
            'nueva_password' => $nueva_password // Solo para mostrar en desarrollo
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al restablecer contraseña: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
}
?>
