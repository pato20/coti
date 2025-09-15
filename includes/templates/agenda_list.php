<?php
// Esta plantilla es incluida por agenda.php, por lo que tiene acceso a $pdo, $user_id, $user_rol

$query_eventos = "SELECT a.*, c.nombre as cliente_nombre, u.nombre_completo as usuario_nombre 
                  FROM agenda a 
                  LEFT JOIN clientes c ON a.cliente_id = c.id 
                  LEFT JOIN usuarios u ON a.usuario_id = u.id 
                  WHERE a.fecha_hora_inicio >= CURDATE()";
$params = [];

if ($user_rol != 'admin') {
    $query_eventos .= " AND a.usuario_id = ?";
    $params[] = $user_id;
}

$query_eventos .= " ORDER BY a.fecha_hora_inicio ASC";

$stmt = $pdo->prepare($query_eventos);
$stmt->execute($params);
$proximas_visitas = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Próximas Visitas</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Título</th>
                        <th>Cliente</th>
                        <?php if ($user_rol == 'admin'): ?><th>Asignado a</th><?php endif; ?>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proximas_visitas)): ?>
                        <tr><td colspan="6" class="text-center text-muted p-4">No hay próximas visitas agendadas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($proximas_visitas as $visita): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($visita['fecha_hora_inicio'])) ?></td>
                            <td><?= htmlspecialchars($visita['titulo']) ?></td>
                            <td><?= htmlspecialchars($visita['cliente_nombre'] ?? 'N/A') ?></td>
                            <?php if ($user_rol == 'admin'): ?><td><?= htmlspecialchars($visita['usuario_nombre'] ?? 'N/A') ?></td><?php endif; ?>
                            <td><span class="badge bg-info"><?= ucfirst($visita['tipo']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick='abrirModalEvento(<?= json_encode($visita) ?>)'><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
