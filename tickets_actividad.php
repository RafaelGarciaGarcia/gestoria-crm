<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php';

$actividad_id = $_GET['actividad_id'] ?? 0;
$cliente_id = $_GET['cliente_id'] ?? 0;

// Obtener los tickets asociados a esta actividad
$stmt = $pdo->prepare("
    SELECT t.*, c.Nombre_y_apellidos AS cliente_nombre 
    FROM tickets t 
    LEFT JOIN clientes c ON t.cliente_id = c.id 
    WHERE t.actividad_id = ?
    ORDER BY t.fecha DESC
");
$stmt->execute([$actividad_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

function bolaEstadoTicket($estado, $actividad_id = null, $es_respondido = false) {
    $estado_lower = strtolower(trim($estado));
    $html_iconos = '';

    if (!empty($actividad_id) || str_contains($estado_lower, 'asignado') || str_contains($estado_lower, 'asociado') || str_contains($estado_lower, 'ticket') && !str_contains($estado_lower, 'respondido')) {
        $html_iconos .= '<span title="Ticket asociado" style="font-size: 1.2rem; cursor: default; margin-right: 2px;" aria-label="Ticket">📨</span>';
    }

    if ($es_respondido || str_contains($estado_lower, 'respondido')) {
        $html_iconos .= '<span title="Ticket respondido" style="font-size: 1.2rem; cursor: default;" aria-label="Respondido">↩️</span>';
    }

    // Si no cayó en ninguna de las anteriores por match estricto, evaluamos los demás estados
    if ($html_iconos === '') {
        return match(true) {
            str_contains($estado_lower, 'nuevo') => '<span title="Nuevo" style="font-size: 1.2rem; cursor: default;">🔵</span>',
            str_contains($estado_lower, 'terminado') => '<span title="Terminado" style="font-size: 1.2rem; cursor: default;">🟢</span>',
            default => '<span title="Estado: ' . htmlspecialchars($estado) . '" style="font-size: 1.2rem; cursor: default;">🔵</span>'
        };
    }

    return $html_iconos;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tickets de la Actividad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Tickets asociados a la actividad</h2>
        <a href="cliente.php?id=<?= $cliente_id ?>" class="btn btn-secondary btn-sm">Volver al cliente</a>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="alert alert-info">No hay tickets asociados a esta actividad.</div>
    <?php else: ?>
        <?php foreach ($tickets as $t): ?>
            <div class="card shadow-sm mb-3 border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <span class="me-2"><?= bolaEstadoTicket($t['estado'], $t['actividad_id']) ?></span>
                            <span class="badge bg-primary fs-6">Ticket #<?= $t['id'] ?></span>
                        </div>
                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($t['fecha'])) ?></small>
                    </div>
                    
                    <p class="mb-1"><strong>De:</strong> <?= htmlspecialchars($t['nombre']) ?> (<?= htmlspecialchars($t['email']) ?>)</p>
                    <p class="mb-2"><strong>Mensaje:</strong><br><?= nl2br(htmlspecialchars($t['mensaje'])) ?></p>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge bg-info text-dark">Estado: <?= htmlspecialchars($t['estado'] ?: 'Ticket') ?></span>
                            <?php if (!empty($t['descuentos'])): ?>
                                <span class="badge bg-warning text-dark">Descuento: <?= htmlspecialchars($t['descuentos']) ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="ticket.php?id=<?= $t['id'] ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                            Ver ticket completo &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</body>
</html>