<?php
session_start();
require 'conexion.php';

$cliente_id = $_POST['cliente_id'];
$nota = $_POST['nota'];

$stmt = $pdo->prepare("
    INSERT INTO notas_cliente (cliente_id, nota)
    VALUES (:cid, :nota)
");
$stmt->execute([
    ':cid' => $cliente_id,
    ':nota' => $nota
]);

header("Location: cliente.php?id=$cliente_id");
exit;