<?php
require 'conexion.php';

$ticket_id = $_POST['ticket_id'] ?? 0;

if (!$ticket_id) {
    echo "ERROR";
    exit;
}

$stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);

echo "OK";