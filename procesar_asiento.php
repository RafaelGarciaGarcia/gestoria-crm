<?php
session_start();
require 'conexion_contabilidad.php';

$stmt = $pdo->prepare("
    INSERT INTO libro_diario 
    (fecha, cuenta_debe, importe_debe, descripcion_debe, cuenta_haber, importe_haber, descripcion_haber, origen_archivo)
    VALUES
    (:fecha, :cuenta_debe, :importe_debe, :descripcion, :cuenta_haber, :importe_haber, :descripcion, :origen)
");

$stmt->execute([
    ':fecha' => $_POST['fecha'],
    ':cuenta_debe' => $_POST['cuenta_debe'],
    ':importe_debe' => $_POST['importe_debe'],
    ':descripcion' => $_POST['descripcion'],
    ':cuenta_haber' => $_POST['cuenta_haber'],
    ':importe_haber' => $_POST['importe_haber'],
    ':origen' => $_POST['origen_archivo']
]);

header("Location: libro_diario.php");
exit;