<?php
session_start();
require 'conexion.php';

// Capturar parámetros de contexto
$cliente_id  = $_POST['cliente_id'] ?? ($_GET['cliente_id'] ?? 0);
$ticket_id   = $_POST['ticket_id'] ?? ($_GET['ticket_id'] ?? 0);
$search      = $_POST['q'] ?? ($_GET['q'] ?? '');

$actividad   = $_POST['actividad'] ?? '';
$estado      = $_POST['estado'] ?? 'Gestionando';
$usuario_id  = $_POST['usuario_asignado_id'] ?? ($_SESSION['usuario_id'] ?? 1);
$fecha_limite= !empty($_POST['fecha_limite']) ? $_POST['fecha_limite'] : NULL;

// Activar modo de errores PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($cliente_id && !empty($actividad)) {
    // 1. Insertar la nueva actividad
    $stmt = $pdo->prepare("
        INSERT INTO actividades (cliente_id, actividad, estado, usuario_asignado_id, fecha_limite)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$cliente_id, $actividad, $estado, $usuario_id, $fecha_limite]);
    
    // Obtener el ID de la actividad recién creada
    $nueva_actividad_id = $pdo->lastInsertId();

    // 2. Si viene desde un ticket, asociar y cerrar el ticket
    if ($ticket_id > 0 && $nueva_actividad_id > 0) {
        $stmtT = $pdo->prepare("
            UPDATE tickets 
            SET actividad_id = ?, cliente_id = ?, estado = 'cerrado' 
            WHERE id = ?
        ");
        $stmtT->execute([$nueva_actividad_id, $cliente_id, $ticket_id]);

        // Obtener datos del ticket para el log
        $stmtTk = $pdo->prepare("SELECT id, nombre FROM tickets WHERE id = ?");
        $stmtTk->execute([$ticket_id]);
        $ticketData = $stmtTk->fetch(PDO::FETCH_ASSOC);

        // 3. CREAR EL LOG DE ASOCIACIÓN AUTOMÁTICA
        $mensaje_log = "Ticket #" . $ticketData['id'] . " (" . $ticketData['nombre'] . ") asociado mediante la creación de esta nueva actividad.";
        
        $stmtLog = $pdo->prepare("
            INSERT INTO actividad_logs (actividad_id, usuario_id, mensaje, estado_en_log, urgente, fecha)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $stmtLog->execute([$nueva_actividad_id, $usuario_id, $mensaje_log, $estado]);

        // Redirigir directamente al listado de tickets
        header("Location: tickets.php?msg=asociado");
        exit;
    }
}

// Redirección por defecto si no venía de un ticket
if ($cliente_id > 0) {
    header("Location: cliente.php?id=" . $cliente_id);
} else {
    header("Location: index.php");
}
exit;