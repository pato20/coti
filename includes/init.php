<?php
// Silencia errores de "session already started" en algunos entornos
error_reporting(E_ALL & ~E_NOTICE);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Si no existe el archivo de config, redirigir al instalador
if (!file_exists(__DIR__ . '/../config/database.php') && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    header('Location: install.php');
    exit;
}

// Incluir archivos solo si la instalación se ha completado
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/auth.php';

    verificarAuth();

    // Cargar datos del usuario logueado
    $current_user = null;
    if (isset($_SESSION['usuario_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // La tabla podría no existir durante la instalación
        }
    }

    // Cargar datos de la empresa
    $empresa_info = null;
    try {
        $stmt_empresa = $pdo->query("SELECT * FROM empresa LIMIT 1");
        $empresa_info = $stmt_empresa->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // La tabla podría no existir durante la instalación
    }

    // Cargar todas las configuraciones del sistema
    $app_settings = [];
    try {
        $app_settings_raw = $pdo->query("SELECT clave, valor FROM configuracion")->fetchAll(PDO::FETCH_KEY_PAIR);
        if ($app_settings_raw) {
            $app_settings = $app_settings_raw;
        }
    } catch (PDOException $e) {
        // La tabla podría no existir durante la instalación
    }
}
?>