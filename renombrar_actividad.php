<?php
session_start();
require 'conexion.php';

$actividad_id = intval($_POST['actividad_id'] ?? 0);
$cliente_id   = intval($_POST['cliente_id'] ?? 0);
$nuevo_nombre = trim($_POST['nuevo_nombre'] ?? '');

if ($actividad_id > 0 && !empty($nuevo_nombre)) {
    $stmt = $pdo->prepare("UPDATE actividades SET actividad = ? WHERE id = ?");
    $stmt->execute([$nuevo_nombre, $actividad_id]);
}

header("Location: logs.php?actividad_id=" . $actividad_id . "&cliente_id=" . $cliente_id);
exit;