<?php
require 'conexion.php';

$id = (int)($_POST['id'] ?? 0);
$cliente_id = (int)($_POST['cliente_id'] ?? 0);
$estado = $_POST['estado'] ?? '';
$asignado = $_POST['asignado'] ?? 'Josemi';
$fecha_limite = $_POST['fecha_limite'] ?? $_POST['fecha'] ?? null;
$urgente = isset($_POST['urgente']) ? 1 : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("
        UPDATE actividades 
        SET estado = :estado,
            asignado = :asignado,
            fecha_limite = :fecha_limite,
            urgente = :urgente
        WHERE id = :id
    ");
    $stmt->execute([
        ':estado' => $estado,
        ':asignado' => $asignado,'
        :fecha_limite' => $fecha_limite,
        ':urgente' => $urgente,
        ':id' => $id
    ]);

    // actualizar urgencia del cliente si alguna actividad es urgente
    $stmt2 = $pdo->prepare("
        UPDATE clientes c
        SET c.urgente = (
            SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END
            FROM actividades a
            WHERE a.cliente_id = c.id AND a.urgente = 1
        )
        WHERE c.id = :cid
    ");
    $stmt2->execute([':cid' => $cliente_id]);
}

header('Location: cliente.php?id=' . $cliente_id);
exit;