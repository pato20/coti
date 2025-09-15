<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Catálogo de Productos y Servicios";
$current_page = "productos";

// Procesar formulario
if ($_POST) {
    if (isset($_POST['action'])) {
        // Sanitizar y validar datos de entrada aquí si es necesario
        switch ($_POST['action']) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO productos_servicios (nombre, descripcion, precio_base, costo, tipo, categoria_id, unidad, activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([
                    $_POST['nombre'],
                    $_POST['descripcion'],
                    $_POST['precio_base'],
                    $_POST['costo'],
                    $_POST['tipo'],
                    $_POST['categoria_id'],
                    $_POST['unidad']
                ]);
                $success = "Producto/Servicio creado exitosamente";
                break;
            
            case 'update':
                $stmt = $pdo->prepare("UPDATE productos_servicios SET nombre=?, descripcion=?, precio_base=?, costo=?, tipo=?, categoria_id=?, unidad=? WHERE id=?");
                $stmt->execute([
                    $_POST['nombre'],
                    $_POST['descripcion'],
                    $_POST['precio_base'],
                    $_POST['costo'],
                    $_POST['tipo'],
                    $_POST['categoria_id'],
                    $_POST['unidad'],
                    $_POST['id']
                ]);
                $success = "Producto/Servicio actualizado exitosamente";
                break;
            
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM productos_servicios WHERE id=?");
                $stmt->execute([$_POST['id']]);
                $success = "Producto/Servicio eliminado exitosamente";
                break;
        }
    }
}

// Obtener productos y categorías
$productos = $pdo->query("SELECT ps.*, c.nombre as categoria_nombre FROM productos_servicios ps LEFT JOIN categorias c ON ps.categoria_id = c.id ORDER BY ps.tipo, c.nombre, ps.nombre")->fetchAll();
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY tipo, nombre")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema de Cotización</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Catálogo de Productos y Servicios</h1>
                    <div class="btn-toolbar">
                        <button type="button" class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#categoriasModal">
                            <i class="fas fa-tags"></i> Gestionar Categorías
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productoModal">
                            <i class="fas fa-plus"></i> Nuevo Producto/Servicio
                        </button>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-select" id="filtroTipo">
                                    <option value="">Todos los tipos</option>
                                    <option value="producto">Productos</option>
                                    <option value="servicio">Servicios</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="filtroCategoria">
                                    <option value="">Todas las categorías</option>
                                    <option value="cerco_electrico">Cerco Eléctrico</option>
                                    <option value="materiales">Materiales</option>
                                    <option value="instalacion">Instalación</option>
                                    <option value="mantenimiento">Mantenimiento</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="buscarProducto" placeholder="Buscar producto/servicio...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="tablaProductos">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Categoría</th>
                                        <th>Costo</th>
                                        <th>Precio Base</th>
                                        <th>Unidad</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $producto): ?>
                                    <tr data-tipo="<?= htmlspecialchars($producto['tipo']) ?>" data-categoria="<?= htmlspecialchars($producto['categoria_nombre']) ?>">
                                        <td><?= $producto['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($producto['nombre']) ?></strong>
                                            <?php if ($producto['descripcion']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($producto['descripcion']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $producto['tipo'] == 'producto' ? 'info' : 'success' ?>">
                                                <?= ucfirst($producto['tipo']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($producto['categoria_nombre']) ?></td>
                                        <td><?= formatCurrency($producto['costo']) ?></td>
                                        <td><?= formatCurrency($producto['precio_base']) ?></td>
                                        <td><?= htmlspecialchars($producto['unidad']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editarProducto(<?php echo htmlspecialchars(json_encode($producto)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarProducto(<?php echo $producto['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Producto -->
    <div class="modal fade" id="productoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="productoModalTitle">Nuevo Producto/Servicio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create" id="productoAction">
                        <input type="hidden" name="id" id="productoId">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="productoNombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" name="nombre" id="productoNombre" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="productoTipo" class="form-label">Tipo *</label>
                                    <select class="form-select" name="tipo" id="productoTipo" required>
                                        <option value="producto">Producto</option>
                                        <option value="servicio">Servicio</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="productoDescripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="productoDescripcion" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="productoCosto" class="form-label">Costo *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="costo" id="productoCosto" step="0.01" required value="0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="productoPrecioBase" class="form-label">Precio Base *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="precio_base" id="productoPrecioBase" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="productoCategoria" class="form-label">Categoría *</label>
                                    <select class="form-select" name="categoria_id" id="productoCategoria" required>
                                        <?php foreach($categorias as $categoria): ?>
                                            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="productoUnidad" class="form-label">Unidad *</label>
                                    <select class="form-select" name="unidad" id="productoUnidad" required>
                                        <option value="unidad">Unidad</option>
                                        <option value="metro">Metro</option>
                                        <option value="hora">Hora</option>
                                        <option value="servicio">Servicio</option>
                                        <option value="kg">Kg</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
        </div>
    </div>

    <!-- Modal Categorías -->
    <div class="modal fade" id="categoriasModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gestionar Categorías</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Categorías Existentes</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Nombre</th><th>Tipo</th><th>Acciones</th></tr></thead>
                            <tbody id="listaCategorias">
                                <!-- Las categorías se cargarán aquí vía JS -->
                            </tbody>
                        </table>
                    </div>
                    <hr>
                    <h6>Nueva Categoría</h6>
                    <form id="formNuevaCategoria">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="nombre" placeholder="Nombre de la categoría" required>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="tipo" required>
                                    <option value="producto">Producto</option>
                                    <option value="servicio">Servicio</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Crear</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/productos.js"></script>
</body>
</html>
