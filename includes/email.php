<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Asegúrate de que la ruta a PHPMailer sea correcta
// La ruta debe ser relativa desde el archivo que incluye email.php
// Por ejemplo, si email.php está en includes/, y PHPMailer está en assets/PHPMailer/src/
// la ruta sería ../assets/PHPMailer/src/Exception.php

require_once __DIR__ . '/../assets/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../assets/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../assets/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/functions.php'; // Incluir para tener formatCurrency()

// Asegúrate de que config/database.php esté incluido antes de llamar a obtenerConfiguracion
// Esto se maneja en los archivos principales (cotizaciones.php, ordenes.php, etc.)

function obtenerConfiguracionEmail() {
    global $pdo;
    $config = [];
    try {
        $stmt = $pdo->query("SELECT clave, valor FROM configuracion WHERE clave LIKE 'smtp_%' OR clave LIKE 'empresa_%'");
        while ($row = $stmt->fetch()) {
            $config[$row['clave']] = $row['valor'];
        }
    } catch (PDOException $e) {
        error_log("Error al obtener configuración de email: " . $e->getMessage());
    }
    return $config;
}

function enviarEmail($destinatario, $asunto, $mensaje_html, $adjuntos = []) {
    $config = obtenerConfiguracionEmail();
    
    $mail = new PHPMailer(true);
    try {
        // Configuración del Servidor
        $mail->isSMTP();                                            // Usar SMTP
        $mail->Host       = $config['smtp_host'] ?? '';       // Servidor SMTP
        $mail->SMTPAuth   = true;                                   // Habilitar autenticación SMTP
        $mail->Username   = $config['smtp_username'] ?? '';       // Usuario SMTP
        $mail->Password   = $config['smtp_password'] ?? '';       // Contraseña SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Habilitar encriptación TLS
        $mail->Port       = $config['smtp_port'] ?? 587;       // Puerto TCP
        $mail->CharSet    = 'UTF-8';

        // Remitente
        $mail->setFrom($config['smtp_username'] ?? 'no-reply@example.com', $config['empresa_nombre'] ?? 'Sistema');

        // Destinatario
        $mail->addAddress($destinatario);

        // Contenido
        $mail->isHTML(true);                                        // Establecer formato de email a HTML
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje_html;
        $mail->AltBody = strip_tags($mensaje_html); // Versión de texto plano para clientes que no soportan HTML

        // Adjuntos
        foreach ($adjuntos as $adjunto) {
            if (file_exists($adjunto)) {
                $mail->addAttachment($adjunto);
            }
        }

        $mail->send();
        return ['success' => true, 'message' => 'Email enviado exitosamente'];
    } catch (Exception $e) {
        error_log("Error al enviar email: {$mail->ErrorInfo}");
        return ['success' => false, 'message' => "Error al enviar email: {$mail->ErrorInfo}"];
    }
}

