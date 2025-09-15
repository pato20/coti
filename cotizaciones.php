<?php
require_once 'includes/init.php';

$page_title = "Cotizaciones";
$current_page = "cotizaciones";

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Procesar formularios
if ($_POST && $action == 'save') {
    $is_update = isset($_POST['cotizacion_id']) && !empty($_POST['cotizacion_id']);
    
    try {
        $pdo->beginTransaction();

        if ($is_update) {
            $cotizacion_id = $_POST['cotizacion_id'];
            $descuento_general = isset($_POST['activar_descuento_general']) ? floatval($_POST['descuento_general']) : 0;
            $query = "UPDATE cotizaciones SET cliente_id=?, fecha_cotizacion=?, fecha_vencimiento=?, observaciones=?, con_iva=?, descuento_general=? WHERE id=?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $_POST['cliente_id'], $_POST['fecha_cotizacion'], $_POST['fecha_vencimiento'],
                $_POST['observaciones'], isset($_POST['activar_iva']) ? 1 : 0, $descuento_general, $cotizacion_id
            ]);
            $stmt = $pdo->prepare("DELETE FROM cotizacion_detalles WHERE cotizacion_id = ?");
            $stmt->execute([$cotizacion_id]);
            $stmt = $pdo->prepare("DELETE FROM cerco_electrico_config WHERE cotizacion_id = ?");
            $stmt->execute([$cotizacion_id]);
        } else {
            $numero_cotizacion = generateQuoteNumber();
            $descuento_general = isset($_POST['activar_descuento_general']) ? floatval($_POST['descuento_general']) : 0;
            $query = "INSERT INTO cotizaciones (numero_cotizacion, cliente_id, fecha_cotizacion, fecha_vencimiento, observaciones, con_iva, descuento_general) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $numero_cotizacion, $_POST['cliente_id'], $_POST['fecha_cotizacion'],
                $_POST['fecha_vencimiento'], $_POST['observaciones'], isset($_POST['activar_iva']) ? 1 : 0, $descuento_general
            ]);
            $cotizacion_id = $pdo->lastInsertId();
        }

        if (isset($_POST['es_cerco_electrico']) && $_POST['es_cerco_electrico'] == '1') {
            $metros = floatval($_POST['metros_lineales']);
            $instalacion = $_POST['tipo_instalacion'];
            $hilos = $_POST['numero_hilos'];

            $query_cerco = "INSERT INTO cerco_electrico_config (cotizacion_id, metros_lineales, tipo_instalacion, numero_hilos) VALUES (?, ?, ?, ?)";
            $stmt_cerco = $pdo->prepare($query_cerco);
            $stmt_cerco->execute([$cotizacion_id, $metros, $instalacion, $hilos]);

            // Calcular precio y añadir como item
            $precio_cerco = calculateCercoElectricoPrice($hilos, $instalacion, $metros);
            $descripcion_cerco = "Instalación de cerco eléctrico ($metros mts, $hilos hilos, instalación $instalacion)";
            
            $query_item = "INSERT INTO cotizacion_detalles (cotizacion_id, producto_servicio_id, cantidad, precio_unitario, subtotal, descripcion_adicional) VALUES (?, NULL, ?, ?, ?, ?)";
            $stmt_item = $pdo->prepare($query_item);
            $stmt_item->execute([$cotizacion_id, 1, $precio_cerco, $precio_cerco, $descripcion_cerco]);
        }
        
        if (isset($_POST['productos']) && is_array($_POST['productos'])) {
            foreach ($_POST['productos'] as $index => $producto_id) {
                if (!empty($producto_id)) {
                    // Obtener el precio_base actual del producto/servicio
                    $stmt_precio_base = $pdo->prepare("SELECT precio_base FROM productos_servicios WHERE id = ?");
                    $stmt_precio_base->execute([$producto_id]);
                    $precio_base_historico = $stmt_precio_base->fetchColumn();

                    $cantidad = floatval($_POST['cantidades'][$index]);
                    $precio = floatval($_POST['precios'][$index]);
                    $descuento_item = floatval($_POST['descuentos_item'][$index] ?? 0);
                    $subtotal = $cantidad * $precio;
                    if ($descuento_item > 0) {
                        $subtotal = $subtotal * (1 - ($descuento_item / 100));
                    }
                    $query = "INSERT INTO cotizacion_detalles (cotizacion_id, producto_servicio_id, cantidad, precio_unitario, subtotal, descuento_item, precio_base_historico) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$cotizacion_id, $producto_id, $cantidad, $precio, $subtotal, $descuento_item, $precio_base_historico]);
                }
            }
        }

        if (isset($_POST['generic_description']) && is_array($_POST['generic_description'])) {
            foreach ($_POST['generic_description'] as $index => $description) {
                if (!empty($description)) {
                    $cantidad = floatval($_POST['generic_quantity'][$index]);
                    $precio = floatval($_POST['generic_price'][$index]);
                    $descuento_item = floatval($_POST['generic_descuentos_item'][$index] ?? 0);
                    $subtotal = $cantidad * $precio;
                    if ($descuento_item > 0) {
                        $subtotal = $subtotal * (1 - ($descuento_item / 100));
                    }
                    $query = "INSERT INTO cotizacion_detalles (cotizacion_id, producto_servicio_id, cantidad, precio_unitario, subtotal, descripcion_adicional, descuento_item, precio_base_historico) VALUES (?, NULL, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$cotizacion_id, $cantidad, $precio, $subtotal, $description, $descuento_item, $precio]); // Para genéricos, precio_base_historico es el precio ingresado
                }
            }
        }
        
        // Recalcular totales y commit
        recalculateQuoteTotals($cotizacion_id);
        $pdo->commit();
        header("Location: cotizaciones.php?success=1");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al guardar: " . $e->getMessage();
        $action = $is_update ? 'edit' : 'new';
    }
}

$cotizacion_edit = null;
$detalles_edit = [];
$detalles_genericos_edit = [];
$cerco_edit = null;
if ($action == 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
    $stmt->execute([$id]);
    $cotizacion_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cotizacion_edit) {
        $stmt_det = $pdo->prepare("SELECT * FROM cotizacion_detalles WHERE cotizacion_id = ? AND producto_servicio_id IS NOT NULL");
        $stmt_det->execute([$id]);
        $detalles_edit = $stmt_det->fetchAll(PDO::FETCH_ASSOC);
        $stmt_gen = $pdo->prepare("SELECT * FROM cotizacion_detalles WHERE cotizacion_id = ? AND producto_servicio_id IS NULL");
        $stmt_gen->execute([$id]);
        $detalles_genericos_edit = $stmt_gen->fetchAll(PDO::FETCH_ASSOC);
        $stmt_cerco = $pdo->prepare("SELECT * FROM cerco_electrico_config WHERE cotizacion_id = ?");
        $stmt_cerco->execute([$id]);
        $cerco_edit = $stmt_cerco->fetch(PDO::FETCH_ASSOC);
    } else {
        header("Location: cotizaciones.php?error=notfound");
        exit;
    }
}

$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$productos_servicios = $pdo->query("SELECT * FROM productos_servicios WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;"></div>

<?php if ($action == 'new' || $action == 'edit'): ?>
    <?php include 'includes/templates/cotizacion_form.php'; ?>
<?php else: ?>
    <?php include 'includes/templates/cotizacion_list.php'; ?>
<?php endif; ?>

<script src="assets/js/cotizaciones.js"></script>

<?php require_once 'includes/footer.php'; ?>