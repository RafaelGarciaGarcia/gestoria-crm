<?php
session_start();
require 'conexion_contabilidad.php';

// Aseguramos que el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    die("No tienes permiso para acceder aquí.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Excel de Cliente</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

<div class="container py-4">

    <a href="index.php" class="btn btn-secondary mb-3">Volver</a>

    <h2 class="mb-4">📤 Subir Excel de Cliente</h2>

    <form action="procesar_excel.php" method="post" enctype="multipart/form-data">

        <div class="mb-3">
            <label>Cliente</label>
            <input type="text" name="cliente" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Archivo Excel (.xlsx)</label>
            <input type="file" name="excel" class="form-control" required>
        </div>

        <button class="btn btn-primary">Procesar Excel</button>
    </form>

</div>

</body>
</html>