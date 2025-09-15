<?php
// Este script está diseñado para ser ejecutado por un cron job.
// No debe ser accesible directamente vía web.

// Definir la ruta raíz del proyecto
define('ROOT_PATH', dirname(__DIR__));

// Incluir archivos necesarios
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/email.php'; // Para enviar emails

// Configuración de la notificación
$dias_antes_vencimiento = 3; // Notificar cotizaciones que vencen en los próximos 3 días

echo "Iniciando script de notificación de cotizaciones próximas a vencer.\n";

try {
    // Obtener cotizaciones próximas a vencer
    $stmt = $pdo->prepare("
        SELECT 
            c.id, c.numero_cotizacion, c.fecha_vencimiento, c.estado,
            cl.nombre AS cliente_nombre, cl.email AS cliente_email,
            u.email AS vendedor_email, u.nombre_completo AS vendedor_nombre,
            e.nombre AS empresa_nombre
        FROM 
            cotizaciones c
        JOIN 
            clientes cl ON c.cliente_id = cl.id
        LEFT JOIN
            usuarios u ON c.vendedor_id = u.id -- Asumiendo que tienes un campo vendedor_id en cotizaciones
        LEFT JOIN
            empresa e ON e.id = 1 -- Asumiendo que la información de la empresa está en la fila 1
        WHERE 
            c.estado IN ('pendiente', 'enviada') AND
            c.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ");
    $stmt->execute([$dias_antes_vencimiento]);
    $cotizaciones_a_notificar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cotizaciones_a_notificar)) {
        echo "No se encontraron cotizaciones próximas a vencer.\n";
    } else {
        echo "Se encontraron " . count($cotizaciones_a_notificar) . " cotizaciones próximas a vencer.\n";
        foreach ($cotizaciones_a_notificar as $cotizacion) {
            echo "Procesando cotización #" . $cotizacion['numero_cotizacion'] . " (ID: " . $cotizacion['id'] . ").\n";

            // --- Notificación al Cliente ---
            if (!empty($cotizacion['cliente_email'])) {
                $asunto_cliente = "Recordatorio: Tu cotización #" . $cotizacion['numero_cotizacion'] . " está por vencer";
                
                $email_data = [
                    'numero_cotizacion' => $cotizacion['numero_cotizacion'],
                    'cliente_nombre' => $cotizacion['cliente_nombre'],
                    'fecha_vencimiento' => date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])),
                    'link_cotizacion' => 'http://localhost/cerco/cotizacion_pdf.php?id=' . $cotizacion['id'], // Reemplazar con la URL real de tu aplicación
                    'empresa_nombre' => $cotizacion['empresa_nombre']
                ];
                $mensaje_cliente_html = generarPlantillaEmail('recordatorio_vencimiento_cotizacion', $email_data); // Necesitarás crear esta plantilla

                $email_sent_cliente = enviarEmail($cotizacion['cliente_email'], $asunto_cliente, $mensaje_cliente_html);
                if ($email_sent_cliente) {
                    echo "  - Email de recordatorio enviado al cliente " . $cotizacion['cliente_email'] . ".\n";
                } else {
                    echo "  - ERROR: No se pudo enviar email al cliente " . $cotizacion['cliente_email'] . ".\n";
                }
            } else {
                echo "  - Cliente sin email, no se envió notificación.\n";
            }

            // --- Notificación Interna (al Vendedor, si existe) ---
            if (!empty($cotizacion['vendedor_email'])) {
                $asunto_vendedor = "Recordatorio Interno: Cotización #" . $cotizacion['numero_cotizacion'] . " de " . $cotizacion['cliente_nombre'] . " está por vencer";
                $mensaje_vendedor = "Hola " . htmlspecialchars($cotizacion['vendedor_nombre']) . ",\n\n";
                $mensaje_vendedor .= "La cotización número " . htmlspecialchars($cotizacion['numero_cotizacion']) . " para el cliente " . htmlspecialchars($cotizacion['cliente_nombre']) . " vence el " . date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])) . ".\n";
                $mensaje_vendedor .= "Por favor, haz seguimiento con el cliente.\n\n";
                $mensaje_vendedor .= "Saludos,\nSistema de Cotizaciones";

                $email_sent_vendedor = enviarEmail($cotizacion['vendedor_email'], $asunto_vendedor, $mensaje_vendedor);
                if ($email_sent_vendedor) {
                    echo "  - Email de recordatorio enviado al vendedor " . $cotizacion['vendedor_email'] . ".\n";
                } else {
                    echo "  - ERROR: No se pudo enviar email al vendedor " . $cotizacion['vendedor_email'] . ".\n";
                }
            } else {
                echo "  - Vendedor no asignado o sin email, no se envió notificación interna.\n";
            }
        }
    }

} catch (Exception $e) {
    echo "ERROR FATAL: " . $e->getMessage() . "\n";
}

echo "Script de notificación de cotizaciones finalizado.\n";
?>