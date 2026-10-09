<?php
session_start();
require 'conexion.php';

if (!isset($_GET['id']) || !isset($_GET['cliente_id'])) {
    die("Nota o cliente no especificado.");
}

$nota_id = intval($_GET['id']);
$cliente_id = intval($_GET['cliente_id']);

$stmt = $pdo->prepare("DELETE FROM notas_cliente WHERE id = ?");
$stmt->execute([$nota_id]);

header("Location: cliente.php?id=" . $cliente_id);
exit;
