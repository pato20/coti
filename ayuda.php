<?php
require_once 'includes/init.php';
require_once 'config/app_config.php';

// Solo para administradores (o si quieres que sea público, quita esta línea)
// verificarRol(['admin', 'vendedor', 'tecnico']); // Ajusta según quién debe ver la ayuda

$page_title = "Ayuda y Acerca de";
$current_page = "ayuda";

// Obtener notas de la versión desde la configuración
$stmt_notes = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'app_release_notes'");
$stmt_notes->execute();
$release_notes = $stmt_notes->fetchColumn() ?? 'No hay notas de versión disponibles.';

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
                <p><strong>Última Actualización:</strong></p>
                <div class="alert alert-light border">
                    <?php if (!empty($release_notes) && $release_notes != 'No hay notas de versión disponibles.'): ?>
                        <pre style="white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars($release_notes) ?></pre>
                    <?php else: ?>
                        <p class="text-muted">No hay notas de versión disponibles para esta instalación. Las notas se mostrarán aquí después de la primera actualización.</p>
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
                <a href="public/manual.md" class="btn btn-info" target="_blank"><i class="fas fa-book me-2"></i>Descargar Manual (Markdown)</a>
                <p class="text-muted mt-2"><em>(El manual está en formato Markdown. Puedes abrirlo con cualquier editor de texto o visor de Markdown.)</em></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
