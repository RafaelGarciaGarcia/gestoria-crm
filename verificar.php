<?php
require 'conexion.php';

$token = $_GET['token'] ?? '';

if ($token === '') {
    die("Token inválido");
}

$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_verificacion = :token");
$stmt->execute([':token' => $token]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("Token no válido o ya usado");
}

$stmt = $pdo->prepare("
    UPDATE usuarios
    SET verificado = 1, token_verificacion = NULL
    WHERE id = :id
");
$stmt->execute([':id' => $usuario['id']]);

echo "Cuenta verificada correctamente. Ya puedes iniciar sesión.";