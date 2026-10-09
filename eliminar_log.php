<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("No tienes permiso para realizar esta acción.");
}

if (!isset($_GET['id']) || !isset($_GET['cliente_id'])) {
    die("Parámetros incompletos.");
}

$log_id = $_GET['id'];
$cliente_id = $_GET['cliente_id'];
$usuario_id = $_SESSION['usuario_id'];

//Verificar que el log existe y pertenece al cliente correcto
$stmt = $pdo->prepare("
    SELECT l.id, l.actividad_id, a.usuario_asignado_id, a.cliente_id
    FROM actividad_logs l
    INNER JOIN actividades a ON a.id = l.actividad_id
    WHERE l.id = :id
");
$stmt->execute([':id' => $log_id]);
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$log) {
    die("El log no existe.");
}

//Verificar que el log pertenece al cliente correcto
if ($log['cliente_id'] != $cliente_id) {
    die("Este log no pertenece a este cliente.");
}

//Verificar que el usuario tiene permiso (solo el asignado puede borrar)
if ($log['usuario_asignado_id'] != $usuario_id) {
    die("No tienes permiso para eliminar este log.");
}

// Eliminar el log
$delete = $pdo->prepare("DELETE FROM actividad_logs WHERE id = :id");
$delete->execute([':id' => $log_id]);

//Redirigir con mensaje de éxito
header("Location: cliente.php?id=" . $cliente_id . "&log_eliminado=1");
exit;
