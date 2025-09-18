<?php
require_once '../includes/init.php';

header('Content-Type: application/json');

// Validar que los datos necesarios fueron enviados
if (!isset($_POST['metros'], $_POST['hilos'], $_POST['instalacion'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

$metros = (float)$_POST['metros'];
$hilos = $_POST['hilos'];
$instalacion = $_POST['instalacion'];

if ($metros <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Los metros deben ser un número mayor a cero.']);
    exit;
}

// Llamar a la función de cálculo original (hardcoded)
$precio_calculado = calculateCercoElectricoPrice($hilos, $instalacion, $metros);

// Devolver el resultado
echo json_encode([
    'success' => true,
    'precio' => $precio_calculado
]);
?>
