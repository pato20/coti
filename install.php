<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- LOCK INSTALLER ---
// If lock file exists, installation is complete. Redirect to main page.
if (file_exists('config/.installed')) {
    header('Location: index.php');
    exit();
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = null;

function execute_sql_file($pdo, $filepath) {
    $sql = file_get_contents($filepath);
    if ($sql === false) {
        throw new Exception("No se pudo leer el archivo SQL.");
    }

    // Clean the SQL file: remove comments and data inserts
    $sql = preg_replace('/^--.*$/m', '', $sql); // Remove SQL comments
    $sql = preg_replace('/LOCK TABLES `.*` WRITE;/m', '', $sql);
    $sql = preg_replace('/UNLOCK TABLES;/m', '', $sql);
    $sql = preg_replace('/INSERT INTO `.*` VALUES .*;/m', '', $sql);

    $queries = explode(';', $sql);

    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
}


// --- Step 2: Process DB Form ---
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];

    try {
        // 1. Test connection
        $dsn = "mysql:host=$db_host";
        $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // 2. Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        
        // 3. Store credentials in session and write config file
        $_SESSION['db_credentials'] = [
            'host' => $db_host,
            'name' => $db_name,
            'user' => $db_user,
            'pass' => $db_pass
        ];

        $config_content = "<?php
// Configuración de la base de datos
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_NAME', '$db_name');

// Conexión PDO
try {
    \$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException \$e) {
    die('Error de conexión a la base de datos: ' . \$e->getMessage());
}
?>";
        
        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }
        file_put_contents('config/database.php', $config_content);

        // Redirect to next step
        header('Location: install.php?step=3');
        exit();

    } catch (PDOException $e) {
        $error = "Error de conexión: " . $e->getMessage();
        $step = 1; // Go back to step 1
    }
}

// --- Step 3: Create Tables ---
if ($step === 3) {
    if (!isset($_SESSION['db_credentials'])) {
        header('Location: install.php?step=1');
        exit();
    }
    try {
        $creds = $_SESSION['db_credentials'];
        $dsn = "mysql:host={$creds['host']};dbname={$creds['name']}";
        $pdo = new PDO($dsn, $creds['user'], $creds['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        execute_sql_file($pdo, 'database/schema.sql');

    } catch (Exception $e) {
        $error = "Error al crear las tablas: " . $e->getMessage();
        // Clean up if failed
        unlink('config/database.php');
        session_destroy();
        $step = 1;
    }
}

// --- Step 4: Process Admin Form ---
if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
     if (!file_exists('config/database.php')) {
        header('Location: install.php?step=1');
        exit();
    }
    include 'config/database.php';

    $fullname = $_POST['admin_fullname'];
    $username = $_POST['admin_username'];
    $email = $_POST['admin_email'];
    $password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (username, email, password, nombre_completo, rol, activo) 
             VALUES (:username, :email, :password, :nombre_completo, 'admin', 1)"
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $password,
            ':nombre_completo' => $fullname
        ]);
        header('Location: install.php?step=5');
        exit();
    } catch (Exception $e) {
        $error = "Error al crear el usuario: " . $e->getMessage();
        $step = 3;
    }
}

