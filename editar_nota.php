<?php
session_start();
require 'conexion.php';

if (!isset($_GET['id']) || !isset($_GET['cliente_id'])) {
    die("Nota o cliente no especificado.");
}

$nota_id = intval($_GET['id']);
$cliente_id = intval($_GET['cliente_id']);

// Obtener nota actual
$stmt = $pdo->prepare("SELECT * FROM notas_cliente WHERE id = ?");
$stmt->execute([$nota_id]);
$nota = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$nota) {
    die("Nota no encontrada.");
}

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_nota = trim($_POST['nota']);

    $stmtUpdate = $pdo->prepare("UPDATE notas_cliente SET nota = ? WHERE id = ?");
    $stmtUpdate->execute([$nueva_nota, $nota_id]);

    header("Location: cliente.php?id=" . $cliente_id);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Editar nota</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="p-4">

<h3>Editar nota</h3>

<form method="POST">
    <textarea name="nota" class="form-control" rows="6" required><?= htmlspecialchars($nota['nota']) ?></textarea>

    <div class="mt-3">
        <button class="btn btn-success">Guardar cambios</button>
        <a href="cliente.php?id=<?= $cliente_id ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

</body>
</html>
