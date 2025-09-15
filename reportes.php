<?php
require_once 'includes/init.php';
verificarRol(['admin']);

$page_title = "Reporte de Ventas";
$current_page = "reportes";



// --- Lógica de obtención de datos ---
$selected_year = $_GET['year'] ?? date('Y');
$available_years_stmt = $pdo->query("SELECT DISTINCT YEAR(fecha_cotizacion) as anio FROM cotizaciones ORDER BY anio DESC");
$available_years = $available_years_stmt->fetchAll(PDO::FETCH_COLUMN);
if (!in_array(date('Y'), $available_years)) {
    $available_years[] = date('Y');
    rsort($available_years);
}

$report_data = [];
$meses_es = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
for ($m = 1; $m <= 12; $m++) {
    $report_data[$m] = [
        'mes' => $meses_es[$m],
        'total_cotizado' => 0, 'monto_aprobado' => 0, 'monto_pagado' => 0, 'monto_rechazado' => 0,
        'qty_nuevas' => 0, 'qty_aprobadas' => 0, 'qty_rechazadas' => 0
    ];
}

$stmt_cotizaciones = $pdo->prepare("SELECT MONTH(fecha_cotizacion) AS mes, COUNT(id) AS qty_nuevas, SUM(total) AS total_cotizado, SUM(CASE WHEN estado = 'aceptada' THEN 1 ELSE 0 END) AS qty_aprobadas, SUM(CASE WHEN estado = 'aceptada' THEN total ELSE 0 END) AS monto_aprobado, SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) AS qty_rechazadas, SUM(CASE WHEN estado = 'rechazada' THEN total ELSE 0 END) AS monto_rechazado FROM cotizaciones WHERE YEAR(fecha_cotizacion) = :year GROUP BY mes");
$stmt_cotizaciones->execute(['year' => $selected_year]);
foreach ($stmt_cotizaciones->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $report_data[(int)$row['mes']] = array_merge($report_data[(int)$row['mes']], $row);
}

$stmt_pagos = $pdo->prepare("SELECT MONTH(p.fecha_pago) AS mes, SUM(p.monto) AS monto_pagado FROM pagos p WHERE YEAR(p.fecha_pago) = :year GROUP BY mes");
$stmt_pagos->execute(['year' => $selected_year]);
foreach ($stmt_pagos->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $report_data[(int)$row['mes']]['monto_pagado'] = $row['monto_pagado'];
}

$kpis = [
    'total_ingresos' => array_sum(array_column($report_data, 'monto_pagado')),
    'total_aprobado' => array_sum(array_column($report_data, 'monto_aprobado')),
    'total_rechazado' => array_sum(array_column($report_data, 'monto_rechazado')),
    'total_cotizaciones' => array_sum(array_column($report_data, 'qty_nuevas')),
    'cuentas_por_cobrar' => $pdo->query("SELECT SUM(monto_total - monto_pagado) FROM ordenes_trabajo WHERE estado_pago != 'pagado'")->fetchColumn() ?? 0
];

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2"><i class="fas fa-chart-bar me-2"></i>Reporte Anual</h1>
            <p class="text-muted">Análisis de ventas y cotizaciones por mes.</p>
        </div>
        <div class="btn-toolbar">
            <form method="GET" class="d-flex align-items-center">
                <label for="year" class="me-2">Año:</label>
                <select name="year" id="year" class="form-select w-auto" onchange="this.form.submit()">
                    <?php foreach ($available_years as $year): ?>
                        <option value="<?= $year ?>" <?= ($selected_year == $year) ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="ajax/exportar_reporte_excel.php?year=<?= $selected_year ?>" class="btn btn-outline-success ms-2"><i class="fas fa-file-excel me-1"></i> Exportar</a>
        </div>
    </div>
</div>

