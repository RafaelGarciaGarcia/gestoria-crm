<?php
session_start();
require 'conexion.php';

$actividad_id = (int)($_GET['actividad_id'] ?? 0);
$cliente_id   = (int)($_GET['cliente_id'] ?? 0);

if ($actividad_id > 0) {

    $stmt = $pdo->prepare("
        UPDATE actividades 
        SET fecha_limite = NULL 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $actividad_id]);
}

header("Location: logs.php?actividad_id=$actividad_id&cliente_id=$cliente_id");
exit;
