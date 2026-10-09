<?php
session_start();
require 'conexion.php';

$actividad_id = $_GET['id'];
$cliente_id = $_GET['cliente_id'];

// Primero borrar logs asociados
$pdo->prepare("DELETE FROM actividad_logs WHERE actividad_id = :id")
    ->execute([':id' => $actividad_id]);

// Luego borrar la actividad
$pdo->prepare("DELETE FROM actividades WHERE id = :id")
    ->execute([':id' => $actividad_id]);

header("Location: cliente.php?id=$cliente_id");
exit;