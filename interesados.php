<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("No hay usuario en sesión");
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener solo interesados
$sql = "
    SELECT a.*, 
           c.Nombre_y_apellidos AS cliente_nombre,
           c.BSN,
           c.Tlf
    FROM actividades a
    INNER JOIN clientes c ON a.cliente_id = c.id
    WHERE a.estado = 'Interesado'
    ORDER BY a.fecha_limite ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$interesados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Interesados</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<h3>🔵Interesados</h3>

<div class="mb-3">
    <a href="index.php" class="btn btn-secondary">Volver</a>
</div>

<table class="table table-bordered table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th>Nombre</th>
            <th>Urgente</th>
            <th>Estado</th>
            <th>Fecha límite</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($interesados as $row): ?>

        <?php
            // 🔥 Urgente
            $urgente_icon = ($row['urgente'] == 1) ? '🔥' : '';

            // 🔵 Interesado
            $estado_icon = '🔵';
        ?>

        <tr onclick="window.open('logs.php?id=<?= $row['id'] ?>', '_blank')" style="cursor:pointer;">
            <td><?= $row['cliente_nombre'] ?></td>
            <td style="font-size: 1.4rem;"><?= $urgente_icon ?></td>
            <td style="font-size: 1.4rem;"><?= $estado_icon ?></td>
            <td><?= date('d/m/Y', strtotime($row['fecha_limite'])) ?></td>
        </tr>

        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>