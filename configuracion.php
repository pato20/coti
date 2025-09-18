<?php
require_once 'includes/init.php';

// 1. Verificación de seguridad: Rol de Administrador
verificarRol(['admin']);

$page_title = "Configuración";
$current_page = "configuracion";

$success = null;
$error = null;

// --- LÓGICA DE AUTO-REPARACIÓN MEJORADA ---
try {
    $default_settings = [
        'smtp_host' => ['valor' => 'smtp.example.com', 'descripcion' => 'Servidor SMTP para envío de emails'],
        'smtp_port' => ['valor' => '587', 'descripcion' => 'Puerto SMTP'],
        'smtp_username' => ['valor' => '', 'descripcion' => 'Usuario SMTP'],
        'smtp_password' => ['valor' => '', 'descripcion' => 'Contraseña SMTP'],
        'whatsapp_token' => ['valor' => '', 'descripcion' => 'Token de WhatsApp Business API'],
        'whatsapp_phone' => ['valor' => '', 'descripcion' => 'Número de teléfono de WhatsApp'],
        'iva_porcentaje' => ['valor' => '19', 'descripcion' => 'Porcentaje de IVA'],
        'moneda' => ['valor' => 'CLP', 'descripcion' => 'Moneda del sistema'],
        'app_release_notes' => ['valor' => 'Primera instalación.', 'descripcion' => 'Notas de la última versión instalada.']
    ];

    $stmt_existing = $pdo->query("SELECT clave FROM configuracion");
    $existing_keys = $stmt_existing->fetchAll(PDO::FETCH_COLUMN);

    $missing_keys = array_diff(array_keys($default_settings), $existing_keys);

    if (!empty($missing_keys)) {
        $stmt_insert = $pdo->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES (:clave, :valor, :descripcion)");
        foreach ($missing_keys as $key) {
            $stmt_insert->execute([
                ':clave' => $key,
                ':valor' => $default_settings[$key]['valor'],
                ':descripcion' => $default_settings[$key]['descripcion']
            ]);
        }
        $success = "Se han añadido nuevas claves de configuración por defecto.";
    }

} catch (Exception $e) {
    $error = "Error al verificar/reparar la configuración: " . $e->getMessage();
}


// 2. Procesar el formulario POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        $form_type = $_POST['form_type'] ?? '';

        if ($form_type === 'empresa') {
            $stmt = $pdo->prepare("SELECT logo FROM empresa WHERE id = 1");
            $stmt->execute();
            $current_logo = $stmt->fetchColumn();
            $logo_path = $current_logo;

            if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                if ($current_logo && file_exists($current_logo) && strpos($current_logo, 'placeholder') === false) {
                    unlink($current_logo);
                }
                $file_name = 'logo-' . uniqid() . '-' . basename($_FILES['logo']['name']);
                $logo_path = $upload_dir . $file_name;
                if (!move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
                    throw new Exception("No se pudo mover el archivo subido.");
                }
            }

            $query = "UPDATE empresa SET nombre = ?, subtitulo = ?, rut = ?, direccion = ?, telefono = ?, email = ?, whatsapp = ?, logo = ? WHERE id = 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$_POST['nombre'] ?? '', $_POST['subtitulo'] ?? '', $_POST['rut'] ?? '', $_POST['direccion'] ?? '', $_POST['telefono'] ?? '', $_POST['email'] ?? '', $_POST['whatsapp'] ?? '', $logo_path]);
            $success = "Información de la empresa actualizada.";

        } elseif ($form_type === 'ajustes') {
            $update_stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
            foreach ($_POST['ajustes'] as $clave => $valor) {
                $update_stmt->execute([$valor, $clave]);
            }
            $success = "Ajustes del sistema actualizados.";
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al actualizar: " . $e->getMessage();
    }
}

