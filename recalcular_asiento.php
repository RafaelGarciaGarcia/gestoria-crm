<?php
session_start();
require 'conexion_contabilidad.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vaciar libro mayor
$pdo->query("TRUNCATE libro_mayor");

// Obtener todas las cuentas únicas
$cuentas = $pdo->query("
    SELECT cuenta_debe AS cuenta FROM libro_diario
    UNION
    SELECT cuenta_haber AS cuenta FROM libro_diario
")->fetchAll(PDO::FETCH_COLUMN);

foreach ($cuentas as $cuenta) {

    // Total debe
    $stmtDebe = $pdo->prepare("SELECT SUM(importe_debe) FROM libro_diario WHERE cuenta_debe = :c");
    $stmtDebe->execute([':c' => $cuenta]);
    $totalDebe = $stmtDebe->fetchColumn() ?: 0;

    // Total haber
    $stmtHaber = $pdo->prepare("SELECT SUM(importe_haber) FROM libro_diario WHERE cuenta_haber = :c");
    $stmtHaber->execute([':c' => $cuenta]);
    $totalHaber = $stmtHaber->fetchColumn() ?: 0;

    // Saldo
    $saldo = $totalDebe - $totalHaber;

    // Insertar en libro mayor
    $stmtInsert = $pdo->prepare("
        INSERT INTO libro_mayor (cuenta, descripcion, total_debe, total_haber, saldo, generado_en)
        VALUES (:cuenta, '', :debe, :haber, :saldo, NOW())
    ");

    $stmtInsert->execute([
        ':cuenta' => $cuenta,
        ':debe' => $totalDebe,
        ':haber' => $totalHaber,
        ':saldo' => $saldo
    ]);
}

header("Location: libro_mayor.php");
exit;