<?php
require_once 'includes/init.php';
require_once 'config/app_config.php';

$page_title = "Ayuda y Acerca de";
$current_page = "ayuda";

// Leer notas de la versión desde un archivo
$release_notes_file = __DIR__ . '/public/release_notes.md';
$release_notes = 'No hay notas de versión disponibles.';
if (file_exists($release_notes_file)) {
    $release_notes = file_get_contents($release_notes_file);
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="h2"><i class="fas fa-question-circle me-2"></i>Ayuda y Acerca de</h1>
    <p class="text-muted">Información sobre la aplicación, actualizaciones y recursos de ayuda.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Información de la Aplicación</h5>
            </div>
            <div class="card-body">
                <p><strong>Versión Actual:</strong> <span class="badge bg-primary fs-6"><?= htmlspecialchars(APP_VERSION) ?></span></p>
                <p><strong>Notas de la Versión:</strong></p>
                <div class="alert alert-light border">
                    <?php if (!empty($release_notes) && $release_notes != 'No hay notas de versión disponibles.'): ?>
                        <pre style="white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars($release_notes) ?></pre>
                    <?php else: ?>
                        <p class="text-muted">No hay notas de versión disponibles.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Información del Desarrollador</h5>
            </div>
            <div class="card-body">
                <p>Esta aplicación fue desarrollada por [Tu Nombre/Nombre de la Empresa Desarrolladora].</p>
                <p>Para soporte técnico o consultas, puedes contactar a:</p>
                <ul>
                    <li>Email: [tu_email@ejemplo.com]</li>
                    <li>Teléfono: [tu_telefono]</li>
                    <li>Sitio Web: [tu_sitio_web.com]</li>
                </ul>
                <p class="text-muted">© <?= date('Y') ?> [Tu Nombre/Nombre de la Empresa Desarrolladora]. Todos los derechos reservados.</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Manual de Funcionamiento</h5>
            </div>
            <div class="card-body">
                <p>Aquí puedes encontrar el manual completo del sistema, con instrucciones detalladas sobre cómo utilizar cada función.</p>
                <a href="public/manual.pdf" class="btn btn-info" target="_blank"><i class="fas fa-book me-2"></i>Ver Manual (PDF)</a>
                <p class="text-muted mt-2"><em>(Asegúrate de tener un visor de PDF instalado.)</em></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