// 3. Obtener los datos actuales (después de cualquier reparación)
$empresa = $pdo->query("SELECT * FROM empresa WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$ajustes_raw = $pdo->query("SELECT * FROM configuracion")->fetchAll(PDO::FETCH_ASSOC);

// Agrupar ajustes para el formulario
$ajustes_agrupados = [
    'smtp' => [], 'whatsapp' => [], 'financiero' => []
];
foreach ($ajustes_raw as $ajuste) {
    if (strpos($ajuste['clave'], 'smtp_') === 0) $ajustes_agrupados['smtp'][] = $ajuste;
    elseif (strpos($ajuste['clave'], 'whatsapp_') === 0) $ajustes_agrupados['whatsapp'][] = $ajuste;
    else $ajustes_agrupados['financiero'][] = $ajuste;
}

require_once 'includes/header.php';
?>

<style>
    .nav-tabs .nav-link { color: #343a40 !important; }
    .nav-tabs .nav-link.active { color: #007bff !important; font-weight: bold; }
</style>

<div class="page-header">
    <h1 class="h2"><i class="fas fa-cogs me-2"></i>Configuración del Sistema</h1>
    <p class="text-muted">Ajusta los parámetros generales y la información de tu empresa.</p>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Pestañas de Navegación -->
<ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" id="empresa-tab" data-bs-toggle="tab" data-bs-target="#empresa" type="button" role="tab">Información de Empresa</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="ajustes-tab" data-bs-toggle="tab" data-bs-target="#ajustes" type="button" role="tab">Ajustes del Sistema</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab">Copias de Seguridad</button></li>
</ul>

<!-- Contenido de las Pestañas -->
<div class="tab-content" id="configTabsContent">
    <!-- Pestaña 1: Empresa -->
    <div class="tab-pane fade show active" id="empresa" role="tabpanel">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="form_type" value="empresa">
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0">Datos Generales</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3"><label class="form-label">Nombre de la Empresa *</label><input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($empresa['nombre'] ?? ''); ?>"></div>
                            <div class="mb-3"><label class="form-label">Subtítulo</label><input type="text" name="subtitulo" class="form-control" value="<?= htmlspecialchars($empresa['subtitulo'] ?? ''); ?>"></div>
                            <div class="mb-3"><label class="form-label">RUT</label><input type="text" name="rut" class="form-control" value="<?= htmlspecialchars($empresa['rut'] ?? ''); ?>"></div>
                            <div class="mb-3"><label class="form-label">Dirección</label><textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($empresa['direccion'] ?? ''); ?></textarea></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Logo Actual</label>
                            <?php if (!empty($empresa['logo']) && file_exists($empresa['logo'])): ?><img src="<?= htmlspecialchars($empresa['logo']); ?>" alt="Logo Actual" class="img-fluid rounded border mb-2" style="max-height: 150px;"><?php else: ?><p class="text-muted">No hay logo.</p><?php endif; ?>
                            <label class="form-label">Cambiar Logo</label>
                            <input type="file" name="logo" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0">Información de Contacto</h5></div>
                <div class="card-body"><div class="row"><div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($empresa['telefono'] ?? ''); ?>"></div><div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($empresa['email'] ?? ''); ?>"></div><div class="col-md-4"><label class="form-label">WhatsApp</label><input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($empresa['whatsapp'] ?? ''); ?>" placeholder="+56912345678"></div></div></div>
            </div>
            <div class="text-end mb-4"><button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i> Guardar Cambios de Empresa</button></div>
        </form>
    </div>

    <!-- Pestaña 2: Ajustes -->
    <div class="tab-pane fade" id="ajustes" role="tabpanel">
        <form method="POST">
            <input type="hidden" name="form_type" value="ajustes">
            <div class="card mb-4"><div class="card-header"><h5 class="card-title mb-0">Ajustes de Email (SMTP)</h5></div><div class="card-body"><?php foreach ($ajustes_agrupados['smtp'] as $ajuste): ?><div class="mb-3"><label for="ajuste_<?= htmlspecialchars($ajuste['clave']); ?>" class="form-label"><?= htmlspecialchars($ajuste['descripcion']); ?></label><input type="text" id="ajuste_<?= htmlspecialchars($ajuste['clave']); ?>" name="ajustes[<?= htmlspecialchars($ajuste['clave']); ?>]" class="form-control" value="<?= htmlspecialchars($ajuste['valor']); ?>"></div><?php endforeach; ?></div></div>
            <div class="card mb-4"><div class="card-header"><h5 class="card-title mb-0">Ajustes de WhatsApp</h5></div><div class="card-body"><?php foreach ($ajustes_agrupados['whatsapp'] as $ajuste): ?><div class="mb-3"><label for="ajuste_<?= htmlspecialchars($ajuste['clave']); ?>" class="form-label"><?= htmlspecialchars($ajuste['descripcion']); ?></label><input type="text" id="ajuste_<?= htmlspecialchars($ajuste['clave']); ?>" name="ajustes[<?= htmlspecialchars($ajuste['clave']); ?>]" class="form-control" value="<?= htmlspecialchars($ajuste['valor']); ?>"></div><?php endforeach; ?></div></div>
            <div class="card mb-4"><div class="card-header"><h5 class="card-title mb-0">Ajustes Financieros</h5></div><div class="card-body"><?php foreach ($ajustes_agrupados['financiero'] as $ajuste): ?><div class="mb-3"><label for="ajuste_<?= htmlspecialchars($ajuste['clave']); ?>" class="form-label"><?= htmlspecialchars($ajuste['descripcion']); ?></label><input type="text" id="ajuste_<?= htmlspecialchars($ajuste['clave']); ?>" name="ajustes[<?= htmlspecialchars($ajuste['clave']); ?>]" class="form-control" value="<?= htmlspecialchars($ajuste['valor']); ?>"></div><?php endforeach; ?></div></div>
            
            <div class="text-end mb-4"><button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i> Guardar Ajustes</button></div>
        </form>
    </div>

    <!-- Pestaña 3: Backup -->
    <div class="tab-pane fade" id="backup" role="tabpanel">
        <div class="card mb-4"><div class="card-header"><h5 class="card-title mb-0">Gestión de Datos</h5></div><div class="card-body"><div class="row"><div class="col-md-6"><h6>Crear Copia de Seguridad</h6><p>Crea y descarga un archivo SQL de la base de datos actual.</p><a href="ajax/gestionar_backup.php?action=crear" class="btn btn-success"><i class="fas fa-download me-2"></i>Crear y Descargar</a></div><div class="col-md-6 border-start"><h6>Restaurar Copia de Seguridad</h6><p class="text-danger"><strong>¡Atención!</strong> Esta acción reemplazará todos los datos actuales de forma irreversible.</p><form id="restoreBackupForm" enctype="multipart/form-data"><div class="mb-3"><input class="form-control" type="file" id="backupFile" name="backupFile" accept=".sql" required></div><button type="submit" class="btn btn-danger"><i class="fas fa-upload me-2"></i>Restaurar desde Archivo</button></form></div></div></div></div>
    </div>
</div>

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