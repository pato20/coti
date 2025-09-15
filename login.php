<?php
session_start();
require_once 'config/database.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, email, password, nombre_completo, rol, activo FROM usuarios WHERE (username = ? OR email = ?) AND activo = 1");
            $stmt->execute([$username, $username]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($password, $usuario['password'])) {
                // Login exitoso
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['username'] = $usuario['username'];
                $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
                $_SESSION['rol'] = $usuario['rol'];
                
                // Actualizar último acceso
                $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                $stmt->execute([$usuario['id']]);
                
                // Crear sesión en BD
                $session_id = session_id();
                $stmt = $pdo->prepare("INSERT INTO sesiones (id, usuario_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))");
                $stmt->execute([$session_id, $usuario['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión. Intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Cotización</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.07);
            max-width: 420px;
            width: 100%;
            padding: 2.5rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header .icon {
            background-color: #eef2ff;
            color: #4f46e5;
            height: 60px;
            width: 60px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .login-header h3 {
            font-weight: 700;
            color: #111827;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
        }
        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.2);
        }
        .btn-login {
            background-color: #2c3e50; /* Color del sidebar */
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-weight: 600;
            width: 100%;
            color: white;
        }
        .btn-login:hover {
            background-color: #34495e; /* Color del sidebar más claro */
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-bolt fa-3x mb-3"></i>
            <h3 class="mb-0">Sistema de Cotización</h3>
            <p class="mb-0 opacity-75">Cercos Eléctricos</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Usuario o Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-user text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" name="username" 
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-lock text-muted"></i>
                        </span>
                        <input type="password" class="form-control border-start-0" name="password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    Usuario demo: <strong>admin</strong> | Contraseña: <strong>admin123</strong>
                </small>
            </div>
        </div>
    </div>
</body>
</html>