<!-- KPIs -->
<div class="row mb-4">
    <div class="col-lg col-md-6 mb-3"><div class="card h-100 bg-success text-white"><div class="card-body"><h5>Ingresos Totales</h5><p class="fs-4 fw-bold"><?= formatCurrency($kpis['total_ingresos']) ?></p></div></div></div>
    <div class="col-lg col-md-6 mb-3"><div class="card h-100 bg-warning text-dark"><div class="card-body"><h5>Cuentas por Cobrar</h5><p class="fs-4 fw-bold"><?= formatCurrency($kpis['cuentas_por_cobrar']) ?></p></div></div></div>
    <div class="col-lg col-md-6 mb-3"><div class="card h-100 bg-primary text-white"><div class="card-body"><h5>Total Aprobado</h5><p class="fs-4 fw-bold"><?= formatCurrency($kpis['total_aprobado']) ?></p></div></div></div>
    <div class="col-lg col-md-6 mb-3"><div class="card h-100 bg-danger text-white"><div class="card-body"><h5>Total Rechazado</h5><p class="fs-4 fw-bold"><?= formatCurrency($kpis['total_rechazado']) ?></p></div></div></div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Desglose Mensual - Año <?= $selected_year ?></h5></div>
    <div class="card-body p-0">
                <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mes</th>
                        <th class="text-end">Total Cotizado</th>
                        <th class="text-end">Monto Aprobado</th>
                        <th class="text-end">Monto Pagado</th>
                        <th class="text-end">Monto Rechazado</th>
                        <th class="text-center">Nuevas</th>
                        <th class="text-center">Aprobadas</th>
                        <th class="text-center">Rechazadas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $data): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($data['mes']) ?></strong></td>
                        <td class="text-end"><?= formatCurrency($data['total_cotizado']) ?></td>
                        <td class="text-end text-success"><?= formatCurrency($data['monto_aprobado']) ?></td>
                        <td class="text-end text-primary"><?= formatCurrency($data['monto_pagado']) ?></td>
                        <td class="text-end text-danger"><?= formatCurrency($data['monto_rechazado']) ?></td>
                        <td class="text-center"><?= $data['qty_nuevas'] ?></td>
                        <td class="text-center"><?= $data['qty_aprobadas'] ?></td>
                        <td class="text-center"><?= $data['qty_rechazadas'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                 <tfoot class="table-dark fw-bold">
                    <tr>
                        <td>Total Anual</td>
                        <td class="text-end"><?= formatCurrency(array_sum(array_column($report_data, 'total_cotizado'))) ?></td>
                        <td class="text-end"><?= formatCurrency($kpis['total_aprobado']) ?></td>
                        <td class="text-end"><?= formatCurrency($kpis['total_ingresos']) ?></td>
                        <td class="text-end"><?= formatCurrency($kpis['total_rechazado']) ?></td>
                        <td class="text-center"><?= array_sum(array_column($report_data, 'qty_nuevas')) ?></td>
                        <td class="text-center"><?= array_sum(array_column($report_data, 'qty_aprobadas')) ?></td>
                        <td class="text-center"><?= array_sum(array_column($report_data, 'qty_rechazadas')) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h5 class="card-title mb-0">Evolución Mensual (Montos)</h5></div>
    <div class="card-body"><canvas id="monthlyChart"></canvas></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($report_data, 'mes')) ?>,
            datasets: [
                { label: 'Monto Aprobado', data: <?= json_encode(array_column($report_data, 'monto_aprobado')) ?>, backgroundColor: 'rgba(25, 135, 84, 0.6)' },
                { label: 'Monto Pagado', data: <?= json_encode(array_column($report_data, 'monto_pagado')) ?>, backgroundColor: 'rgba(13, 110, 253, 0.6)', type: 'line', tension: 0.3, fill: false },
                { label: 'Monto Rechazado', data: <?= json_encode(array_column($report_data, 'monto_rechazado')) ?>, backgroundColor: 'rgba(220, 53, 69, 0.6)' }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
