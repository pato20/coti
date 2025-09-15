<?php
require_once '../config/database.php';
require_once '../includes/email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$tipo = $input['tipo'] ?? null;

try {
    if ($tipo === 'cotizacion') {
        $id = $input['cotizacion_id'] ?? null;
        if (!$id) throw new Exception('ID de cotización requerido');

        $stmt = $pdo->prepare("SELECT c.*, cl.nombre as cliente_nombre, cl.email as cliente_email, e.nombre as empresa_nombre FROM cotizaciones c JOIN clientes cl ON c.cliente_id = cl.id JOIN empresa e ON e.id = 1 WHERE c.id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) throw new Exception('Cotización no encontrada');

        $to = $data['cliente_email'];
        $subject = "Cotización N° " . $data['numero_cotizacion'] . " - " . $data['empresa_nombre'];
        $message = "
            <html><body>
                <p>Estimado/a " . htmlspecialchars($data['cliente_nombre']) . ",</p>
                <p>Adjuntamos la cotización N° <strong>" . htmlspecialchars($data['numero_cotizacion']) . "</strong> solicitada para su revisión.</p>
                <p>Puede verla en línea aquí: <a href='http://" . $_SERVER['HTTP_HOST'] . "/cerco/cotizacion_pdf.php?id=" . $id . "'>Ver Cotización</a></p>
                <p>Saludos cordiales,<br>" . htmlspecialchars($data['empresa_nombre']) . "</p>
            </body></html>
        ";
        
        $result = enviarEmail($to, $subject, $message);

    } elseif ($tipo === 'orden') {
        $id = $input['orden_id'] ?? null;
        if (!$id) throw new Exception('ID de orden requerido');

        $stmt = $pdo->prepare("SELECT o.*, cl.nombre as cliente_nombre, cl.email as cliente_email, e.nombre as empresa_nombre FROM ordenes_trabajo o JOIN clientes cl ON o.cliente_id = cl.id JOIN empresa e ON e.id = 1 WHERE o.id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) throw new Exception('Orden de trabajo no encontrada');

        $to = $data['cliente_email'];
        $subject = "Orden de Trabajo N° " . $data['numero_orden'] . " - " . $data['empresa_nombre'];
        $message = "
            <html><body>
                <p>Estimado/a " . htmlspecialchars($data['cliente_nombre']) . ",</p>
                <p>Le informamos sobre su Orden de Trabajo N° <strong>" . htmlspecialchars($data['numero_orden']) . "</strong>.</p>
                <p>Puede ver el detalle en línea aquí: <a href='http://" . $_SERVER['HTTP_HOST'] . "/cerco/orden_pdf.php?id=" . $id . "'>Ver Orden de Trabajo</a></p>
                <p>Saludos cordiales,<br>" . htmlspecialchars($data['empresa_nombre']) . "</p>
            </body></html>
        ";
        
        $result = enviarEmail($to, $subject, $message);

    } else {
        throw new Exception('Tipo de documento no válido');
    }

    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'Email enviado exitosamente']);
    } else {
        throw new Exception($result['message']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>