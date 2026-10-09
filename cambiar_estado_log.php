<?php
session_start();
require 'conexion.php';

// Habilitar reporte de errores PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$log_id       = intval($_POST['log_id'] ?? 0);
$nuevo_estado = trim($_POST['estado'] ?? '');
$actividad_id = intval($_POST['actividad_id'] ?? 0);

if ($log_id > 0 && !empty($nuevo_estado)) {
    try {
        // 1. Actualizar el estado del log
        $stmt = $pdo->prepare("UPDATE actividad_logs SET estado_en_log = ? WHERE id = ?");
        $stmt->execute([$nuevo_estado, $log_id]);

        // 2. Actualizar también el estado general de la actividad si aplica
        if ($actividad_id > 0) {
            $stmtA = $pdo->prepare("UPDATE actividades SET estado = ? WHERE id = ?");
            $stmtA->execute([$nuevo_estado, $actividad_id]);
        }

        echo "OK";
    } catch (Exception $e) {
        echo "ERROR_BD: " . $e->getMessage();
    }
} else {
    echo "ERROR_PARAMETROS";
}
exit;