<?php
require 'conexion.php';

$actividad_id = (int)($_POST['actividad_id'] ?? 0);
$cliente_id   = (int)($_POST['cliente_id'] ?? 0);
$mensaje      = $_POST['mensaje'] ?? '';
$asignado     = $_POST['asignado'] ?? 'Josemi';
$urgente      = isset($_POST['urgente']) ? 1 : 0;
$fecha_manual = $_POST['fecha_manual'] ?: null;

if ($actividad_id > 0 && $mensaje !== '') {

    $stmt = $pdo->prepare("
        INSERT INTO actividades (cliente_id, descripcion, urgente, usuario_asignado_id, estado)
        VALUES (:cliente_id, :descripcion, :urgente, :usuario_asignado_id, 'Pendiente')

    ");
    $stmt->execute([
        ':act' => $actividad_id,
        ':msg' => $mensaje,
        ':asg' => $asignado,
        ':urg' => $urgente,
        ':fec' => $fecha_manual
    ]);
    
    $stmt = $pdo->prepare("
        UPDATE actividades
        SET estado = ''
        WHERE id = :id
    ");
    $stmt->execute([':id' => $actividad_id]);


    $stmt = $pdo->prepare("
        UPDATE actividades a
        SET a.urgente = (
            SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END
            FROM actividad_logs l
            WHERE l.actividad_id = a.id AND l.urgente = 1
        )
        WHERE a.id = :act
    ");
    $stmt->execute([':act' => $actividad_id]);

    $stmt = $pdo->prepare("
        UPDATE clientes c
        SET c.urgente = (
            SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END
            FROM actividades a
            WHERE a.cliente_id = c.id AND a.urgente = 1
        )
        WHERE c.id = :cid
    ");
    $stmt->execute([':cid' => $cliente_id]);
}

header('Location: logs.php?actividad_id=' . $actividad_id . '&cliente_id=' . $cliente_id);
exit;