<?php
session_start();
require 'conexion.php';

$log_id = $_POST['log_id'];
$cliente_id = $_POST['cliente_id'];
$mensaje = $_POST['mensaje'];

$stmt = $pdo->prepare("
    UPDATE actividad_logs
    SET mensaje = :mensaje
    WHERE id = :id
");
$stmt->execute([
    ':mensaje' => $mensaje,
    ':id' => $log_id
]);

header("Location: cliente.php?id=$cliente_id");
exit;
