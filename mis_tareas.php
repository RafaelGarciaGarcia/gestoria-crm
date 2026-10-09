<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("No hay usuario en sesión");
}

$usuario_id = $_SESSION['usuario_id'];

$sql = "
    SELECT a.*, 
           c.Nombre_y_apellidos AS cliente_nombre,
           c.BSN,
           c.Tlf
    FROM actividades a
    INNER JOIN clientes c ON a.cliente_id = c.id
    WHERE (a.usuario_asignado_id = :id OR a.usuario_asignado_id IS NULL)
      AND (a.estado IS NULL OR a.estado != 'Terminada')
    ORDER BY 
        CASE 
            WHEN a.urgente = 1 THEN 0
            WHEN a.estado = 'Gestionando' THEN 1
            WHEN a.estado = 'Interesado' THEN 2
            ELSE 3
        END,
        a.fecha_limite ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $usuario_id]);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis tareas pendientes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<h3>Mis tareas pendientes</h3>
<p class="text-muted">Urgentes, gestionando e interesados.</p>

<table class="table table-bordered table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th>Nombre</th>
            <th>Urgente</th>
            <th>Pendiente</th>
            <th>Fecha límite</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tareas as $row): ?>

        <?php
            // 🔥 Urgente
            $urgente_icon = ($row['urgente'] == 1) ? '🔥' : '';

            // Bolita según estado
            if ($row['estado'] == 'Gestionando') {
                $pendiente_icon = '🟡';
            } elseif ($row['estado'] == 'Interesado') {
                $pendiente_icon = '🔵';
            } else {
                $pendiente_icon = '⚪';
            }
        ?>

        <tr onclick="window.open('logs.php?id=<?= $row['id'] ?>&cliente_id=<? $row['cliente_id']?>', '_blank')" style="cursor:pointer;">
            <td><?= $row['cliente_nombre'] ?></td>
            <td style="font-size: 1.4rem;"><?= $urgente_icon ?></td>
            <td style="font-size: 1.4rem;"><?= $pendiente_icon ?></td>
            <td><?= $row['fecha_limite'] ? date('d/m/Y', strtotime($row['fecha_limite'])) : '—' ?></td>
        </tr>

        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>