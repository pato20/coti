<?php
require_once 'config/database.php';

try {
    // Verificar si el usuario admin existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    $admin_exists = $stmt->fetch();
    
    // Hash correcto para la contraseña "admin123"
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    if ($admin_exists) {
        // Actualizar contraseña del admin existente
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE username = 'admin'");
        $stmt->execute([$password_hash]);
        echo "Contraseña del usuario admin actualizada correctamente.<br>";
    } else {
        // Crear usuario admin
        $stmt = $pdo->prepare("INSERT INTO usuarios (username, email, password, nombre_completo, rol) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@cercoselectricos.cl', $password_hash, 'Administrador Sistema', 'admin']);
        echo "Usuario admin creado correctamente.<br>";
    }
    
    echo "Usuario: admin<br>";
    echo "Contraseña: admin123<br>";
    echo "<a href='login.php'>Ir al Login</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
