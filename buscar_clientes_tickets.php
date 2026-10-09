<?php
require 'conexion.php';

$q         = $_POST['q'] ?? ($_GET['q'] ?? '');
$ticket_id = $_POST['ticket_id'] ?? ($_GET['ticket_id'] ?? 0);

if (empty(trim($q))) {
    echo '<p class="text-muted">Escribe un nombre para buscar clientes.</p>';
    exit;
}

$stmt = $pdo->prepare("SELECT id, Nombre_y_apellidos, BSN FROM clientes WHERE Nombre_y_apellidos LIKE ? ORDER BY Nombre_y_apellidos ASC LIMIT 10");
$stmt->execute(['%' . $q . '%']);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($clientes)) {
    echo '<div class="alert alert-warning mb-3">No se encontró ningún cliente con el nombre <strong>"' . htmlspecialchars($q) . '"</strong>.</div>';
    echo '<a href="nuevo_cliente.php?ticket_id=' . $ticket_id . '&q=' . urlencode($q) . '" class="btn btn-success">';
    echo '👤➕ Crear cliente "' . htmlspecialchars($q) . '"';
    echo '</a>';
} else {
    echo '<label class="form-label text-muted">Clientes encontrados:</label>';
    echo '<div class="list-group mb-3">';
    foreach ($clientes as $c) {
        $bsnText = !empty($c['BSN']) ? ' (BSN: ' . htmlspecialchars($c['BSN']) . ')' : '';
        
        echo '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="buscarActividadesCliente(' . $c['id'] . ', ' . $ticket_id . ', \'' . addslashes($q) . '\')">';
        echo '<span><strong>' . htmlspecialchars($c['Nombre_y_apellidos']) . '</strong>' . $bsnText . '</span>';
        echo '<span class="badge bg-primary rounded-pill">Ver actividades ➔</span>';
        echo '</button>';
    }
    echo '</div>';
    
    echo '<hr>';
    echo '<a href="nuevo_cliente.php?ticket_id=' . $ticket_id . '&q=' . urlencode($q) . '" class="btn btn-sm btn-outline-success">';
    echo '➕ Crear otro cliente';
    echo '</a>';
}