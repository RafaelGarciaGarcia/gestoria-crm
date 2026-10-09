<?php
require 'conexion.php';

$texto = $_GET['texto'] ?? '';

$stmt = $pdo->prepare("
    SELECT id, nombre
    FROM actividad_tipos
    WHERE nombre LIKE :t
    ORDER BY nombre ASC
    LIMIT 20
");

$stmt->execute([':t' => "%$texto%"]);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($resultados);
