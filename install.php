<?php
// Script de instalación para crear la base de datos y tablas
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Conectar sin especificar base de datos
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Leer y ejecutar el archivo SQL
    $sql = file_get_contents('database/schema.sql');
    
    // Dividir en consultas individuales
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    
    echo "<h2>✅ Base de datos instalada correctamente</h2>";
    echo "<p>La base de datos 'cerco_electrico_db' y todas las tablas han sido creadas.</p>";
    echo "<p><a href='index.php' class='btn btn-primary'>Ir al Sistema</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error en la instalación</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema de Cotización</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h1>Instalación del Sistema</h1>
                        <p>Ejecuta este archivo una sola vez para crear la base de datos.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
