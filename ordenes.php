<?php
require_once 'includes/init.php';

$page_title = "Órdenes de Trabajo";
$current_page = "ordenes";

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Procesar formularios POST para actualizaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action == 'update_progress') {
    try {
        $orden_id = $_POST['orden_id'];
        $nuevo_porcentaje = floatval($_POST['porcentaje']);
        $descripcion = $_POST['descripcion'];
        $estado = $_POST['estado'];
        
        $pdo->beginTransaction();

        $query = "SELECT porcentaje_avance FROM ordenes_trabajo WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$orden_id]);
        $porcentaje_anterior = $stmt->fetchColumn();
        
        $query = "UPDATE ordenes_trabajo SET porcentaje_avance = ?, estado = ? WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$nuevo_porcentaje, $estado, $orden_id]);
        
        if ($estado == 'completada') {
            $query = "UPDATE ordenes_trabajo SET fecha_real_fin = CURDATE() WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$orden_id]);
        }
        
        $query = "INSERT INTO orden_seguimiento (orden_id, fecha, porcentaje_anterior, porcentaje_actual, descripcion, usuario) VALUES (?, CURDATE(), ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$orden_id, $porcentaje_anterior, $nuevo_porcentaje, $descripcion, $_SESSION['nombre_completo'] ?? 'Sistema']);
        
        $pdo->commit();
        header("Location: ordenes.php?action=view&id=$orden_id&success=updated");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        // Guardar el error en una variable de sesión para mostrarlo después de la redirección
        $_SESSION['error_message'] = "Error al actualizar: " . $e->getMessage();
        header("Location: ordenes.php?action=view&id={\$_POST['orden_id']}");
        exit;
    }
}

require_once 'includes/header.php';
?>

<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;"></div>

<?php
// Mostrar errores o éxitos de la sesión
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i>' . $_SESSION['error_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['error_message']);
}
if (isset($_GET['success'])) {
    $message = '';
    if ($_GET['success'] == 'updated') $message = 'Progreso actualizado exitosamente.';
    if ($_GET['success'] == 'created') $message = 'Orden de trabajo creada exitosamente.';
    if ($message) {
        echo '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>' . $message . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

if ($action == 'view' && $id) {
    // Cargar datos para la vista de detalle
    $query = "SELECT ot.*, c.numero_cotizacion, cl.nombre as cliente_nombre, cl.telefono, cl.email, cec.metros_lineales, cec.numero_hilos, cec.tipo_instalacion\n              FROM ordenes_trabajo ot\n              LEFT JOIN cotizaciones c ON ot.cotizacion_id = c.id\n              LEFT JOIN clientes cl ON ot.cliente_id = cl.id\n              LEFT JOIN cerco_electrico_config cec ON c.id = cec.cotizacion_id\n              WHERE ot.id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $orden = $stmt->fetch();

    if (!$orden) {
        echo "<div class='alert alert-danger'>Orden no encontrada.</div>";
    } else {
        $pagos_stmt = $pdo->prepare("SELECT * FROM pagos WHERE orden_id = ? ORDER BY fecha_pago DESC");
        $pagos_stmt->execute([$id]);
        $pagos = $pagos_stmt->fetchAll();

        $seguimientos_stmt = $pdo->prepare("SELECT * FROM orden_seguimiento WHERE orden_id = ? ORDER BY fecha DESC, created_at DESC");
        $seguimientos_stmt->execute([$id]);
        $seguimientos = $seguimientos_stmt->fetchAll();
        
        include 'includes/templates/orden_view.php';
    }
} else {
    include 'includes/templates/orden_list.php';
}
?>

<script src="assets/js/ordenes.js"></script>

<?php
require_once 'includes/footer.php';
?>