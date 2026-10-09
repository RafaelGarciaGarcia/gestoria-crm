<?php
require 'conexion.php';

$nombre = $_POST['nombre'] ?? '';
$email  = $_POST['email'] ?? '';
$tlf    = $_POST['tlf'] ?? '';

$sql = "INSERT INTO clientes (Nombre_y_apellidos, Mail, Tlf, urgente, notas)
        VALUES (?, ?, ?, 0, 'Cliente creado desde ticket')";

$stmt = $pdo->prepare($sql);
$stmt->execute([$nombre, $email, $tlf]);

echo $pdo->lastInsertId();