<?php
require 'conexion.php';

$cliente_id = $_POST['cliente_id'] ?? ($_GET['cliente_id'] ?? 0);
$ticket_id  = $_POST['ticket_id'] ?? ($_GET['ticket_id'] ?? 0);
$q          = $_POST['q'] ?? ($_GET['q'] ?? '');

if (!$cliente_id) {
    echo "<div class='alert alert-danger'>Cliente no válido.</div>";
    exit;
}

// Obtener datos del cliente
$stmtC = $pdo->prepare("SELECT Nombre_y_apellidos FROM clientes WHERE id = ?");
$stmtC->execute([$cliente_id]);
$cliente = $stmtC->fetch(PDO::FETCH_ASSOC);

// Obtener actividades
$sql = "SELECT id, actividad, estado, fecha_limite, urgente
        FROM actividades
        WHERE cliente_id = ?
        ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$cliente_id]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<div class="d-flex justify-content-between align-items-center mb-3">';
echo '  <h6 class="mb-0">Cliente: <strong>' . htmlspecialchars($cliente['Nombre_y_apellidos'] ?? '') . '</strong></h6>';
echo '  <button type="button" class="btn btn-sm btn-secondary" onclick="realizarBusqueda(\'' . addslashes($q) . '\')">⬅ Volver a buscar clientes</button>';
echo '</div>';

if (empty($actividades)) {
    echo '
    <div class="alert alert-warning mb-3">
        Este cliente no tiene actividades creadas.
    </div>';
} else {
    echo '<div class="mb-3">';
    foreach ($actividades as $a) {
        $urgenteBadge = $a['urgente'] ? ' 🔥' : '';
        echo "
        <div class='border rounded p-3 mb-2 bg-white d-flex justify-content-between align-items-center shadow-sm'>
            <div>
                <strong>" . htmlspecialchars($a['actividad']) . "{$urgenteBadge}</strong><br>
                <small class='text-muted'>Estado: " . htmlspecialchars($a['estado'] ?? 'Sin estado') . "</small>
            </div>
            <button type='button' class='btn btn-success btn-sm' onclick='asociarActividadFinal({$a['id']})'>
                Asociar a esta actividad
            </button>
        </div>";
    }
    echo '</div>';
}

echo '<div class="text-end mt-3">';
echo '<a href="nueva_actividad.php?cliente_id=' . $cliente_id . '&ticket_id=' . $ticket_id . '&q=' . urlencode($q) . '" class="btn btn-primary">';
echo '➕ Crear nueva actividad';
echo '</a>';
echo '</div>';