<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php';

$cliente_id = $_GET['cliente_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nota = trim($_POST['nota']);

    if ($nota !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO notas_cliente (cliente_id, usuario_id, nota)
            VALUES (:cliente_id, :usuario_id, :nota)
        ");
        $stmt->execute([
            ':cliente_id' => $cliente_id,
            ':usuario_id' => $_SESSION['usuario_id'],
            ':nota' => $nota
        ]);
    }

    header("Location: cliente.php?id=$cliente_id");
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nueva nota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h3 class="mb-3">Añadir nota</h3>

    <form method="post" class="card p-3">
        <textarea name="nota" class="form-control mb-3" rows="5" required></textarea>
        <button class="btn btn-success">Guardar nota</button><br>
        <a href="cliente.php?id=<?= $cliente_id ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

</body>
</html>