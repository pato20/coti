<?php
session_start();

// 1. Cargar dependencias y configuración
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php'; // Contiene las credenciales de la BD ($host, $db_name, $username, $password)
require_once ROOT_PATH . '/includes/auth.php';

// --- CONFIGURACIÓN IMPORTANTE ---
// Si XAMPP no está instalado en C:\xampp, ajusta esta ruta.
$mysql_bin_path = 'C:\\xampp\\mysql\\bin\\';

// 2. Verificación de seguridad: Solo administradores pueden acceder
verificarAuth();
verificarRol(['admin'], true); // El 'true' hace que la respuesta sea JSON en caso de error

$action = $_GET['action'] ?? '';

// 3. Lógica principal según la acción solicitada
switch ($action) {
    case 'crear':
        crearBackup(DB_HOST, DB_USER, DB_PASS, DB_NAME, $mysql_bin_path);
        break;

    case 'restaurar':
        restaurarBackup(DB_HOST, DB_USER, DB_PASS, DB_NAME, $mysql_bin_path);
        break;

    default:
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        exit;
}

/**
 * Crea un backup de la base de datos usando mysqldump y lo envía para descarga.
 */
function crearBackup($host, $user, $pass, $db_name, $mysql_bin_path) {
    try {
        $backup_file = 'backup-' . $db_name . '-' . date("Y-m-d-H-i-s") . '.sql';
        $backup_path = ROOT_PATH . '/database/' . $backup_file;

        if (!is_dir(dirname($backup_path))) {
            mkdir(dirname($backup_path), 0755, true);
        }

        // Construir el comando mysqldump con la ruta explícita
        $mysqldump_cmd = escapeshellarg($mysql_bin_path . 'mysqldump.exe');
        $command = sprintf('%s --host=%s --user=%s ', $mysqldump_cmd, escapeshellarg($host), escapeshellarg($user));
        if (!empty($pass)) {
            $command .= sprintf('--password=%s ', escapeshellarg($pass));
        }
        $command .= sprintf('%s > %s', escapeshellarg($db_name), escapeshellarg($backup_path));

        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            throw new Exception("Error al crear el backup con mysqldump. Código de retorno: $return_var. Salida: " . implode("\n", $output));
        }

        if (!file_exists($backup_path)) {
            throw new Exception("El archivo de backup no fue creado.");
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($backup_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($backup_path));
        
        ob_clean();
        flush();
        readfile($backup_path);

        unlink($backup_path);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        die("Error al crear el backup: " . $e->getMessage());
    }
}

/**
 * Restaura la base de datos desde un archivo .sql subido.
 */
function restaurarBackup($host, $user, $pass, $db_name, $mysql_bin_path) {
    header('Content-Type: application/json');

    try {
        if (!isset($_FILES['backupFile']) || $_FILES['backupFile']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error en la subida del archivo o archivo no proporcionado.');
        }

        $file_path = $_FILES['backupFile']['tmp_name'];
        $file_name = $_FILES['backupFile']['name'];

        if (pathinfo($file_name, PATHINFO_EXTENSION) !== 'sql') {
            throw new Exception('El archivo debe ser de tipo .sql');
        }

        // Construir el comando mysql para la importación con la ruta explícita
        $mysql_cmd = escapeshellarg($mysql_bin_path . 'mysql.exe');
        $command = sprintf('%s --host=%s --user=%s ', $mysql_cmd, escapeshellarg($host), escapeshellarg($user));
        if (!empty($pass)) {
            $command .= sprintf('--password=%s ', escapeshellarg($pass));
        }
        $command .= sprintf('%s < %s', escapeshellarg($db_name), escapeshellarg($file_path));

        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            throw new Exception("Error al restaurar la base de datos. Código de retorno: $return_var. Salida: " . implode("\n", $output));
        }

        echo json_encode(['success' => true, 'message' => 'Base de datos restaurada exitosamente.']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}