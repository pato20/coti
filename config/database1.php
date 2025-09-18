<?php
// --- URL de la Aplicación ---
// IMPORTANTE: Cambiar por la URL pública de la aplicación para que los enlaces de WhatsApp funcionen.
// Ejemplo: https://www.suempresa.com/cerco
define('APP_URL', 'http://localhost/cerco');

// Configuración de la Base de Datos

$host = 'localhost';
$db_name = 'cerco_electrico_db_2';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// Opciones de configuración para PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa preparaciones nativas de la BD
];

// Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

try {
    // Crear la instancia de PDO
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // Si la conexión falla, muestra un error y detiene la ejecución
    // En un entorno de producción, esto debería registrarse en un archivo de log
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>