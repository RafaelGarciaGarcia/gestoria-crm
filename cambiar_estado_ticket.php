<?php
session_start();
require 'conexion.php';

$ticket_id = intval($_POST['ticket_id'] ?? 0);
$nuevo_estado = $_POST['estado'] ?? '';
$redirigir = $_POST['redirigir'] ?? '';

// Validar estados permitidos
$estados_validos = ['nuevo', 'respondido', 'terminado'];

if ($ticket_id > 0 && in_array(strtolower($nuevo_estado), $estados_validos)) {
    $stmt = $pdo->prepare("UPDATE tickets SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $ticket_id]);
}

// Redirección inteligente según de dónde venga
if ($redirigir === 'detalle') {
    header("Location: ticket.php?id=" . $ticket_id);
} else {
    header("Location: tickets.php");
}
exit;