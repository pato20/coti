<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

verificarAuth();
verificarRol(['admin']);

setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252');

// --- GET YEAR FILTER ---
$selected_year = $_GET['year'] ?? date('Y');

// --- Set Headers for Excel download ---
$filename = "Reporte_Ventas_{$selected_year}.xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// --- INITIALIZE DATA FOR 12 MONTHS ---
$report_data = [];
$meses_es = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
for ($m = 1; $m <= 12; $m++) {
    $month_name = $meses_es[$m];
    $report_data[$m] = [
        'mes' => $month_name,
        'qty_nuevas' => 0,
        'total_cotizado' => 0,
        'qty_aprobadas' => 0,
        'monto_aprobado' => 0,
        'qty_rechazadas' => 0,
        'monto_rechazado' => 0,
        'monto_pagado' => 0,
    ];
}

// --- QUERY 1: COTIZACIONES DATA ---
$stmt_cotizaciones = $pdo->prepare("
    SELECT
        MONTH(fecha_cotizacion) AS mes,
        COUNT(id) AS qty_nuevas,
        SUM(total) AS total_cotizado,
        SUM(CASE WHEN estado = 'aceptada' THEN 1 ELSE 0 END) AS qty_aprobadas,
        SUM(CASE WHEN estado = 'aceptada' THEN total ELSE 0 END) AS monto_aprobado,
        SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) AS qty_rechazadas,
        SUM(CASE WHEN estado = 'rechazada' THEN total ELSE 0 END) AS monto_rechazado
    FROM cotizaciones
    WHERE YEAR(fecha_cotizacion) = :year
    GROUP BY mes
");
$stmt_cotizaciones->execute(['year' => $selected_year]);
$cotizaciones_results = $stmt_cotizaciones->fetchAll(PDO::FETCH_ASSOC);

// --- QUERY 2: PAGOS DATA ---
$stmt_pagos = $pdo->prepare("
    SELECT
        MONTH(p.fecha_pago) AS mes,
        SUM(p.monto) AS monto_pagado
    FROM pagos p
    WHERE YEAR(p.fecha_pago) = :year
    GROUP BY mes
");
$stmt_pagos->execute(['year' => $selected_year]);
$pagos_results = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

// --- COMBINE DATA ---
foreach ($cotizaciones_results as $row) {
    $month = (int)$row['mes'];
    if (isset($report_data[$month])) {
        $report_data[$month] = array_merge($report_data[$month], $row);
    }
}
foreach ($pagos_results as $row) {
    $month = (int)$row['mes'];
    if (isset($report_data[$month])) {
        $report_data[$month]['monto_pagado'] = $row['monto_pagado'];
    }
}

// --- OUTPUT TO EXCEL ---
$output = fopen("php://output", "w");

// Header Row
fputcsv($output, [
    'Mes',
    'Monto Cotizado',
    'Monto Aprobado',
    'Monto Pagado',
    'Monto Rechazado',
    '# Nuevas',
    '# Aprobadas',
    '# Rechazadas'
], "\t");

// Data Rows
foreach ($report_data as $data) {
    fputcsv($output, [
        $data['mes'],
        $data['total_cotizado'],
        $data['monto_aprobado'],
        $data['monto_pagado'],
        $data['monto_rechazado'],
        $data['qty_nuevas'],
        $data['qty_aprobadas'],
        $data['qty_rechazadas']
    ], "\t");
}

// --- TOTALS ---
$kpis = [
    'total_cotizado' => array_sum(array_column($report_data, 'total_cotizado')),
    'total_aprobado' => array_sum(array_column($report_data, 'monto_aprobado')),
    'total_pagado' => array_sum(array_column($report_data, 'monto_pagado')),
    'total_rechazado' => array_sum(array_column($report_data, 'monto_rechazado')),
    'total_nuevas' => array_sum(array_column($report_data, 'qty_nuevas')),
    'total_aprobadas' => array_sum(array_column($report_data, 'qty_aprobadas')),
    'total_rechazadas' => array_sum(array_column($report_data, 'qty_rechazadas')),
];

// Empty row
fputcsv($output, [], "\t");

// Footer Row
fputcsv($output, [
    'Total Anual',
    $kpis['total_cotizado'],
    $kpis['total_aprobado'],
    $kpis['total_pagado'],
    $kpis['total_rechazado'],
    $kpis['total_nuevas'],
    $kpis['total_aprobadas'],
    $kpis['total_rechazadas']
], "\t");

fclose($output);
exit();
?>