<?php
// El título y la página actual son definidos en el archivo principal antes de incluir este header
$page_title = isset($page_title) ? $page_title : 'Sistema de Cotización';
$current_page = isset($current_page) ? $current_page : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Mobile Header -->
    <header class="d-md-none navbar navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="index.php">CyC Electric</a>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php
                // Verificar si hay actualizaciones disponibles en cada carga de página
                if (function_exists('check_for_updates')) {
                    check_for_updates();
                }
                ?>
                <?php if (isset($_SESSION['update_available']) && $_SESSION['update_available'] && is_admin()): ?>
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-gift me-2"></i>
                        <strong>¡Nueva versión disponible!</strong> La versión <?= htmlspecialchars($_SESSION['update_info']['remote_version']) ?> está lista.
                        <a href="updater.php" class="alert-link fw-bold">Ir al actualizador</a> para instalar.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>