<?php
require_once 'includes/init.php';

$page_title = "Catálogo";
$current_page = "catalogo";

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Procesar formularios
if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        try {
            $id = $_POST['id'];
            $query = "DELETE FROM productos_servicios WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id]);
            header("Location: catalogo.php?success=deleted");
            exit;
        } catch (Exception $e) {
            $error = "Error al eliminar el producto. Es posible que esté en uso en alguna cotización.";
        }
    } elseif (isset($_POST['nombre'])) { // Asumimos que es una acción de guardado
        try {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Actualizar
                $query = "UPDATE productos_servicios SET categoria_id=?, nombre=?, descripcion=?, precio_base=?, unidad=?, tipo=?, activo=?, stock=? WHERE id=?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    $_POST['categoria_id'] ?: null,
                    $_POST['nombre'],
                    $_POST['descripcion'],
                    $_POST['precio_base'],
                    $_POST['unidad'],
                    $_POST['tipo'],
                    isset($_POST['activo']) ? 1 : 0,
                    $_POST['stock'] ?? 0, // Añadir stock
                    $_POST['id']
                ]);
                $success_message = "Producto/Servicio actualizado exitosamente";
            } else {
                // Crear
                $query = "INSERT INTO productos_servicios (categoria_id, nombre, descripcion, precio_base, unidad, tipo, activo, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    $_POST['categoria_id'] ?: null,
                    $_POST['nombre'],
                    $_POST['descripcion'],
                    $_POST['precio_base'],
                    $_POST['unidad'],
                    $_POST['tipo'],
                    isset($_POST['activo']) ? 1 : 0,
                    $_POST['stock'] ?? 0 // Añadir stock
                ]);
                $success_message = "Producto/Servicio creado exitosamente";
            }
            header("Location: catalogo.php?success=saved");
            exit;
        } catch (Exception $e) {
            $error = "Error al guardar: " . $e->getMessage();
        }
    }
}

// Obtener categorías
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos/servicios
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';

$query_ps = "SELECT ps.*, c.nombre as categoria_nombre 
             FROM productos_servicios ps 
             LEFT JOIN categorias c ON ps.categoria_id = c.id 
             WHERE 1=1";
$params = [];

if ($filtro_tipo) {
    $query_ps .= " AND ps.tipo = ?";
    $params[] = $filtro_tipo;
}
if ($filtro_categoria) {
    $query_ps .= " AND ps.categoria_id = ?";
    $params[] = $filtro_categoria;
}

$query_ps .= " ORDER BY ps.nombre";
$stmt_ps = $pdo->prepare($query_ps);
$stmt_ps->execute($params);
$productos_servicios = $stmt_ps->fetchAll(PDO::FETCH_ASSOC);

// Si es edición, obtener datos del producto
$producto_edit = null;
if ($action == 'edit' && $id) {
    $stmt_edit = $pdo->prepare("SELECT * FROM productos_servicios WHERE id = ?");
    $stmt_edit->execute([$id]);
    $producto_edit = $stmt_edit->fetch(PDO::FETCH_ASSOC);
}

require_once 'includes/header.php';
?>

