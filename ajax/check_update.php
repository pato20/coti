<?php
require_once '../includes/init.php';
require_once '../config/app_config.php';

// Solo para administradores
if (!is_admin()) {
    header('HTTP/1.1 403 Forbidden');
    die('<div class="alert alert-danger">Acceso denegado.</div>');
}

// --- Verificación de la URL del Manifiesto ---
if (!defined('UPDATE_MANIFEST_URL') || filter_var(UPDATE_MANIFEST_URL, FILTER_VALIDATE_URL) === false) {
    die('<div class="alert alert-danger">La URL del manifiesto de actualización no está configurada correctamente.</div>');
}

// --- Obtener el manifiesto remoto usando cURL ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, UPDATE_MANIFEST_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 segundos de timeout
curl_setopt($ch, CURLOPT_USERAGENT, 'Cerco App Updater'); // Es buena práctica enviar un User-Agent

// Para problemas de SSL en entornos locales como XAMPP, puede que necesites descomentar la siguiente línea.
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$manifest_json = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($manifest_json === false) {
    $error_message = 'No se pudo conectar al servidor de actualizaciones. Por favor, inténtalo de nuevo más tarde.';
    if ($curl_error) {
        $error_message .= '<br><small>Detalle: ' . htmlspecialchars($curl_error) . '</small>';
    }
    die('<div class="alert alert-warning">' . $error_message . '</div>');
}

$manifest_data = json_decode($manifest_json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die('<div class="alert alert-danger">El archivo de manifiesto remoto está corrupto o no es un JSON válido.</div>');
}

// --- Comparar versiones ---
$local_version = APP_VERSION;
$remote_version = $manifest_data['version'] ?? '0';

if (version_compare($remote_version, $local_version, 'gt')) {
    // Hay una actualización disponible
    $release_notes = !empty($manifest_data['release_notes']) 
        ? nl2br(htmlspecialchars($manifest_data['release_notes'])) 
        : 'No hay notas de esta versión.';

    echo <<<HTML
    <div class="alert alert-success">
        <h4 class="alert-heading">¡Nueva versión disponible!</h4>
        <p>
            Hay una nueva versión <strong>($remote_version)</strong> lista para instalar.
            Tu versión actual es la <strong>$local_version</strong>.
        </p>
        <hr>
        <h5>Notas de la versión:</h5>
        <div class="release-notes">$release_notes</div>
        <hr>
        <form action="updater.php?action=run_update" method="POST">
            <input type="hidden" name="version" value="{$remote_version}">
            <input type="hidden" name="url" value="{$manifest_data['url']}">
            <input type="hidden" name="checksum" value="{$manifest_data['checksum']}">
            <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('¿Estás seguro de que quieres iniciar el proceso de actualización? La aplicación entrará en modo mantenimiento.')">
                <i class="fas fa-rocket me-2"></i>
                Actualizar Ahora
            </button>
        </form>
    </div>
HTML;

} else {
    // Ya estás en la última versión
    echo <<<HTML
    <div class="alert alert-info">
        <h4 class="alert-heading">¡Estás al día!</h4>
        <p>Tu instalación de la aplicación (versión <strong>$local_version</strong>) es la más reciente disponible.</p>
    </div>
HTML;
}
