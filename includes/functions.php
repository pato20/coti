<?php
// Colección de funciones de ayuda para el sistema

function generateQuoteNumber()
{
    return 'COT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generateWorkOrderNumber()
{
    return 'OT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function calculateCercoElectricoPrice($hilos, $instalacion, $metros)
{
    $precios = [
        '4' => ['basica' => 2250, 'media' => 2750, 'compleja' => 3750],
        '5' => ['basica' => 2750, 'media' => 3250, 'compleja' => 4250],
        '6' => ['basica' => 3250, 'media' => 3750, 'compleja' => 4750]
    ];
    
    $precio_metro = $precios[$hilos][$instalacion] ?? 0;
    return $precio_metro * $metros;
}

function sendWhatsAppMessage($phone, $message)
{
    // Integración con API de WhatsApp (ejemplo con WhatsApp Business API)
    $phone = str_replace(['+', ' ', '-'], '', $phone);
    $encoded_message = urlencode($message);
    $whatsapp_url = "https://wa.me/$phone?text=$encoded_message";
    return $whatsapp_url;
}

function sendEmail($to, $subject, $body, $attachments = [])
{
    // Configuración básica de email (usar PHPMailer en producción)
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: info@cercoselectricos.cl' . "\r\n";
    
    return mail($to, $subject, $body, $headers);
}

function formatCurrency($amount)
{
    if (!is_numeric($amount)) {
        $amount = 0;
    }
    return '$' . number_format($amount, 0, ',', '.');
}

function getEstadoColor($estado)
{
    $colors = [
        'pendiente' => 'warning',
        'enviada' => 'info',
        'aceptada' => 'success',
        'rechazada' => 'danger',
        'vencida' => 'secondary',
        'en_proceso' => 'primary',
        'pausada' => 'warning',
        'completada' => 'success',
        'cancelada' => 'danger'
    ];
    return $colors[$estado] ?? 'secondary';
}

function getRolColor($rol)
{
    $colors = [
        'admin' => 'danger',
        'vendedor' => 'primary',
        'tecnico' => 'success'
    ];
    return $colors[$rol] ?? 'secondary';
}

function tienePermiso($accion, $rol_usuario = null)
{
    if (!$rol_usuario) {
        $rol_usuario = $_SESSION['rol'] ?? null;
    }
    
    $permisos = [
        'admin' => ['*'], // Administrador tiene todos los permisos
        'vendedor' => ['clientes', 'cotizaciones', 'catalogo', 'reportes'],
        'tecnico' => ['ordenes', 'cotizaciones', 'clientes']
    ];
    
    if (!isset($permisos[$rol_usuario])) {
        return false;
    }
    
    return in_array('*', $permisos[$rol_usuario]) || in_array($accion, $permisos[$rol_usuario]);
}

/**
 * Registra una actividad en la base de datos.
 * Nota: Esta función requiere que la variable $pdo (conexión a la BD) esté disponible en el ámbito donde se llama.
 */
function registrarActividad($usuario_id, $accion, $descripcion = '')
{
    global $pdo;

    if (!$pdo) {
        error_log("registrarActividad: La conexión PDO no está disponible.");
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO actividades_usuario (usuario_id, accion, descripcion, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $usuario_id,
            $accion,
            $descripcion,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (PDOException $e) {
        error_log("Error registrando actividad: " . $e->getMessage());
    }
}

function generarPasswordSegura($longitud = 12)
{
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    
    for ($i = 0; $i < $longitud; $i++) {
        $password .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    
    return $password;
}

function recalculateQuoteTotals($cotizacion_id)
{
    global $pdo;

    if (!$pdo) {
        error_log("recalculateQuoteTotals: La conexión PDO no está disponible.");
        return;
    }

    try {
        // Calcular el subtotal desde los detalles
        $stmt_subtotal = $pdo->prepare("SELECT SUM(subtotal) as subtotal FROM cotizacion_detalles WHERE cotizacion_id = ?");
        $stmt_subtotal->execute([$cotizacion_id]);
        $subtotal_result = $stmt_subtotal->fetch(PDO::FETCH_ASSOC);
        $subtotal = $subtotal_result['subtotal'] ?? 0;

        // Obtener la cotización para saber si aplica IVA y descuento general
        $stmt_cot = $pdo->prepare("SELECT con_iva, descuento_general FROM cotizaciones WHERE id = ?");
        $stmt_cot->execute([$cotizacion_id]);
        $cotizacion = $stmt_cot->fetch(PDO::FETCH_ASSOC);

        $iva = 0;
        $total = $subtotal;

        // Aplicar descuento general si existe
        if ($cotizacion && $cotizacion['descuento_general'] > 0) {
            $total = $subtotal * (1 - ($cotizacion['descuento_general'] / 100));
        } else {
            $total = $subtotal;
        }

        if ($cotizacion && $cotizacion['con_iva']) {
            $iva = $total * 0.19; // IVA se calcula sobre el total después del descuento general
            $total = $total + $iva;
        }

        // Actualizar la cotización con los nuevos totales
        $stmt_update = $pdo->prepare("UPDATE cotizaciones SET subtotal = ?, iva = ?, total = ? WHERE id = ?");
        $stmt_update->execute([$subtotal, $iva, $total, $cotizacion_id]);

    } catch (PDOException $e) {
        error_log("Error recalculando totales de cotización: " . $e->getMessage());
        // Opcional: podrías lanzar una excepción aquí para que el try-catch principal la maneje
        throw $e;
    }
}

/**
 * Actualiza el stock de un producto.
 *
 * @param int $productId El ID del producto.
 * @param float $quantityChange La cantidad a sumar o restar (positivo para sumar, negativo para restar).
 * @return bool True si la actualización fue exitosa, false en caso contrario.
 */
function updateProductStock($productId, $quantityChange)
{
    global $pdo;

    if (!$pdo) {
        error_log("updateProductStock: La conexión PDO no está disponible.");
        return false;
    }

    try {
        $stmt = $pdo->prepare("UPDATE productos_servicios SET stock = stock + ? WHERE id = ? AND tipo = 'producto'");
        $stmt->execute([$quantityChange, $productId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error actualizando stock del producto {$productId}: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene el tipo de un producto/servicio (producto o servicio).
 *
 * @param int $productId El ID del producto/servicio.
 * @return string|null El tipo ('producto' o 'servicio') o null si no se encuentra.
 */
function getProductType($productId)
{
    global $pdo;

    if (!$pdo) {
        error_log("getProductType: La conexión PDO no está disponible.");
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT tipo FROM productos_servicios WHERE id = ?");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['tipo'] ?? null;
    } catch (PDOException $e) {
        error_log("Error obteniendo tipo de producto {$productId}: " . $e->getMessage());
        return null;
    }
}

/**
 * Verifica si hay una nueva versión de la aplicación.
 * Usa un sistema de caché en la sesión para no sobrecargar el servidor de actualizaciones.
 */
function check_for_updates() {
    // Solo los admins pueden verificar
    if (!is_admin()) {
        return;
    }

    $last_check = $_SESSION['update_last_check'] ?? 0;
    $now = time();
    $cache_duration = 3600; // 1 hora en segundos

    // Si no ha pasado 1 hora desde la última vez, no hacer nada
    if (($now - $last_check) < $cache_duration) {
        return;
    }

    // Actualizar el tiempo de la última verificación
    $_SESSION['update_last_check'] = $now;

    // Limpiar información de actualización anterior
    unset($_SESSION['update_available']);
    unset($_SESSION['update_info']);

    // No podemos usar init.php aquí para evitar bucles, así que cargamos lo mínimo necesario
    if (file_exists(__DIR__ . '/../config/app_config.php')) {
        require_once __DIR__ . '/../config/app_config.php';
    }

    if (!defined('UPDATE_MANIFEST_URL') || filter_var(UPDATE_MANIFEST_URL, FILTER_VALIDATE_URL) === false) {
        return; // No hay URL de manifiesto configurada
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, UPDATE_MANIFEST_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Necesario para XAMPP local
    $manifest_json = curl_exec($ch);
    curl_close($ch);

    if ($manifest_json === false) {
        return; // No se pudo conectar
    }

    $manifest_data = json_decode($manifest_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return; // JSON corrupto
    }

    $local_version = defined('APP_VERSION') ? APP_VERSION : '0.0.0';
    $remote_version = $manifest_data['version'] ?? '0.0.0';

    if (version_compare($remote_version, $local_version, 'gt')) {
        $_SESSION['update_available'] = true;
        $_SESSION['update_info'] = [
            'local_version' => $local_version,
            'remote_version' => $remote_version,
        ];
    }
}

function is_admin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

?>