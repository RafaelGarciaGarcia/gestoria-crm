<?php
session_start();
require 'conexion.php';

$actividad_id = $_GET['actividad_id'];
$cliente_id   = $_GET['cliente_id'];

$stmt = $pdo->prepare("
    UPDATE actividades
    SET estado = 'Terminada',
        urgente = 0
    WHERE id = :id
");
$stmt->execute([':id' => $actividad_id]);

$stmtLog = $pdo->prepare("
    INSERT INTO actividad_logs (actividad_id, usuario_id, mensaje, urgente, estado)
    VALUES (:actividad_id, :usuario_id, '✔ Tarea marcada como completada', 0, 'Terminada')
");
$stmtLog->execute([
    ':actividad_id' => $actividad_id,
    ':usuario_id' => $_SESSION['usuario_id']
]);

header("Location: logs.php?actividad_id=$actividad_id&cliente_id=$cliente_id");
exit;