<?php
require_once 'includes/init.php';

// 1. Verificación de seguridad: Rol de Administrador
verificarRol(['admin']);

$page_title = "Configuración";
$current_page = "configuracion";

$success = null;
$error = null;

// 2. Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Obtener el path del logo actual para borrarlo si se sube uno nuevo
        $stmt = $pdo->prepare("SELECT logo FROM empresa WHERE id = 1");
        $stmt->execute();
        $current_logo = $stmt->fetchColumn();
        $logo_path = $current_logo;

        // Manejo de la subida del logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            // Borrar el logo antiguo si existe y no es un placeholder
            if ($current_logo && file_exists($current_logo) && strpos($current_logo, 'placeholder') === false) {
                unlink($current_logo);
            }

            $file_name = 'logo-' . uniqid() . '-' . basename($_FILES['logo']['name']);
            $logo_path = $upload_dir . $file_name;
            
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
                throw new Exception("No se pudo mover el archivo subido.");
            }
        }

        // Actualizar la base de datos
        $query = "UPDATE empresa SET nombre = ?, rut = ?, direccion = ?, telefono = ?, email = ?, whatsapp = ?, logo = ? WHERE id = 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $_POST['nombre'] ?? '',
            $_POST['rut'] ?? '',
            $_POST['direccion'] ?? '',
            $_POST['telefono'] ?? '',
            $_POST['email'] ?? '',
            $_POST['whatsapp'] ?? '',
            $logo_path
        ]);

        $success = "Información de la empresa actualizada exitosamente.";

    } catch (Exception $e) {
        $error = "Error al actualizar: " . $e->getMessage();
    }
}

// 3. Obtener los datos actuales de la empresa
$empresa = $pdo->query("SELECT * FROM empresa WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="h2"><i class="fas fa-building me-2"></i>Configuración de la Empresa</h1>
    <p class="text-muted">Actualiza los datos que se usarán en cotizaciones y documentos.</p>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="card mb-4">
        <div class="card-header"><h5 class="card-title mb-0">Datos Generales</h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3"><label class="form-label">Nombre de la Empresa *</label><input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($empresa['nombre'] ?? ''); ?>"></div>
                    <div class="mb-3"><label class="form-label">RUT</label><input type="text" name="rut" class="form-control" value="<?= htmlspecialchars($empresa['rut'] ?? ''); ?>"></div>
                    <div class="mb-3"><label class="form-label">Dirección</label><textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($empresa['direccion'] ?? ''); ?></textarea></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Logo Actual</label>
                    <?php if (!empty($empresa['logo']) && file_exists($empresa['logo'])): ?>
                        <img src="<?= htmlspecialchars($empresa['logo']); ?>" alt="Logo Actual" class="img-fluid rounded border mb-2" style="max-height: 150px;">
                    <?php else: ?>
                        <p class="text-muted">No hay logo.</p>
                    <?php endif; ?>
                    <label class="form-label">Cambiar Logo</label>
                    <input type="file" name="logo" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="card-title mb-0">Información de Contacto</h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($empresa['telefono'] ?? ''); ?>"></div>
                <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($empresa['email'] ?? ''); ?>"></div>
                <div class="col-md-4"><label class="form-label">WhatsApp</label><input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($empresa['whatsapp'] ?? ''); ?>" placeholder="+56912345678"></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="card-title mb-0">Gestión de Datos</h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Crear Backup</h6>
                    <a href="ajax/gestionar_backup.php?action=crear" class="btn btn-success"><i class="fas fa-download me-2"></i>Crear y Descargar</a>
                </div>
                <div class="col-md-6 border-start">
                    <h6>Restaurar Backup</h6>
                    <p class="text-danger"><strong>¡Atención!</strong> Esta acción reemplazará todos los datos actuales.</p>
                    <form id="restoreBackupForm" enctype="multipart/form-data">
                        <div class="mb-3"><input class="form-control" type="file" id="backupFile" name="backupFile" accept=".sql" required></div>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-upload me-2"></i>Restaurar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="text-end mb-4">
        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i> Guardar Cambios</button>
    </div>
</form>

<script>
document.getElementById('restoreBackupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (confirm('¿ESTÁS SEGURO? Esta acción es irreversible y reemplazará toda la base de datos.')) {
        const formData = new FormData(this);
        fetch('ajax/gestionar_backup.php?action=restaurar', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? 'Restauración completada.' : 'Error: ' + data.message);
            if(data.success) window.location.reload();
        })
        .catch(error => alert('Error de comunicación.'));
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>