function generarPlantillaEmail($tipo, $datos) {
    global $pdo;
    $config = obtenerConfiguracionEmail(); // Usar la nueva función
    $empresa_nombre = $config['empresa_nombre'] ?? 'Sistema de Cotización';
    $empresa_telefono = $config['empresa_telefono'] ?? '';
    $empresa_email = $config['empresa_email'] ?? '';
    $empresa_web = $config['empresa_web'] ?? '';
    
    $plantilla = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $empresa_nombre . '</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { padding: 20px; }
            .footer { background: #34495e; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 8px 8px; }
            .btn { display: inline-block; padding: 10px 20px; background: #2c3e50; color: white; text-decoration: none; border-radius: 5px; }
            .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .table th { background: #f2f2f2; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . $empresa_nombre . '</h1>
            </div>
            <div class="content">
    ';
    
    switch ($tipo) {
        case 'cotizacion':
            $plantilla .= '
                <h2>Nueva Cotización #' . $datos['numero'] . '</h2>
                <p>Estimado/a ' . $datos['cliente_nombre'] . ',</p>
                <p>Adjuntamos la cotización solicitada.</p>
                
                <table class="table">
                    <tr><th>Número de Cotización:</th><td>' . $datos['numero'] . '</td></tr>
                    <tr><th>Fecha:</th><td>' . date('d/m/Y', strtotime($datos['fecha'])) . '</td></tr>
                    <tr><th>Cliente:</th><td>' . $datos['cliente_nombre'] . '</td></tr>
                    <tr><th>Total:</th><td>' . formatCurrency($datos['total']) . '</td></tr>
                </table>
                
                <p>Esta cotización tiene una validez de 30 días.</p>
                <p>Para cualquier consulta, no dude en contactarnos.</p>
            ';
            break;
            
        case 'orden_trabajo':
            $plantilla .= '
                <h2>Orden de Trabajo #' . $datos['numero'] . '</h2>
                <p>Estimado/a ' . $datos['cliente_nombre'] . ',</p>
                <p>Su cotización ha sido aprobada y hemos generado la orden de trabajo correspondiente.</p>
                
                <table class="table">
                    <tr><th>Número de Orden:</th><td>' . $datos['numero'] . '</td></tr>
                    <tr><th>Estado:</th><td>' . ucfirst($datos['estado']) . '</td></tr>
                    <tr><th>Fecha de Inicio:</th><td>' . date('d/m/Y', strtotime($datos['fecha_inicio'])) . '</td></tr>
                    <tr><th>Progreso:</th><td>' . $datos['progreso'] . '%</td></tr>
                </table>
                
                <p>Mantendremos informado sobre el progreso de su instalación.</p>
            ';
            break;
            
        case 'actualizacion_orden':
            $plantilla .= '
                <h2>Actualización Orden de Trabajo #' . $datos['numero'] . '</h2>
                <p>Estimado/a ' . $datos['cliente_nombre'] . ',</p>
                <p>Le informamos sobre el progreso de su orden de trabajo:</p>
                
                <table class="table">
                    <tr><th>Estado Actual:</th><td>' . ucfirst($datos['estado']) . '</td></tr>
                    <tr><th>Progreso:</th><td>' . $datos['progreso'] . '%</td></tr>
                    <tr><th>Última Actualización:</th><td>' . date('d/m/Y H:i', strtotime($datos['fecha_actualizacion'])) . '</td></tr>
                </table>
                
                ' . (!empty($datos['comentarios']) ? '<p><strong>Comentarios:</strong> ' . $datos['comentarios'] . '</p>' : '') . '
            ';
            break;

        case 'recordatorio_vencimiento_cotizacion':
            $plantilla .= '
                <h2>Recordatorio: Cotización #' . $datos['numero_cotizacion'] . ' por vencer</h2>
                <p>Estimado/a ' . $datos['cliente_nombre'] . ',</p>
                <p>Queremos recordarte que la cotización número <strong>' . htmlspecialchars($datos['numero_cotizacion']) . '</strong>, emitida por ' . htmlspecialchars($datos['empresa_nombre']) . ', vence el <strong>' . htmlspecialchars($datos['fecha_vencimiento']) . '</strong>.</p>
                <p>Si aún estás interesado/a en nuestros servicios o productos, te invitamos a revisar los detalles de tu cotización haciendo clic en el siguiente enlace:</p>
                <p style="text-align: center; margin: 20px 0;"><a href="' . htmlspecialchars($datos['link_cotizacion']) . '" class="btn">Ver Cotización</a></p>
                <p>Para cualquier consulta o para aceptar la cotización, no dudes en contactarnos.</p>
                <p>¡Esperamos poder servirte pronto!</p>
            ';
            break;
            
        case 'pago_registrado':
            $plantilla .= '
                <h2>Pago Registrado para Orden #' . $datos['numero_orden'] . '</h2>
                <p>Estimado/a ' . $datos['cliente_nombre'] . ',</p>
                <p>Hemos registrado un pago de ' . formatCurrency($datos['monto_pago']) . ' para su orden de trabajo.</p>
                
                <table class="table">
                    <tr><th>Orden:</th><td>' . $datos['numero_orden'] . '</td></tr>
                    <tr><th>Monto Pagado:</th><td>' . formatCurrency($datos['monto_pago']) . '</td></tr>
                    <tr><th>Total Pagado:</th><td>' . formatCurrency($datos['total_pagado']) . '</td></tr>
                    <tr><th>Saldo Pendiente:</th><td>' . formatCurrency($datos['saldo_pendiente']) . '</td></tr>
                    <tr><th>Estado de Pago:</th><td>' . ucfirst($datos['estado_pago']) . '</td></tr>
                </table>
                
                <p>Gracias por su pago.</p>
            ';
            break;
    }
    
    $plantilla .= '
            </div>
            <div class="footer">
                <p>' . $empresa_nombre . '</p>
                ' . ($empresa_telefono ? '<p>Teléfono: ' . $empresa_telefono . '</p>' : '') . '
                ' . ($empresa_email ? '<p>Email: ' . $empresa_email . '</p>' : '') . '
                ' . ($empresa_web ? '<p>Web: ' . $empresa_web . '</p>' : '') . '
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $plantilla;
}
?>