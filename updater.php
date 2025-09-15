<?php
require_once 'includes/init.php';
require_once 'config/app_config.php';

if (!is_admin()) {
    header('Location: index.php');
    exit;
}

$page_title = "Actualizador";
$current_page = "updater";
$action = $_GET['action'] ?? null;

// --- Lógica de la Actualización ---
if ($action === 'run_update') {
    // Iniciar la interfaz de streaming
    ob_start();
    require_once 'includes/header.php';
    ?>
    <style>
        #update-log { background-color: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 5px; font-family: monospace; height: 400px; overflow-y: scroll; }
        .log-entry { margin-bottom: 5px; }
        .log-success { color: #2ecc71; }
        .log-error { color: #e74c3c; font-weight: bold; }
        .log-info { color: #3498db; }
    </style>
    <div class="page-header">
        <h1 class="h2 mb-1"><i class="fas fa-rocket me-2 text-primary"></i> Actualización en Progreso</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <div id="update-log"></div>
            <a href="index.php" id="back-to-dash" class="btn btn-primary mt-3" style="display:none;">Volver al Dashboard</a>
        </div>
    </div>
    <?php
    require_once 'includes/footer.php';
    
    function log_message($message, $type = 'info') {
        echo '<div class="log-entry log-' . $type . '"><pre>[' . date('Y-m-d H:i:s') . '] ' . htmlspecialchars($message) . '</pre></div>';
        ob_flush();
        flush();
        usleep(100000); // Pequeña pausa para que el navegador renderice
    }

    function show_final_button() {
        echo '<script>document.getElementById("back-to-dash").style.display = "block";</script>';
        ob_flush();
        flush();
    }

    // 1. Activar modo mantenimiento
    log_message("Activando modo mantenimiento...");
    @file_put_contents('.maintenance', 'Sistema en mantenimiento por actualización.');
    log_message("Modo mantenimiento activado.", "success");

    // Directorios
    $backup_dir = __DIR__ . '/backups';
    $temp_dir = __DIR__ . '/tmp_update';

    // Validar datos POST
    $version = $_POST['version'] ?? null;
    $url = $_POST['url'] ?? null;
    $checksum = $_POST['checksum'] ?? null;

    if (!$version || !$url || !$checksum) {
        log_message("Error: Faltan datos para la actualización.", "error");
        @unlink('.maintenance');
        exit;
    }

    try {
        // 2. Descargar actualización con cURL
        log_message("Descargando paquete desde $url...");
        $update_zip_path = $temp_dir . '/update.zip';
        if (!is_dir($temp_dir)) mkdir($temp_dir, 0755, true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Timeout más largo para descargas
        curl_setopt($ch, CURLOPT_USERAGENT, 'Cerco App Updater');
        
        // Para problemas de SSL en entornos locales como XAMPP
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $zip_content = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($zip_content === false) {
            throw new Exception("No se pudo descargar el archivo de actualización. Detalle: " . htmlspecialchars($curl_error));
        }
        
        file_put_contents($update_zip_path, $zip_content);
        log_message("Paquete descargado.", "success");

        // 3. Verificar Checksum
        log_message("Verificando integridad del archivo...");
        $downloaded_checksum = hash_file('sha256', $update_zip_path);
        if (!hash_equals($checksum, $downloaded_checksum)) {
            throw new Exception("El checksum no coincide. El archivo puede estar corrupto o alterado.");
        }
        log_message("Checksum verificado correctamente.", "success");

        // 4. Crear Backup
        log_message("Creando copia de seguridad...");
        if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);
        if (!file_exists($backup_dir . '/.htaccess')) {
            file_put_contents($backup_dir . '/.htaccess', 'deny from all');
        }
        $backup_file = $backup_dir . '/backup-' . date('Y-m-d_H-i-s') . '-' . APP_VERSION . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($backup_file, ZipArchive::CREATE) !== TRUE) throw new Exception("No se pudo crear el archivo de backup.");
        
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (strpos($file->getRealPath(), $backup_dir) === false && strpos($file->getRealPath(), $temp_dir) === false) {
                $relativePath = substr($file->getRealPath(), strlen(__DIR__) + 1);
                $zip->addFile($file->getRealPath(), $relativePath);
            }
        }
        $zip->close();
        log_message("Copia de seguridad creada en: " . basename($backup_file), "success");

        // 5. Extraer nuevos archivos
        log_message("Extrayendo nuevos archivos...");
        if ($zip->open($update_zip_path) !== TRUE) throw new Exception("No se pudo abrir el paquete de actualización.");
        $zip->extractTo($temp_dir);
        $zip->close();
        log_message("Archivos extraídos en directorio temporal.", "success");

        // 6. Instalar archivos
        log_message("Instalando nuevos archivos...");

        // --- Lógica mejorada para encontrar el directorio correcto ---
        $scan = glob($temp_dir . '/*');
        $update_files_dir = $temp_dir; // Por defecto, es el directorio temporal

        // Filtra el update.zip para no considerarlo como directorio de actualizacion
        $scan = array_filter($scan, function($file) {
            return basename($file) != 'update.zip';
        });

        if (count($scan) === 1 && is_dir(reset($scan))) {
            // Si solo hay un directorio dentro, ese es nuestro directorio de origen
            $update_files_dir = reset($scan);
            log_message("Directorio de actualización encontrado: " . basename($update_files_dir));
        } else {
            log_message("Los archivos de actualización están en la raíz del zip.");
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($update_files_dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        
        foreach ($iterator as $file) {
            $destination = __DIR__ . '/' . $iterator->getSubPathName();
            if ($file->isDir()) {
                if (!is_dir($destination)) {
                    mkdir($destination, 0755, true);
                }
            } else {
                if (!copy($file, $destination)) {
                     log_message("ADVERTENCIA: No se pudo copiar el archivo: " . $file->getRealPath(), "error");
                }
            }
        }
        log_message("Nuevos archivos instalados.", "success");

        // 7. Ejecutar migraciones de BD
        log_message("Iniciando proceso de migración de base de datos...");
        $migrations_dir = $temp_dir . '/migrations';
        if (is_dir($migrations_dir)) {
            // Obtener migraciones ya aplicadas
            try {
                $stmt = $pdo->query("SELECT version FROM migrations");
                $applied_migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
                log_message("Migraciones aplicadas encontradas: " . implode(', ', $applied_migrations ?: ['ninguna']));
            } catch (PDOException $e) {
                log_message("Tabla 'migrations' no encontrada. Asumiendo primera instalación de migraciones.", "info");
                $pdo->exec("CREATE TABLE `migrations` (`id` int(11) NOT NULL AUTO_INCREMENT, `version` varchar(255) NOT NULL, `applied_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), UNIQUE KEY `version` (`version`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
                log_message("Tabla 'migrations' creada.", "success");
                $applied_migrations = [];
            }

            $migration_files = glob($migrations_dir . '/*.sql');
            sort($migration_files); // Asegurar orden de ejecución

            $new_migrations_found = false;
            foreach ($migration_files as $file) {
                $new_migrations_found = true;
                $version_from_file = basename($file, '.sql');
                
                if (in_array($version_from_file, $applied_migrations)) {
                    log_message("Migración '$version_from_file' ya ha sido aplicada. Omitiendo.");
                    continue;
                }

                log_message("Aplicando migración '$version_from_file'...");
                $sql = file_get_contents($file);
                $pdo->exec($sql);

                // Registrar nueva migración
                $insert_stmt = $pdo->prepare("INSERT INTO migrations (version) VALUES (?)");
                $insert_stmt->execute([$version_from_file]);
                log_message("Migración '$version_from_file' aplicada y registrada.", "success");
            }

            if (!$new_migrations_found) {
                log_message("No se encontraron nuevos archivos de migración en el paquete.");
            }

        } else {
            log_message("El paquete de actualización no contiene un directorio de migraciones.");
        }

        // 8. Actualizar versión local
        log_message("Actualizando número de versión local...");
        $config_path = __DIR__ . '/config/app_config.php';
        $config_content = file_get_contents($config_path);
        $new_config_content = preg_replace("/define\('APP_VERSION', '.*?'\);/", "define('APP_VERSION', '$version');", $config_content);
        file_put_contents($config_path, $new_config_content);
        log_message("Versión actualizada a $version.", "success");

        log_message("Actualización completada con éxito.", "success");

    } catch (Exception $e) {
        log_message("ERROR: " . $e->getMessage(), "error");
        log_message("El sistema podría estar inestable. Se recomienda restaurar la copia de seguridad.");
    } finally {
        // 9. Limpieza y desactivar modo mantenimiento
        log_message("Realizando limpieza...");
        if (is_dir($temp_dir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir()) rmdir($file->getRealPath()); else unlink($file->getRealPath());
            }
            rmdir($temp_dir);
        }
        log_message("Archivos temporales eliminados.");
        
        @unlink('.maintenance');
        log_message("Modo mantenimiento desactivado.");
        show_final_button();
    }

    exit;
}

// --- Vista Principal del Actualizador ---
require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="h2 mb-1">
        <i class="fas fa-sync-alt me-2 text-primary"></i>
        Sistema de Actualización
    </h1>
    <p class="text-muted mb-0">Comprueba si hay nuevas versiones y actualiza la aplicación.</p>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Verificación de Versión</h5>
    </div>
    <div class="card-body">
        <p>Versión actual instalada: <strong class="badge bg-primary fs-6"><?= htmlspecialchars(APP_VERSION) ?></strong></p>
        
        <div id="update-checker">
            <button id="check-for-update" class="btn btn-info">
                <i class="fas fa-cloud-download-alt me-2"></i>
                Buscar Actualizaciones
            </button>
        </div>

        <div id="update-info" class="mt-4" style="display: none;">
            <!-- La información de la actualización se mostrará aquí -->
        </div>
    </div>
</div>

<script src="assets/js/updater.js"></script>

<?php
require_once 'includes/footer.php';
?>
