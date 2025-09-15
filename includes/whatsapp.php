<?php
require_once 'config/database.php';

function obtenerConfiguracionWhatsApp() {
    global $pdo;
    $config = [];
    $stmt = $pdo->query("SELECT clave, valor FROM configuracion WHERE clave IN ('whatsapp_numero', 'whatsapp_token', 'empresa_nombre')");
    while ($row = $stmt->fetch()) {
        $config[$row['clave']] = $row['valor'];
    }
    return $config;
}

function enviarWhatsApp($numero, $mensaje) {
    $config = obtenerConfiguracionWhatsApp();
    
    $whatsapp_token = $config['whatsapp_token'] ?? '';
    $empresa_nombre = $config['empresa_nombre'] ?? 'Sistema de Cotización';
    
    if (empty($whatsapp_token)) {
        return ['success' => false, 'message' => 'Token de WhatsApp no configurado'];
    }
    
    // Limpiar número de teléfono
    $numero = preg_replace('/[^0-9]/', '', $numero);
    if (substr($numero, 0, 2) == '56') {
        $numero = '+' . $numero;
    } elseif (substr($numero, 0, 1) == '9') {
        $numero = '+56' . $numero;
    } else {
        $numero = '+56' . $numero;
    }
    
    // Preparar mensaje
    $mensaje_completo = "*{$empresa_nombre}*\n\n" . $mensaje;
    
    // Aquí implementarías la integración con la API de WhatsApp Business
    // Por ahora, simularemos el envío
    
    // Ejemplo de integración con WhatsApp Business API:
    /*
    $url = "https://graph.facebook.com/v17.0/YOUR_PHONE_NUMBER_ID/messages";
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $numero,
        'type' => 'text',
        'text' => ['body' => $mensaje_completo]
    ];
    
    $headers = [
        'Authorization: Bearer ' . $whatsapp_token,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        return ['success' => true, 'message' => 'Mensaje enviado por WhatsApp'];
    } else {
        return ['success' => false, 'message' => 'Error al enviar WhatsApp: ' . $response];
    }
    */
    
    // Simulación para desarrollo
    return ['success' => true, 'message' => 'Mensaje de WhatsApp simulado enviado a ' . $numero];
}

function generarMensajeWhatsApp($tipo, $datos) {
    switch ($tipo) {
        case 'cotizacion':
            return "🔌 *Nueva Cotización #{$datos['numero']}*\n\n" .
                   "Hola {$datos['cliente_nombre']},\n\n" .
                   "Hemos preparado su cotización para cerco eléctrico:\n" .
                   "💰 Total: $" . number_format($datos['total'], 0, ',', '.') . "\n" .
                   "📅 Válida hasta: " . date('d/m/Y', strtotime($datos['fecha'] . ' +30 days')) . "\n\n" .
                   "¿Desea proceder con la instalación? Responda este mensaje para coordinar.";
                   
        case 'orden_trabajo':
            return "✅ *Orden de Trabajo #{$datos['numero']}*\n\n" .
                   "Hola {$datos['cliente_nombre']},\n\n" .
                   "Su cotización ha sido aprobada. Detalles:\n" .
                   "📋 Estado: " . ucfirst($datos['estado']) . "\n" .
                   "📅 Inicio: " . date('d/m/Y', strtotime($datos['fecha_inicio'])) . "\n" .
                   "📊 Progreso: {$datos['progreso']}%\n\n" .
                   "Le mantendremos informado sobre el avance.";
                   
        case 'actualizacion_orden':
            $emoji = '';
            switch ($datos['estado']) {
                case 'en_proceso': $emoji = '🔧'; break;
                case 'pausada': $emoji = '⏸️'; break;
                case 'completada': $emoji = '✅'; break;
                default: $emoji = '📋'; break;
            }
            
            return "{$emoji} *Actualización Orden #{$datos['numero']}*\n\n" .
                   "Hola {$datos['cliente_nombre']},\n\n" .
                   "Estado actual: " . ucfirst($datos['estado']) . "\n" .
                   "📊 Progreso: {$datos['progreso']}%\n" .
                   "🕐 Actualizado: " . date('d/m/Y H:i', strtotime($datos['fecha_actualizacion'])) . "\n\n" .
                   (!empty($datos['comentarios']) ? "💬 Comentarios: {$datos['comentarios']}\n\n" : '') .
                   "¿Tiene alguna consulta? Responda este mensaje.";
                   
        default:
            return $datos['mensaje'] ?? 'Mensaje desde el sistema de cotización.';
    }
}

function enviarNotificacionWhatsApp($tipo, $datos) {
    $mensaje = generarMensajeWhatsApp($tipo, $datos);
    return enviarWhatsApp($datos['telefono'], $mensaje);
}
?>
