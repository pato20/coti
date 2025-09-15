<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de orden no v\u00e1lido');
}

$orden_id = (int)$_GET['id'];

try {
    // Obtener datos de la orden
    $stmt = $pdo->prepare("
        SELECT o.*, c.nombre as cliente_nombre, c.rut as cliente_rut, 
               c.email as cliente_email, c.telefono as cliente_telefono, 
               c.direccion as cliente_direccion,
               e.nombre as empresa_nombre, e.rut as empresa_rut,
               e.direccion as empresa_direccion, e.telefono as empresa_telefono,
               e.email as empresa_email
        FROM ordenes_trabajo o
        JOIN clientes c ON o.cliente_id = c.id
        JOIN empresa e ON e.id = 1
        WHERE o.id = ?
    ");
    $stmt->execute([$orden_id]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orden) {
        die('Orden no encontrada');
    }

    // Obtener detalles de la cotización asociada a la orden
    $stmt = $pdo->prepare("
        SELECT cd.*, ps.nombre as producto_nombre, ps.descripcion as producto_descripcion
        FROM cotizacion_detalles cd
        LEFT JOIN productos_servicios ps ON cd.producto_servicio_id = ps.id
        WHERE cd.cotizacion_id = ?
        ORDER BY cd.id
    ");
    $stmt->execute([$orden['cotizacion_id']]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die('Error al obtener datos: ' . $e->getMessage());
}

header('Content-Type: text/html; charset=UTF-8');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Trabajo <?php echo htmlspecialchars($orden['numero_orden']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .company-info {
            float: left;
            width: 50%;
        }
        .order-info {
            float: right;
            width: 50%;
            text-align: right;
        }
        .company-info h1, .order-info h2 {
            margin: 0;
            font-size: 16pt;
            font-weight: bold;
        }
        .order-info h2 {
            color: #2c3e50;
        }
        .company-info p, .order-info p {
            margin: 0;
            font-size: 9pt;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        .client-info, .order-details-section {
            margin-bottom: 25px;
        }
        .section-title {
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
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Imprimir PDF</button>
        <button onclick="sendEmail()" class="btn btn-primary"><i class="fas fa-envelope"></i> Enviar Email</button>
        <button onclick="window.close()" class="btn btn-secondary"><i class="fas fa-times"></i> Cerrar</button>
    </div>

    <div class="container">
        <div class="header clearfix">
            <div class="company-info">
                <h1><?php echo htmlspecialchars($orden['empresa_nombre']); ?></h1>
                <p><?php echo htmlspecialchars($orden['empresa_direccion']); ?></p>
                <p>RUT: <?php echo htmlspecialchars($orden['empresa_rut']); ?></p>
                <p>Email: <?php echo htmlspecialchars($orden['empresa_email']); ?> | Tel: <?php echo htmlspecialchars($orden['empresa_telefono']); ?></p>
            </div>
            <div class="order-info">
                <h2>ORDEN DE TRABAJO</h2>
                <p><strong>N°:</strong> <?php echo htmlspecialchars($orden['numero_orden']); ?></p>
                <p><strong>Fecha Creación:</strong> <?php echo date('d/m/Y', strtotime($orden['created_at'])); ?></p>
                <p><strong>Estado:</strong> <span style="text-transform: capitalize;"><?php echo htmlspecialchars($orden['estado']); ?></span></p>
            </div>
        </div>

        <div class="client-info">
            <h3 class="section-title">CLIENTE</h3>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($orden['cliente_nombre']); ?></p>
            <p><strong>RUT:</strong> <?php echo htmlspecialchars($orden['cliente_rut']); ?></p>
            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($orden['cliente_direccion']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($orden['cliente_email']); ?> | <strong>Tel:</strong> <?php echo htmlspecialchars($orden['cliente_telefono']); ?></p>
        </div>

        <div class="order-details-section">
            <h3 class="section-title">DESCRIPCIÓN OBSERVACIONES</h3>
            <p style="white-space: pre-wrap; word-wrap: break-word;"><?php echo nl2br(htmlspecialchars($orden['observaciones'])); ?></p>
        </div>

        <div class="content">
            <h3 class="section-title">DETALLES Y COSTOS</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">Precio Unit.</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($detalle['producto_nombre'] ?? $detalle['descripcion_adicional']); ?></strong>
                            <?php if ($detalle['producto_nombre'] && $detalle['descripcion_adicional']): ?>
                                <br><small style="color: #555;"><em><?php echo htmlspecialchars($detalle['descripcion_adicional']); ?></em></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?php echo number_format($detalle['cantidad'], 2, ',', '.'); ?></td>
                        <td class="text-right"><?= formatCurrency($detalle['precio_unitario']) ?></td>
                        <td class="text-right"><?= formatCurrency($detalle['subtotal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="totals clearfix">
            <table class="table">
                 <tr>
                    <td>Subtotal</td>
                    <td class="text-right"><?= formatCurrency($orden['monto_total']) // Asumiendo que monto_total es sin IVA si aplica ?></td>
                </tr>
                <tr>
                    <td>Monto Pagado</td>
                    <td class="text-right"><?= formatCurrency($orden['monto_pagado']) ?></td>
                </tr>
                <tr style="background-color: #f4f7f9; font-weight: bold; font-size: 12pt;">
                    <td>SALDO PENDIENTE</td>
                    <td class="text-right"><?= formatCurrency($orden['monto_total'] - $orden['monto_pagado']) ?></td>
                </tr>
            </table>
        </div>

        

        <div class="footer">
            <p>Esta es una Orden de Trabajo generada por el sistema.</p>
            <p><?php echo htmlspecialchars($orden['empresa_nombre']); ?> | <?php echo htmlspecialchars($orden['empresa_email']); ?> | <?php echo htmlspecialchars($orden['empresa_telefono']); ?></p>
        </div>
    </div>

    <script>
        function sendEmail() {
            if (confirm('\u00bfEnviar la orden de trabajo por email al cliente?')) {
                fetch('ajax/enviar_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        orden_id: <?php echo $orden_id; ?>,
                        tipo: 'orden'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('\u2705 Email enviado exitosamente');
                    } else {
                        alert('\u274c Error al enviar email: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('\u274c Error de conexi\u00f3n: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>
