<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php';

$ticket_id    = intval($_POST['ticket_id'] ?? 0);
$actividad_id = intval($_POST['actividad_id'] ?? 0);
$usuario_id   = $_SESSION['usuario_id'] ?? 1;

if ($ticket_id > 0 && $actividad_id > 0) {
    // 1. Obtener cliente_id de la actividad
    $stmtA = $pdo->prepare("SELECT cliente_id FROM actividades WHERE id = ?");
    $stmtA->execute([$actividad_id]);
    $actividad = $stmtA->fetch(PDO::FETCH_ASSOC);

    $cliente_id = $actividad['cliente_id'] ?? NULL;

    // 2. Obtener información del ticket para rescatar su mensaje (cuerpo)
    $stmtTicket = $pdo->prepare("SELECT id, nombre, email, mensaje FROM tickets WHERE id = ?");
    $stmtTicket->execute([$ticket_id]);
    $ticket = $stmtTicket->fetch(PDO::FETCH_ASSOC);
    
    if ($ticket) {
        // Actualizar el ticket indicando su actividad, cliente y estado 'Asociado'
        $stmtUpdate = $pdo->prepare("UPDATE tickets SET actividad_id = ?, cliente_id = ?, estado = 'Asociado' WHERE id = ?");
        $stmtUpdate->execute([$actividad_id, $cliente_id, $ticket_id]);

        // Crear el log de asociación con el MENSAJE (cuerpo) del ticket y el estado exacto 'Ticket'
        $mensaje_log = $ticket['mensaje'];
        $estado_log  = "Ticket"; 

        $stmtLog = $pdo->prepare("
            INSERT INTO actividad_logs (actividad_id, usuario_id, mensaje, estado_en_log, urgente, fecha)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $stmtLog->execute([$actividad_id, $usuario_id, $mensaje_log, $estado_log]);

        // Devolvemos la URL del ticket para redirigir allí directamente
        echo "ticket.php?id=" . $ticket_id;
    } else {
        echo "Error: Ticket no encontrado";
    }
} else {
    echo "Error: Parámetros inválidos";
}