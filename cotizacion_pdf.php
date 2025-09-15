<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar que se proporcione un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de cotización no válido');
}

$cotizacion_id = (int)$_GET['id'];

try {
    // Obtener datos de la cotización
    $stmt = $pdo->prepare("
        SELECT c.*, cl.nombre as cliente_nombre, cl.rut as cliente_rut, 
               cl.email as cliente_email, cl.telefono as cliente_telefono, 
               cl.direccion as cliente_direccion,
               e.nombre as empresa_nombre, e.rut as empresa_rut,
               e.direccion as empresa_direccion, e.telefono as empresa_telefono,
               e.email as empresa_email
        FROM cotizaciones c 
        JOIN clientes cl ON c.cliente_id = cl.id 
        JOIN empresa e ON e.id = 1
        WHERE c.id = ?
    ");
    $stmt->execute([$cotizacion_id]);
    $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cotizacion) {
        die('Cotización no encontrada');
    }

    // Obtener detalles de la cotización (incluyendo genéricos)
    $stmt = $pdo->prepare("
        SELECT cd.*, ps.nombre as producto_nombre, ps.descripcion as producto_descripcion
        FROM cotizacion_detalles cd
        LEFT JOIN productos_servicios ps ON cd.producto_servicio_id = ps.id
        WHERE cd.cotizacion_id = ?
        ORDER BY cd.id
    ");
    $stmt->execute([$cotizacion_id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener configuración de cerco eléctrico si existe
    $stmt = $pdo->prepare("SELECT * FROM cerco_electrico_config WHERE cotizacion_id = ?");
    $stmt->execute([$cotizacion_id]);
    $cerco_config = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die('Error al obtener datos: ' . $e->getMessage());
}

// Configurar headers para PDF
header('Content-Type: text/html; charset=UTF-8');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización <?php echo htmlspecialchars($cotizacion['numero_cotizacion']); ?></title>
    <style>
        @page {
            size: letter;
            margin: 1cm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #333;
            font-size: 10pt;
            line-height: 1.5;
        }
        .container {
            width: 100%;
        }
        .header, .footer, .content {
            width: 100%;
        }
        .header {
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
            padding-bottom: 20px;
        }
        .company-logo {
            float: left;
            width: 20%;
            max-height: 80px;
            object-fit: contain;
        }
        .company-info {
            float: left;
            width: 40%;
            text-align: left;
            padding-left: 20px;
        }
        .quote-info {
            float: right;
            width: 40%;
            text-align: right;
        }
        .company-info h1, .quote-info h2 {
            margin: 0;
            font-size: 16pt;
            font-weight: bold;
        }
        .quote-info h2 {
            color: #2c3e50;
        }
        .company-info p, .quote-info p {
            margin: 0;
            font-size: 9pt;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        .client-info {
            margin-bottom: 25px;
        }
        .client-info h3 {
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            font-size: 12pt;
            margin-bottom: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border-bottom: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .table th {
            background-color: #f4f7f9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9pt;
            color: #555;
        }
        .table .text-right {
            text-align: right;
        }
        .totals {
            float: right;
            width: 40%;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 1cm;
            right: 1cm;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            text-align: center;
            font-size: 8pt;
            color: #777;
        }
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 5px;
        }
        .btn-primary {
            background-color: #2c3e50;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        @media print {
            .no-print { display: none; }
            body, .container { margin: 0; padding: 0; box-shadow: none; }
            .footer { position: fixed; bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">📄 Imprimir PDF</button>
        <button onclick="sendEmail()" class="btn btn-primary">📧 Enviar Email</button>
        <button onclick="sendWhatsApp()" class="btn btn-primary">📱 WhatsApp</button>
        <button onclick="window.close()" class="btn btn-secondary">✖ Cerrar</button>
    </div>

    <div class="container">
        <div class="header clearfix">
            <div class="company-info">
                <h1><?php echo htmlspecialchars($cotizacion['empresa_nombre']); ?></h1>
                <p><?php echo htmlspecialchars($cotizacion['empresa_direccion']); ?></p>
                <p>RUT: <?php echo htmlspecialchars($cotizacion['empresa_rut']); ?></p>
                <p>Email: <?php echo htmlspecialchars($cotizacion['empresa_email']); ?> | Tel: <?php echo htmlspecialchars($cotizacion['empresa_telefono']); ?></p>
            </div>
            <div class="quote-info">
                <h2>COTIZACIÓN</h2>
                <p><strong>N°:</strong> <?php echo htmlspecialchars($cotizacion['numero_cotizacion']); ?></p>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($cotizacion['fecha_cotizacion'])); ?></p>
                <p><strong>Válida hasta:</strong> <?php echo date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])); ?></p>
            </div>
        </div>

        <div class="client-info">
            <h3>CLIENTE</h3>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($cotizacion['cliente_nombre']); ?></p>
            <p><strong>RUT:</strong> <?php echo htmlspecialchars($cotizacion['cliente_rut']); ?></p>
            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($cotizacion['cliente_direccion']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($cotizacion['cliente_email']); ?> | <strong>Tel:</strong> <?php echo htmlspecialchars($cotizacion['cliente_telefono']); ?></p>
        </div>

        <?php if ($cerco_config): ?>
        <div class="cerco-details" style="margin-bottom: 25px;">
            <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px; font-size: 12pt; margin-bottom: 10px;">DETALLES DE INSTALACIÓN DE CERCO ELÉCTRICO</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                        <p style="margin-bottom: 5px;"><strong>Metros Lineales:</strong> <?= htmlspecialchars($cerco_config['metros_lineales']) ?> m</p>
                        <p style="margin-bottom: 5px;"><strong>Número de Hilos:</strong> <?= htmlspecialchars($cerco_config['numero_hilos']) ?> hilos</p>
                        <p style="margin-bottom: 5px;"><strong>Tipo de Instalación:</strong> <?= ucfirst(htmlspecialchars($cerco_config['tipo_instalacion'])) ?></p>
                        <?php if ($cerco_config['necesita_postes']): ?>
                        <p style="margin-bottom: 5px;"><strong>Postes Adicionales:</strong> Sí (<?= htmlspecialchars($cerco_config['cantidad_postes']) ?> unidades)</p>
                        <?php endif; ?>
                    </td>
                    <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                        <?php if ($cerco_config['necesita_andamios']): ?>
                        <p style="margin-bottom: 5px;"><strong>Necesita Andamios:</strong> Sí</p>
                        <?php endif; ?>
                        <?php if ($cerco_config['certificacion_sec']): ?>
                        <p style="margin-bottom: 5px;"><strong>Certificación SEC:</strong> Sí</p>
                        <?php endif; ?>
                        <p style="margin-bottom: 5px;"><strong>Precio Mano de Obra por Metro:</strong> <?= formatCurrency($cerco_config['precio_mano_obra_metro']) ?></p>
                        <p style="margin-bottom: 5px;"><strong>Precio Total Mano de Obra:</strong> <?= formatCurrency($cerco_config['precio_total_mano_obra']) ?></p>
                    </td>
                </tr>
            </table>
            <?php if ($cerco_config['observaciones_tecnicas']): ?>
            <div style="clear: both; margin-top: 10px;">
                <p><strong>Observaciones Técnicas:</strong> <?= nl2br(htmlspecialchars($cerco_config['observaciones_tecnicas'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="content">
            <table class="table">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">Precio Unit.</th>
                        <th class="text-right">Desc. (%)</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td>
                            <strong>
                                <?php 
                                // Si es un item genérico (sin nombre de producto), muestra su descripción. Si no, muestra el nombre del producto.
                                echo htmlspecialchars($detalle['producto_nombre'] ?? $detalle['descripcion_adicional']); 
                                ?>
                            </strong>
                            <?php 
                            // Para productos de catálogo, muestra la descripción adicional si existe.
                            if ($detalle['producto_nombre'] && $detalle['descripcion_adicional']): 
                            ?>
                                <br><small style="color: #555;"><em><?php echo htmlspecialchars($detalle['descripcion_adicional']); ?></em></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?php echo number_format($detalle['cantidad'], 2, ',', '.'); ?></td>
                        <td class="text-right"><?= formatCurrency($detalle['precio_unitario']) ?></td>
                        <td class="text-right"><?= number_format($detalle['descuento_item'] ?? 0, 2, ',', '.') ?>%</td>
                        <td class="text-right"><?= formatCurrency($detalle['subtotal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="totals clearfix">
            <table class="table">
                <?php 
                $subtotal_sin_descuento_general = 0;
                foreach ($detalles as $detalle) {
                    $subtotal_sin_descuento_general += ($detalle['cantidad'] * $detalle['precio_unitario']);
                }
                ?>
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right"><?= formatCurrency($subtotal_sin_descuento_general) ?></td>
                </tr>
                <?php if ($cotizacion['descuento_general'] > 0): ?>
                <tr>
                    <td>Descuento General (<?= number_format($cotizacion['descuento_general'], 2, ',', '.') ?>%)</td>
                    <td class="text-right">- <?= formatCurrency($subtotal_sin_descuento_general - ($cotizacion['subtotal'] ?? 0)) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($cotizacion['con_iva']): ?>
                <tr>
                    <td>Neto</td>
                    <td class="text-right"><?= formatCurrency($cotizacion['subtotal'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td>IVA (19%)</td>
                    <td class="text-right"><?= formatCurrency($cotizacion['iva'] ?? 0) ?></td>
                </tr>
                <?php endif; ?>
                <tr style="background-color: #f4f7f9; font-weight: bold; font-size: 12pt;">
                    <td>TOTAL</td>
                    <td class="text-right"><?= formatCurrency($cotizacion['total'] ?? 0) ?></td>
                </tr>
            </table>
        </div>

        <?php if ($cotizacion['observaciones']): ?>
        <div style="clear: both; margin-top: 30px;">
            <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px; font-size: 12pt; margin-bottom: 10px;">OBSERVACIONES</h3>
            <p style="font-size: 9pt;"><?php echo nl2br(htmlspecialchars($cotizacion['observaciones'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Para aceptar esta cotización, por favor contáctenos. Esta cotización es válida por 30 días.</p>
            <p><?php echo htmlspecialchars($cotizacion['empresa_nombre']); ?> | <?php echo htmlspecialchars($cotizacion['empresa_email']); ?> | <?php echo htmlspecialchars($cotizacion['empresa_telefono']); ?></p>
        </div>
    </div>

    <script>
        function sendEmail() {
            if (confirm('¿Enviar cotización por email al cliente?')) {
                fetch('ajax/enviar_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cotizacion_id: <?php echo $cotizacion_id; ?>,
                        tipo: 'cotizacion'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Email enviado exitosamente');
                    } else {
                        alert('❌ Error al enviar email: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('❌ Error de conexión: ' + error.message);
                });
            }
        }
        
        function sendWhatsApp() {
            if (confirm('¿Enviar cotización por WhatsApp al cliente?')) {
                fetch('ajax/enviar_whatsapp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cotizacion_id: <?php echo $cotizacion_id; ?>,
                        tipo: 'cotizacion'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Mensaje de WhatsApp enviado exitosamente');
                    } else {
                        alert('❌ Error al enviar WhatsApp: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('❌ Error de conexión: ' + error.message);
                });
            }
        }

        // Auto-print cuando se abre en nueva ventana
        if (window.location.search.includes('print=1')) {
            window.onload = function() {
                setTimeout(() => window.print(), 500);
            };
        }
    </script>
</body>
</html>
