<?php
session_start();
require 'conexion.php';

$id = $_GET['id'];
$actividad_id = $_GET['actividad_id'];
$cliente_id = $_GET['cliente_id'];

$stmt = $pdo->prepare("SELECT ruta FROM actividad_archivos WHERE id = :id");
$stmt->execute([':id' => $id]);
$archivo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($archivo) {
    if (file_exists($archivo['ruta'])) {
        unlink($archivo['ruta']);
    }

    $pdo->prepare("DELETE FROM actividad_archivos WHERE id = :id")
        ->execute([':id' => $id]);
}

header("Location: logs.php?actividad_id=$actividad_id&cliente_id=$cliente_id");
exit;
