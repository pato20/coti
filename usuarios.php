<?php
require_once 'includes/init.php';
verificarRol(['admin']);

$page_title = "Gestión de Usuarios";
$current_page = "usuarios";

$success = null;
$error = null;

if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create':
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ? OR email = ?");
                $check_stmt->execute([$_POST['username'], $_POST['email']]);
                if ($check_stmt->fetchColumn() > 0) {
                    $error = "El usuario o email ya existe";
                } else {
                    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO usuarios (username, email, password, nombre_completo, rol, activo) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$_POST['username'], $_POST['email'], $password_hash, $_POST['nombre_completo'], $_POST['rol'], isset($_POST['activo']) ? 1 : 0]);
                    $success = "Usuario creado exitosamente";
                }
                break;
            case 'update':
                if (!empty($_POST['password'])) {
                    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET username=?, email=?, password=?, nombre_completo=?, rol=?, activo=? WHERE id=?");
                    $stmt->execute([$_POST['username'], $_POST['email'], $password_hash, $_POST['nombre_completo'], $_POST['rol'], isset($_POST['activo']) ? 1 : 0, $_POST['id']]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET username=?, email=?, nombre_completo=?, rol=?, activo=? WHERE id=?");
                    $stmt->execute([$_POST['username'], $_POST['email'], $_POST['nombre_completo'], $_POST['rol'], isset($_POST['activo']) ? 1 : 0, $_POST['id']]);
                }
                $success = "Usuario actualizado exitosamente";
                break;
            case 'delete':
                if ($_POST['id'] == $_SESSION['usuario_id']) {
                    $error = "No puedes eliminar tu propio usuario";
                } else {
                    $pdo->prepare("DELETE FROM sesiones WHERE usuario_id = ?")->execute([$_POST['id']]);
                    $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$_POST['id']]);
                    $success = "Usuario eliminado exitosamente";
                }
                break;
        }
    } catch (PDOException $e) {
        $error = "Error en la base de datos: " . $e->getMessage();
    }
}

$usuarios = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM sesiones s WHERE s.usuario_id = u.id AND s.expires_at > NOW()) as sesiones_activas, DATE_FORMAT(u.ultimo_acceso, '%d/%m/%Y %H:%i') as ultimo_acceso_formatted FROM usuarios u ORDER BY u.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="h2"><i class="fas fa-user-cog me-2"></i>Gestión de Usuarios</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usuarioModal"><i class="fas fa-user-plus me-2"></i> Nuevo Usuario</button>
</div>

<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Usuario</th><th>Email</th><th>Rol</th><th>Estado</th><th>Último Acceso</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($usuario['nombre_completo']) ?></strong><br><small class="text-muted">@<?= htmlspecialchars($usuario['username']) ?></small></td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                        <td><span class="badge bg-<?= getRolColor($usuario['rol']) ?>"><?= ucfirst($usuario['rol']) ?></span></td>
                        <td><span class="badge bg-<?= $usuario['activo'] ? 'success' : 'danger' ?>"><?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                        <td><small class="text-muted"><?= $usuario['ultimo_acceso_formatted'] ?? 'Nunca' ?></small></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editarUsuario(<?= htmlspecialchars(json_encode($usuario)) ?>)"><i class="fas fa-edit"></i></button>
                            <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario(<?= $usuario['id'] ?>)"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="usuarioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="usuarioForm">
                <div class="modal-header"><h5 class="modal-title">Nuevo Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create" id="usuarioAction">
                    <input type="hidden" name="id" id="usuarioId">
                    <div class="mb-3"><label>Nombre Completo *</label><input type="text" class="form-control" name="nombre_completo" id="usuarioNombre" required></div>
                    <div class="mb-3"><label>Usuario *</label><input type="text" class="form-control" name="username" id="usuarioUsername" required></div>
                    <div class="mb-3"><label>Email *</label><input type="email" class="form-control" name="email" id="usuarioEmail" required></div>
                    <div class="mb-3"><label>Rol *</label><select class="form-select" name="rol" id="usuarioRol" required><option value="admin">Admin</option><option value="vendedor">Vendedor</option><option value="tecnico">Técnico</option></select></div>
                    <div class="mb-3"><label>Contraseña *</label><input type="password" class="form-control" name="password" id="usuarioPassword" required><small id="passwordHelp" class="form-text text-muted">Mínimo 6 caracteres.</small></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="activo" id="usuarioActivo" checked><label class="form-check-label" for="usuarioActivo">Activo</label></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarUsuario(usuario) {
    document.getElementById('usuarioAction').value = 'update';
    document.getElementById('usuarioId').value = usuario.id;
    document.getElementById('usuarioNombre').value = usuario.nombre_completo;
    document.getElementById('usuarioUsername').value = usuario.username;
    document.getElementById('usuarioEmail').value = usuario.email;
    document.getElementById('usuarioRol').value = usuario.rol;
    document.getElementById('usuarioActivo').checked = usuario.activo == 1;
    document.getElementById('usuarioPassword').removeAttribute('required');
    document.getElementById('passwordHelp').textContent = 'Dejar en blanco para no cambiar.';
    document.querySelector('#usuarioModal .modal-title').textContent = 'Editar Usuario';
    new bootstrap.Modal(document.getElementById('usuarioModal')).show();
}

function eliminarUsuario(id) {
    if (confirm('¿Seguro que quieres eliminar este usuario?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

document.getElementById('usuarioModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('usuarioForm').reset();
    document.getElementById('usuarioAction').value = 'create';
    document.getElementById('usuarioPassword').setAttribute('required', 'required');
    document.getElementById('passwordHelp').textContent = 'Mínimo 6 caracteres.';
    document.querySelector('#usuarioModal .modal-title').textContent = 'Nuevo Usuario';
});
</script>

<?php require_once 'includes/footer.php'; ?>
