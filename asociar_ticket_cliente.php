<?php
require 'conexion.php';

$ticket_id  = $_POST['ticket_id'] ?? 0;
$cliente_id = $_POST['cliente_id'] ?? 0;

if ($ticket_id && $cliente_id) {

    $pdo->prepare("UPDATE tickets SET cliente_id=?, estado='asociado_cliente' WHERE id=?")
        ->execute([$cliente_id, $ticket_id]);

    $pdo->prepare("INSERT INTO notas_cliente (cliente_id, nota, fecha)
                   VALUES (?, 'Ticket asociado desde formulario', NOW())")
        ->execute([$cliente_id]);

    echo "OK";
    exit;
}

echo "ERROR";