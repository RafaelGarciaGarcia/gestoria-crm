<?php
session_start();
require 'conexion.php';

$cliente_id = $_GET['id'];

// 1. Borrar logs de actividades del cliente
$pdo->prepare("
    DELETE l FROM actividad_logs l
    INNER JOIN actividades a ON a.id = l.actividad_id
    WHERE a.cliente_id = :cid
")->execute([':cid' => $cliente_id]);

// 2. Borrar actividades del cliente
$pdo->prepare("
    DELETE FROM actividades
    WHERE cliente_id = :cid
")->execute([':cid' => $cliente_id]);

// 3. Borrar notas del cliente
$pdo->prepare("
    DELETE FROM notas_cliente
    WHERE cliente_id = :cid
")->execute([':cid' => $cliente_id]);

// 4. Borrar el cliente
$pdo->prepare("
    DELETE FROM clientes
    WHERE id = :cid
")->execute([':cid' => $cliente_id]);

header("Location: index.php");
exit;