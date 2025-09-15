<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$cotizacion_id = $input['cotizacion_id'] ?? null;

if (!$cotizacion_id) {
    echo json_encode(['success' => false, 'message' => 'ID de cotización no proporcionado.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // 1. Cambiar estado de la cotización a 'aceptada'
    $query = "UPDATE cotizaciones SET estado = 'aceptada' WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$cotizacion_id]);

    // 2. Verificar si ya existe una orden para esta cotización
    $query = "SELECT id FROM ordenes_trabajo WHERE cotizacion_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$cotizacion_id]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe una orden de trabajo para esta cotización.');
    }

    // 3. Obtener datos de la cotización para crear la orden
    $query = "SELECT cliente_id FROM cotizaciones WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$cotizacion_id]);
    $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
    $cliente_id = $cotizacion['cliente_id'];

    // 4. Crear la orden de trabajo
    $numero_orden = generateWorkOrderNumber();
    $query = "INSERT INTO ordenes_trabajo (numero_orden, cotizacion_id, cliente_id, fecha_inicio, estado) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$numero_orden, $cotizacion_id, $cliente_id, date('Y-m-d'), 'pendiente']);
    $orden_id = $db->lastInsertId();

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Orden de trabajo creada exitosamente.', 'orden_id' => $orden_id]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>