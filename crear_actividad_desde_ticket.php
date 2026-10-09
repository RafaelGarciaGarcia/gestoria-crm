<?php
require 'conexion.php';

$cliente_id = $_POST['cliente_id'];
$actividad  = $_POST['actividad'];

$sql = "INSERT INTO actividades (cliente_id, actividad, estado, fecha_limite)
        VALUES (?, ?, 'pendiente', NULL)";

$stmt = $pdo->prepare($sql);
$stmt->execute([$cliente_id, $actividad]);

echo $pdo->lastInsertId();