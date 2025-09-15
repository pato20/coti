<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cotizacion_id = $input['cotizacion_id'] ?? null;
$tipo = $input['tipo'] ?? 'cotizacion';

if (!$cotizacion_id) {
    echo json_encode(['success' => false, 'message' => 'ID de cotización requerido']);
    exit;
}

try {
    // Obtener datos de la cotización y cliente
    $stmt = $pdo->prepare("
        SELECT c.*, cl.nombre as cliente_nombre, cl.telefono as cliente_telefono,
               e.nombre as empresa_nombre, e.telefono as empresa_telefono
        FROM cotizaciones c 
        JOIN clientes cl ON c.cliente_id = cl.id 
        JOIN empresa e ON e.id = 1
        WHERE c.id = ?
    ");
    $stmt->execute([$cotizacion_id]);
    $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cotizacion) {
        throw new Exception('Cotización no encontrada');
    }

    if (!$cotizacion['cliente_telefono']) {
        throw new Exception('El cliente no tiene número de teléfono registrado');
    }

    // Limpiar número de teléfono (solo números)
    $telefono = preg_replace('/[^0-9]/', '', $cotizacion['cliente_telefono']);
    
    // Agregar código de país si no lo tiene (Chile +56)
    if (!str_starts_with($telefono, '56')) {
        $telefono = '56' . $telefono;
    }

    // Crear mensaje de WhatsApp
    $mensaje = "🏢 *" . $cotizacion['empresa_nombre'] . "*\n\n";
    $mensaje .= "Hola " . $cotizacion['cliente_nombre'] . "! 👋\n\n";
    $mensaje .= "Te enviamos la cotización solicitada:\n\n";
    $mensaje .= "📋 *Cotización N°:* " . $cotizacion['numero_cotizacion'] . "\n";
    $mensaje .= "📅 *Fecha:* " . date('d/m/Y', strtotime($cotizacion['fecha_cotizacion'])) . "\n";
    $mensaje .= "💰 *Total:* $" . number_format($cotizacion['total'], 0, ',', '.') . "\n";
    $mensaje .= "⏰ *Válida hasta:* " . date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])) . "\n\n";
    $mensaje .= "Para ver el detalle completo, puedes descargar el PDF desde nuestro sistema.\n\n";
    $mensaje .= "¿Tienes alguna consulta? ¡Estamos aquí para ayudarte! 😊\n\n";
    $mensaje .= "📞 " . $cotizacion['empresa_telefono'];

    // URL de WhatsApp Web
    $whatsapp_url = "https://wa.me/" . $telefono . "?text=" . urlencode($mensaje);

    echo json_encode([
        'success' => true, 
        'message' => 'Mensaje preparado para WhatsApp',
        'whatsapp_url' => $whatsapp_url,
        'telefono' => $telefono
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
