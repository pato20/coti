<?php
session_start();

// 1. Cargar dependencias y configuración
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/auth.php';

// 2. Verificación de seguridad: Solo administradores pueden acceder
verificarAuth();
verificarRol(['admin'], true); // El 'true' hace que la respuesta sea JSON en caso de error

// 3. Establecer cabecera de respuesta como JSON
header('Content-Type: application/json');

// Solo se permite el método POST para esta acción
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

try {
    // 4. Iniciar una transacción para asegurar la integridad de los datos
    $pdo->beginTransaction();

    // 5. Eliminar los detalles de todas las cotizaciones.
    // Se usa TRUNCATE para mayor eficiencia y para reiniciar el autoincremento.
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0"); // Desactivar temporalmente la revisión de claves foráneas
    $pdo->exec("TRUNCATE TABLE cotizacion_detalles");
    
    // 6. Eliminar todas las cotizaciones
    $pdo->exec("TRUNCATE TABLE cotizaciones");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); // Reactivar la revisión de claves foráneas

    // 7. Confirmar la transacción
    $pdo->commit();

    // 8. Enviar respuesta de éxito
    echo json_encode(['success' => true, 'message' => 'Todas las cotizaciones y sus detalles han sido eliminados exitosamente.']);

} catch (Exception $e) {
    // 9. En caso de error, revertir la transacción
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Re-activar las claves foráneas si la transacción falló a medio camino
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); 

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al eliminar las cotizaciones: ' . $e->getMessage()]);
}

exit;
?>