// --- Step 5: Process Company Form ---
if ($step === 6 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!file_exists('config/database.php')) {
        header('Location: install.php?step=1');
        exit();
    }
    include 'config/database.php';

    $nombre = $_POST['company_name'];
    $subtitulo = $_POST['company_subtitle'];
    $rut = $_POST['company_rut'];
    $direccion = $_POST['company_address'];
    $telefono = $_POST['company_phone'];
    $email = $_POST['company_email'];
    $logo_path = null;

    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == 0) {
        if (!is_dir('uploads')) {
            mkdir('uploads', 0755, true);
        }
        $target_dir = "uploads/";
        $image_name = "logo_" . basename($_FILES["company_logo"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["company_logo"]["tmp_name"], $target_file)) {
            $logo_path = $target_file;
        }
    }

    try {
        // The schema already inserts a row in `empresa`, so we UPDATE it.
        $stmt = $pdo->prepare(
            "UPDATE empresa SET nombre = :nombre, subtitulo = :subtitulo, rut = :rut, direccion = :direccion, telefono = :telefono, email = :email, logo = :logo WHERE id = 1"
        );
        // If update fails (maybe table was empty), insert.
        if ($stmt->execute([':nombre' => $nombre, ':subtitulo' => $subtitulo, ':rut' => $rut, ':direccion' => $direccion, ':telefono' => $telefono, ':email' => $email, ':logo' => $logo_path]) && $stmt->rowCount() == 0) {
             $stmt = $pdo->prepare(
                "INSERT INTO empresa (id, nombre, subtitulo, rut, direccion, telefono, email, logo) 
                 VALUES (1, :nombre, :subtitulo, :rut, :direccion, :telefono, :email, :logo)"
            );
            $stmt->execute([':nombre' => $nombre, ':subtitulo' => $subtitulo, ':rut' => $rut, ':direccion' => $direccion, ':telefono' => $telefono, ':email' => $email, ':logo' => $logo_path]);
        }
        
        header('Location: install.php?step=7');
        exit();

    } catch (Exception $e) {
        $error = "Error al guardar la información de la empresa: " . $e->getMessage();
        $step = 5;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación del Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .progress-bar {
            width: <?= ($step / 7) * 100 ?>%;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1>Instalación del Sistema</h1>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" aria-valuenow="<?= $step ?>" aria-valuemin="1" aria-valuemax="7"></div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php // --- Step 1: DB Form --- ?>
                    <?php if ($step === 1): ?>
                        <h2>Paso 1: Configuración de la Base de Datos</h2>
                        <p>Ingresa los datos de conexión a tu base de datos MySQL.</p>
                        <form action="install.php?step=2" method="POST">
                            <div class="mb-3">
                                <label for="db_host" class="form-label">Servidor (Host)</label>
                                <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                            </div>
                            <div class="mb-3">
                                <label for="db_name" class="form-label">Nombre de la Base de Datos</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="db_user" class="form-label">Usuario</label>
                                <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                            </div>
                            <div class="mb-3">
                                <label for="db_pass" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="db_pass" name="db_pass">
                            </div>
                            <button type="submit" class="btn btn-primary">Continuar</button>
                        </form>
                    <?php endif; ?>

                    <?php // --- Step 2 is processing --- ?>
                    
                    <?php // --- Step 3: Admin Form --- ?>
                    <?php if ($step === 3): ?>
                        <h2>Paso 2: Crear Usuario Administrador</h2>
                        <div class="alert alert-success">¡Base de datos configurada y tablas creadas!</div>
                        <p>Ahora, crea la cuenta principal para administrar el sistema.</p>
                        <form action="install.php?step=4" method="POST">
                            <div class="mb-3">
                                <label for="admin_fullname" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="admin_fullname" name="admin_fullname" required>
                            </div>
                            <div class="mb-3">
                                <label for="admin_username" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                            </div>
                            <div class="mb-3">
                                <label for="admin_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                            </div>
                            <div class="mb-3">
                                <label for="admin_password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Crear Usuario y Continuar</button>
                        </form>
                    <?php endif; ?>

                     <?php // --- Step 4 is processing --- ?>

                    <?php // --- Step 5: Company Form --- ?>
                    <?php if ($step === 5): ?>
                        <h2>Paso 3: Información de la Empresa</h2>
                        <div class="alert alert-success">¡Usuario administrador creado!</div>
                        <p>Ingresa la información general de tu empresa.</p>
                        <form action="install.php?step=6" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Nombre de la Empresa</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="company_subtitle" class="form-label">Subtítulo (Opcional)</label>
                                <input type="text" class="form-control" id="company_subtitle" name="company_subtitle">
                            </div>
                             <div class="mb-3">
                                <label for="company_rut" class="form-label">RUT/ID</label>
                                <input type="text" class="form-control" id="company_rut" name="company_rut">
                            </div>
                             <div class="mb-3">
                                <label for="company_address" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="company_address" name="company_address">
                            </div>
                            <div class="mb-3">
                                <label for="company_phone" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="company_phone" name="company_phone">
                            </div>
                            <div class="mb-3">
                                <label for="company_email" class="form-label">Email de Contacto</label>
                                <input type="email" class="form-control" id="company_email" name="company_email">
                            </div>
                            <div class="mb-3">
                                <label for="company_logo" class="form-label">Logo (Opcional)</label>
                                <input type="file" class="form-control" id="company_logo" name="company_logo">
                            </div>
                            <button type="submit" class="btn btn-primary">Finalizar Instalación</button>
                        </form>
                    <?php endif; ?>

                    <?php // --- Step 6 is processing --- ?>

                    <?php // --- Step 7: Finish --- ?>
                    <?php if ($step === 7): ?>
                        <div class="text-center">
                            <h2>✅ ¡Instalación Completada!</h2>
                            <p>El sistema ha sido instalado y configurado correctamente.</p>
                            <p><strong>Por seguridad, el instalador ha sido bloqueado.</strong></p>
                            <a href="login.php" class="btn btn-success">Ir al Login</a>
                        </div>
                        <?php 
                            if (!is_dir('config')) { mkdir('config', 0755, true); }
                            file_put_contents('config/.installed', 'Installation completed on ' . date('Y-m-d H:i:s'));
                            session_destroy(); 
                        ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>