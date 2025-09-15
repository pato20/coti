<?php
require_once 'includes/init.php';

$current_page = "agenda";
$page_title = "Agenda";
$user_id = $_SESSION['usuario_id'];
$user_rol = $_SESSION['rol'];

// Endpoint de eventos para FullCalendar
if (isset($_GET['action']) && $_GET['action'] == 'get_events') {
    header('Content-Type: application/json');
    $start = $_GET['start'] ?? date('Y-m-01');
    $end = $_GET['end'] ?? date('Y-m-t');
    
    $query = "SELECT id, titulo as title, fecha_hora_inicio as start, fecha_hora_fin as end, tipo FROM agenda WHERE fecha_hora_inicio BETWEEN ? AND ?";
    $params = [$start, $end];
    if ($user_rol != 'admin') {
        $query .= " AND usuario_id = ?";
        $params[] = $user_id;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Lógica para manejar POST (Crear/Actualizar/Eliminar eventos)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'save') {
            $evento_id = $_POST['id'] ?? null;
            $asignado_a = ($user_rol == 'admin' && !empty($_POST['usuario_id'])) ? $_POST['usuario_id'] : $user_id;
            if ($evento_id) {
                $stmt = $pdo->prepare("UPDATE agenda SET usuario_id=?, cliente_id=?, titulo=?, descripcion=?, fecha_hora_inicio=?, tipo=? WHERE id=?");
                $stmt->execute([$asignado_a, $_POST['cliente_id'], $_POST['titulo'], $_POST['descripcion'], $_POST['fecha_hora_inicio'], $_POST['tipo'], $evento_id]);
                $_SESSION['success_message'] = "Visita actualizada.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO agenda (usuario_id, cliente_id, titulo, descripcion, fecha_hora_inicio, tipo) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$asignado_a, $_POST['cliente_id'], $_POST['titulo'], $_POST['descripcion'], $_POST['fecha_hora_inicio'], $_POST['tipo']]);
                $_SESSION['success_message'] = "Visita agendada.";
            }
        } elseif ($_POST['action'] === 'delete') {
            $pdo->prepare("DELETE FROM agenda WHERE id = ?")->execute([$_POST['id']]);
            $_SESSION['success_message'] = "Visita eliminada.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    header("Location: agenda.php");
    exit;
}

// Obtener datos para la vista
$clientes = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetchAll();
$usuarios = ($user_rol == 'admin') ? $pdo->query("SELECT id, nombre_completo FROM usuarios WHERE activo=1 ORDER BY nombre_completo")->fetchAll() : [];

require_once 'includes/header.php';
?>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />
<style>
    #calendar { max-width: 1100px; margin: 0 auto; }
    .fc-event { cursor: pointer; }
    .view-toggle .btn.active { background-color: #2c3e50; color: white; }
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2"><i class="fas fa-calendar-alt me-2"></i>Agenda</h1>
        </div>
        <div>
            <div class="btn-group view-toggle me-2">
                <button type="button" class="btn btn-outline-secondary active" id="btn-list-view"><i class="fas fa-list"></i> Lista</button>
                <button type="button" class="btn btn-outline-secondary" id="btn-calendar-view"><i class="fas fa-calendar-days"></i> Calendario</button>
            </div>
            <button type="button" class="btn btn-primary" onclick="abrirModalEvento()"><i class="fas fa-plus me-2"></i> Nueva Visita</button>
        </div>
    </div>
</div>

<?php 
if(isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>'; unset($_SESSION['success_message']); } 
if(isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>'; unset($_SESSION['error_message']); }
?>

<div id="list-view">
    <?php include 'includes/templates/agenda_list.php'; ?>
</div>

<div id="calendar-view" style="display: none;">
    <div class="card"><div class="card-body"><div id="calendar"></div></div></div>
</div>

<!-- Modals -->
<?php include 'includes/templates/agenda_modals.php'; ?>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script src="assets/js/agenda.js"></script>

<?php require_once 'includes/footer.php'; ?>