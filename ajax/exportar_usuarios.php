<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

verificarAuth();
verificarRol(['admin']);

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="usuarios_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

$database = new Database();
$db = $database->getConnection();

// Consulta de usuarios
$query = "SELECT u.id, u.username, u.email, u.nombre_completo, u.rol, 
          CASE WHEN u.activo = 1 THEN 'Activo' ELSE 'Inactivo' END as estado,
          DATE_FORMAT(u.created_at, '%d/%m/%Y') as fecha_registro,
          DATE_FORMAT(u.ultimo_acceso, '%d/%m/%Y %H:%i') as ultimo_acceso
          FROM usuarios u 
          ORDER BY u.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generar tabla HTML para Excel
echo '<table border="1">';
echo '<tr>';
echo '<th>ID</th>';
echo '<th>Usuario</th>';
echo '<th>Email</th>';
echo '<th>Nombre Completo</th>';
echo '<th>Rol</th>';
echo '<th>Estado</th>';
echo '<th>Fecha Registro</th>';
echo '<th>Último Acceso</th>';
echo '</tr>';

foreach($usuarios as $usuario) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($usuario['id']) . '</td>';
    echo '<td>' . htmlspecialchars($usuario['username']) . '</td>';
    echo '<td>' . htmlspecialchars($usuario['email']) . '</td>';
    echo '<td>' . htmlspecialchars($usuario['nombre_completo']) . '</td>';
    echo '<td>' . ucfirst($usuario['rol']) . '</td>';
    echo '<td>' . $usuario['estado'] . '</td>';
    echo '<td>' . $usuario['fecha_registro'] . '</td>';
    echo '<td>' . ($usuario['ultimo_acceso'] ?? 'Nunca') . '</td>';
    echo '</tr>';
}

echo '</table>';
?>
