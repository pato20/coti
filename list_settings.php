<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT clave, valor, descripcion FROM configuracion ORDER BY clave");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Configuraciones actuales en la base de datos:\n";
    foreach ($settings as $setting) {
        echo "  - Clave: " . $setting['clave'] . ", Valor: " . $setting['valor'] . ", Descripción: " . $setting['descripcion'] . "\n";
    }
} catch (Exception $e) {
    echo "Error al listar configuraciones: " . $e->getMessage() . "\n";
}
?>