<?php
session_start();
require 'conexion.php';

$id = $_GET['id'];
$cliente_id = $_GET['cliente_id'];

$stmt = $pdo->prepare("
    UPDATE actividades 
    SET estado = 'Terminada', urgente = 0 
    WHERE id = :id
");
$stmt->execute([':id' => $id]);

header("Location: logs.php?actividad_id=$id&cliente_id=$cliente_id");
exit;