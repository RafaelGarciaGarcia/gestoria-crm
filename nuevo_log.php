<?php
session_start();
require 'conexion.php';

$actividad_id = (int)($_POST['actividad_id'] ?? 0);
$cliente_id   = (int)($_POST['cliente_id'] ?? 0);
$mensaje      = trim($_POST['mensaje'] ?? '');
$estado_nuevo = $_POST['estado'] ?? null;
$urgente_form = (int)($_POST['urgente'] ?? 0);
$usuario_id   = (int)($_POST['usuario_asignado'] ?? 0);
$fecha_manual = !empty($_POST['fecha_limite']) ? $_POST['fecha_limite'] : null;

if ($actividad_id > 0) {

    // 1. Obtener estado actual de la actividad
    $stmtEstado = $pdo->prepare("SELECT estado FROM actividades WHERE id = ?");
    $stmtEstado->execute([$actividad_id]);
    $estado_actual = $stmtEstado->fetchColumn();

    // 2. Determinar estado para el log
    $estado_en_log = ($estado_nuevo !== null && $estado_nuevo !== "Null") ? $estado_nuevo : $estado_actual;

    // 3. Insertar log SOLO SI se introdujo algún mensaje
    if ($mensaje !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO actividad_logs 
            (actividad_id, usuario_id, mensaje, urgente, fecha_manual, asignado, estado, estado_en_log)
            VALUES 
            (:actividad_id, :usuario_id, :mensaje, :urgente, :fecha_manual, :asignado, :estado, :estado_en_log)
        ");

        $stmt->execute([
            ':actividad_id'  => $actividad_id,
            ':usuario_id'    => $usuario_id,
            ':mensaje'        => $mensaje,
            ':urgente'        => $urgente_form,
            ':fecha_manual'  => $fecha_manual,
            ':asignado'      => 'Josemi',
            ':estado'        => $estado_en_log,
            ':estado_en_log' => $estado_en_log
        ]);
    }

    // 4. Actualizar estado de la actividad si se seleccionó uno válido
    if ($estado_nuevo !== null && $estado_nuevo !== "Null") {
        $stmt = $pdo->prepare("UPDATE actividades SET estado = :estado WHERE id = :id");
        $stmt->execute([
            ':estado' => $estado_nuevo,
            ':id'     => $actividad_id
        ]);
    }

    // 5. Actualizar SIEMPRE la fecha límite de la actividad
    $stmt = $pdo->prepare("
        UPDATE actividades 
        SET fecha_limite = :fecha 
        WHERE id = :id
    ");
    $stmt->bindValue(':fecha', $fecha_manual, $fecha_manual === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':id', $actividad_id, PDO::PARAM_INT);
    $stmt->execute();

    // 6. Actualizar urgencia de la actividad (Si es Terminada → 0, si no → urgente seleccionado)
    $nueva_urgencia = ($estado_nuevo === "Terminada") ? 0 : $urgente_form;

    $stmt = $pdo->prepare("
        UPDATE actividades 
        SET urgente = :urgente
        WHERE id = :id
    ");
    $stmt->execute([
        ':urgente' => $nueva_urgencia,
        ':id'      => $actividad_id
    ]);

    // 7. Recalcular urgencia del cliente
    if ($cliente_id > 0) {
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
}

header("Location: logs.php?actividad_id=$actividad_id&cliente_id=$cliente_id");
exit;