<?php if ($action == 'new' || $action == 'edit'): ?>
<!-- Formulario Nuevo/Editar -->
<div class="page-header slide-in-up">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2 mb-1">
                <i class="fas fa-<?php echo $action == 'edit' ? 'edit' : 'plus'; ?> me-2 text-primary"></i>
                <?php echo $action == 'edit' ? 'Editar' : 'Nuevo'; ?> Producto/Servicio
            </h1>
            <p class="text-muted mb-0">Gestiona los productos y servicios de tu catálogo</p>
        </div>
        <a href="catalogo.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Volver
        </a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card slide-in-up">
    <div class="card-body">
        <form method="POST" action="catalogo.php">
            <input type="hidden" name="id" value="<?php echo $producto_edit['id'] ?? ''; ?>">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($producto_edit['nombre'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" class="form-select" required>
                        <option value="producto" <?php echo ($producto_edit['tipo'] ?? '') == 'producto' ? 'selected' : ''; ?>>Producto</option>
                        <option value="servicio" <?php echo ($producto_edit['tipo'] ?? '') == 'servicio' ? 'selected' : ''; ?>>Servicio</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="categoria_id" class="form-select">
                        <option value="">Sin categoría</option>
                        <?php foreach($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($producto_edit['categoria_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"><?php echo htmlspecialchars($producto_edit['descripcion'] ?? ''); ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Precio Base *</label>
                    <input type="number" name="precio_base" class="form-control" step="any" min="0" required value="<?php echo htmlspecialchars($producto_edit['precio_base'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Unidad *</label>
                    <select name="unidad" class="form-select" required>
                        <option value="unidad" <?php echo ($producto_edit['unidad'] ?? '') == 'unidad' ? 'selected' : ''; ?>>Unidad</option>
                        <option value="metro" <?php echo ($producto_edit['unidad'] ?? '') == 'metro' ? 'selected' : ''; ?>>Metro</option>
                        <option value="metro2" <?php echo ($producto_edit['unidad'] ?? '') == 'metro2' ? 'selected' : ''; ?>>Metro²</option>
                        <option value="hora" <?php echo ($producto_edit['unidad'] ?? '') == 'hora' ? 'selected' : ''; ?>>Hora</option>
                        <option value="servicio" <?php echo ($producto_edit['unidad'] ?? '') == 'servicio' ? 'selected' : ''; ?>>Servicio</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-control" step="any" min="0" value="<?php echo htmlspecialchars($producto_edit['stock'] ?? '0'); ?>">
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="activo" value="1" id="activoCheck" <?php echo ($producto_edit['activo'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="activoCheck">Activo</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Guardar</button>
            <a href="catalogo.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Lista de Productos/Servicios -->
<div class="page-header slide-in-up">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-list me-2 text-primary"></i> Catálogo</h1>
            <p class="text-muted mb-0">Administra tu inventario y servicios</p>
        </div>
        <a href="catalogo.php?action=new" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Nuevo Ítem</a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    Operación realizada exitosamente.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card mb-4 slide-in-up">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filtrar por Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="producto" <?php echo $filtro_tipo == 'producto' ? 'selected' : ''; ?>>Productos</option>
                    <option value="servicio" <?php echo $filtro_tipo == 'servicio' ? 'selected' : ''; ?>>Servicios</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Filtrar por Categoría</label>
                <select name="categoria" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach($categorias as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $filtro_categoria == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-info w-100"><i class="fas fa-filter me-2"></i>Filtrar</button>
                <a href="catalogo.php" class="btn btn-outline-secondary w-100"><i class="fas fa-times me-2"></i>Limpiar</a>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#categoryModal" title="Administrar Categorías"><i class="fas fa-tags"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card slide-in-up">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th>Precio Base</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($productos_servicios as $ps): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($ps['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($ps['categoria_nombre'] ?? 'N/A'); ?></td>
                        <td><span class="badge bg-<?php echo $ps['tipo'] == 'producto' ? 'info' : 'warning'; ?>"><?php echo ucfirst($ps['tipo']); ?></span></td>
                        <td><?php echo formatCurrency($ps['precio_base']); ?></td>
                        <td><?php echo htmlspecialchars($ps['stock']); ?></td>
                        <td><span class="badge bg-<?php echo $ps['activo'] ? 'success' : 'secondary'; ?>"><?php echo $ps['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                        <td>
                            <a href="catalogo.php?action=edit&id=<?php echo $ps['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form method="POST" action="catalogo.php" onsubmit="return confirm('¿Estás seguro?')" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $ps['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Category Management Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tags me-2"></i> Administrar Categorías</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="category-list" class="mb-3"></div>
                <hr>
                <h6>Añadir/Editar Categoría</h6>
                <form id="categoryForm" class="row g-2 align-items-end">
                    <input type="hidden" id="categoryId">
                    <div class="col">
                        <input type="text" class="form-control" id="categoryName" placeholder="Nombre de categoría" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryModal = document.getElementById('categoryModal');
    const categoryListEl = document.getElementById('category-list');
    const categoryForm = document.getElementById('categoryForm');
    const categoryIdInput = document.getElementById('categoryId');
    const categoryNameInput = document.getElementById('categoryName');

    categoryModal.addEventListener('show.bs.modal', loadCategories);
    categoryForm.addEventListener('submit', handleCategorySubmit);

    function handleCategorySubmit(e) {
        e.preventDefault();
        const id = categoryIdInput.value;
        const name = categoryNameInput.value.trim();
        if (name) {
            const action = id ? 'update' : 'create';
            fetch('ajax/gestionar_categorias.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action, id, nombre: name })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    loadCategories();
                    categoryForm.reset();
                    categoryIdInput.value = '';
                } else {
                    alert(data.message);
                }
            });
        }
    }

    function loadCategories() {
        categoryListEl.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
        fetch('ajax/gestionar_categorias.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let html = '<ul class="list-group">';
                    data.categorias.forEach(cat => {
                        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                            ${escapeHTML(cat.nombre)}
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-2" onclick="editCategory(${cat.id}, '${escapeHTML(cat.nombre)}')"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(${cat.id})"><i class="fas fa-trash"></i></button>
                            </div>
                        </li>`;
                    });
                    html += '</ul>';
                    categoryListEl.innerHTML = html;
                } else {
                    categoryListEl.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
                }
            });
    }

    window.editCategory = function(id, name) {
        categoryIdInput.value = id;
        categoryNameInput.value = name;
        categoryNameInput.focus();
    }

    window.deleteCategory = function(id) {
        if (confirm('¿Seguro que quieres eliminar esta categoría?')) {
            fetch('ajax/gestionar_categorias.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'delete', id })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    loadCategories();
                } else {
                    alert(data.message);
                }
            });
        }
    }

    function escapeHTML(str) {
        return str.replace(/[&<>"'`]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '`': '&#x60;' }[match]));
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
