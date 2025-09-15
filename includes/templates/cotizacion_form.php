<?php $is_edit_mode = ($action == 'edit'); ?>
<div class="page-header slide-in-up">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2 mb-1">
                <i class="fas fa-file-invoice-dollar me-2 text-primary"></i>
                <?= $is_edit_mode ? 'Editar Cotización #' . htmlspecialchars($cotizacion_edit['numero_cotizacion']) : 'Nueva Cotización' ?>
            </h1>
            <p class="text-muted mb-0"><?= $is_edit_mode ? 'Modifica los detalles de la cotización.' : 'Crea una nueva cotización para tus clientes' ?></p>
        </div>
        <a href="cotizaciones.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Volver</a>
    </div>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?= $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="cotizaciones.php?action=save">
    <?php if ($is_edit_mode): ?>
        <input type="hidden" name="cotizacion_id" value="<?= $cotizacion_edit['id'] ?>">
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card slide-in-up mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i> Información General</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cliente *</label>
                            <select name="cliente_id" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach($clientes as $cliente): ?>
                                <option value="<?= $cliente['id']; ?>" <?= (($cotizacion_edit['cliente_id'] ?? '') == $cliente['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fecha Cotización *</label>
                            <input type="date" name="fecha_cotizacion" class="form-control" value="<?= htmlspecialchars($cotizacion_edit['fecha_cotizacion'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fecha Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" class="form-control" value="<?= htmlspecialchars($cotizacion_edit['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+30 days'))) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card slide-in-up">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-list-ul me-2"></i> Detalles de la Cotización</h5>
                </div>
                <div class="card-body">
                    <div id="productos-container">
                        <!-- Filas de productos existentes -->
                        <?php if ($is_edit_mode): ?>
                            <?php foreach($detalles_edit as $item): ?>
                            <div class="row producto-row mb-2">
                                <div class="col-md-4"><select name="productos[]" class="form-select"><?php foreach($productos_servicios as $ps): ?><option value="<?= $ps['id'] ?>" data-precio="<?= $ps['precio_base'] ?>" <?= ($item['producto_servicio_id'] == $ps['id']) ? 'selected' : '' ?>><?= htmlspecialchars($ps['nombre']) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-2"><input type="number" name="cantidades[]" class="form-control" value="<?= $item['cantidad'] ?>" placeholder="Cant."></div>
                                <div class="col-md-2"><input type="number" name="precios[]" class="form-control" value="<?= $item['precio_unitario'] ?>" placeholder="Precio"></div>
                                <div class="col-md-2"><input type="number" name="descuentos_item[]" class="form-control" value="<?= $item['descuento_item'] ?? '0' ?>" placeholder="Desc. (%)" min="0" max="100" step="0.01"></div>
                                <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.producto-row').remove()"><i class="fas fa-trash"></i></button></div>
                            </div>
                            <?php endforeach; ?>
                            <?php foreach($detalles_genericos_edit as $item): ?>
                            <div class="row producto-row mb-2">
                                <div class="col-md-4"><input type="text" name="generic_description[]" class="form-control" value="<?= htmlspecialchars($item['descripcion_adicional']) ?>" placeholder="Descripción"></div>
                                <div class="col-md-2"><input type="number" name="generic_quantity[]" class="form-control" value="<?= $item['cantidad'] ?>" placeholder="Cant."></div>
                                <div class="col-md-2"><input type="number" name="generic_price[]" class="form-control" value="<?= $item['precio_unitario'] ?>" placeholder="Precio"></div>
                                <div class="col-md-2"><input type="number" name="generic_descuentos_item[]" class="form-control" value="<?= $item['descuento_item'] ?? '0' ?>" placeholder="Desc. (%)" min="0" max="100" step="0.01"></div>
                                <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.producto-row').remove()"><i class="fas fa-trash"></i></button></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-success btn-sm me-2" onclick="addProductRow()"><i class="fas fa-plus"></i> Añadir Ítem</button>
                        <button type="button" class="btn btn-info btn-sm" onclick="addGenericRow()"><i class="fas fa-pencil-alt"></i> Añadir Genérico</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card slide-in-up">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-cog me-2"></i> Opciones</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="4"><?= htmlspecialchars($cotizacion_edit['observaciones'] ?? '') ?></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="activar_iva" name="activar_iva" value="1" <?= ($cotizacion_edit['con_iva'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activar_iva">Activar IVA (19%)</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="activar_descuento_general" name="activar_descuento_general" value="1" <?= (isset($cotizacion_edit['descuento_general']) && $cotizacion_edit['descuento_general'] > 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activar_descuento_general">Aplicar Descuento General</label>
                    </div>
                    <div id="descuento_general_fields" class="<?= (isset($cotizacion_edit['descuento_general']) && $cotizacion_edit['descuento_general'] > 0) ? '' : 'd-none' ?> mb-3">
                        <label class="form-label">Descuento General (%)</label>
                        <input type="number" name="descuento_general" class="form-control" value="<?= htmlspecialchars($cotizacion_edit['descuento_general'] ?? '0') ?>" min="0" max="100" step="0.01">
                    </div>
                    <hr>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="es_cerco_electrico" name="es_cerco_electrico" value="1" <?= $cerco_edit ? 'checked' : '' ?>>
                        <label class="form-check-label" for="es_cerco_electrico">Es Cerco Eléctrico</label>
                    </div>
                    <div id="cerco-electrico-fields" class="<?= $cerco_edit ? '' : 'd-none' ?> mt-3">
                        <div class="mb-3">
                            <label class="form-label">Metros Lineales</label>
                            <input type="number" name="metros_lineales" class="form-control" value="<?= htmlspecialchars($cerco_edit['metros_lineales'] ?? '') ?>" placeholder="Ej: 50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Instalación</label>
                            <select name="tipo_instalacion" class="form-select">
                                <option value="basica" <?= (($cerco_edit['tipo_instalacion'] ?? '') == 'basica') ? 'selected' : '' ?>>Básica (Muro)</option>
                                <option value="media" <?= (($cerco_edit['tipo_instalacion'] ?? '') == 'media') ? 'selected' : '' ?>>Media (Pandereta)</option>
                                <option value="compleja" <?= (($cerco_edit['tipo_instalacion'] ?? '') == 'compleja') ? 'selected' : '' ?>>Compleja (Reja)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Número de Hilos</label>
                            <select name="numero_hilos" class="form-select">
                                <option value="4" <?= (($cerco_edit['numero_hilos'] ?? '') == '4') ? 'selected' : '' ?>>4 Hilos</option>
                                <option value="5" <?= (($cerco_edit['numero_hilos'] ?? '') == '5') ? 'selected' : '' ?>>5 Hilos</option>
                                <option value="6" <?= (($cerco_edit['numero_hilos'] ?? '') == '6') ? 'selected' : '' ?>>6 Hilos</option>
                            </select>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label">Subtotal:</label>
                    <span id="cotizacion_subtotal" class="form-control-plaintext fw-bold">0.00</span>
                </div>
                <div class="mb-3">
                    <label class="form-label">IVA (19%):</label>
                    <span id="cotizacion_iva" class="form-control-plaintext fw-bold">0.00</span>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total:</label>
                    <span id="cotizacion_total" class="form-control-plaintext fw-bold fs-4 text-primary">0.00</span>
                </div>
                <div class="card-footer">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i> <?= $is_edit_mode ? 'Actualizar' : 'Guardar' ?> Cotización</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Plantilla para nueva fila de producto -->
<template id="producto-fila-template">
    <div class="row producto-row mb-2">
        <div class="col-md-4">
            <select name="productos[]" class="form-select">
                <option value="">Seleccionar producto/servicio...</option>
                <?php foreach($productos_servicios as $ps): ?>
                <option value="<?= $ps['id']; ?>" data-precio="<?= $ps['precio_base']; ?>"><?= htmlspecialchars($ps['nombre']); ?> - <?= formatCurrency($ps['precio_base']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><input type="number" name="cantidades[]" class="form-control" placeholder="Cant." step="0.1" min="0"></div>
        <div class="col-md-2"><input type="number" name="precios[]" class="form-control" placeholder="Precio" step="0.01" min="0"></div>
        <div class="col-md-2"><input type="number" name="descuentos_item[]" class="form-control" placeholder="Desc. (%)" min="0" max="100" step="0.01"></div>
        <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.producto-row').remove()"><i class="fas fa-trash"></i></button></div>
    </div>
</template>

<!-- Plantilla para nueva fila genérica -->
<template id="generico-fila-template">
    <div class="row producto-row mb-2">
        <div class="col-md-4"><input type="text" name="generic_description[]" class="form-control" placeholder="Descripción de item genérico" required></div>
        <div class="col-md-2"><input type="number" name="generic_quantity[]" class="form-control" placeholder="Cant." step="0.1" min="0" required></div>
        <div class="col-md-2"><input type="number" name="generic_price[]" class="form-control" placeholder="Precio" step="0.01" min="0" required></div>
        <div class="col-md-2"><input type="number" name="generic_descuentos_item[]" class="form-control" placeholder="Desc. (%)" min="0" max="100" step="0.01"></div>
        <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.producto-row').remove()"><i class="fas fa-trash"></i></button></div>
    </div>
</template>
