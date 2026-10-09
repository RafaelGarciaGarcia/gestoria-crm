<?php
require 'conexion.php';

$nombre      = $_POST['nombre']      ?? '';
$email       = $_POST['email']       ?? '';
$telefono    = $_POST['telefono']    ?? '';
$mensaje     = $_POST['mensaje']     ?? '';
$preferencia = $_POST['preferencia'] ?? '';
$descuentos  = $_POST['descuentos']  ?? '';

if (!$nombre || !$email || !$mensaje) {
    header("Location: /formulario-contacto-rafael.php?error=1");
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO tickets (nombre, email, telefono, mensaje, preferencia, descuentos, fecha, estado)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), 'nuevo')
");

$stmt->execute([
    $nombre,
    $email,
    $telefono,
    $mensaje,
    $preferencia,
    $descuentos
]);

header("Location: /gracias");
exit;