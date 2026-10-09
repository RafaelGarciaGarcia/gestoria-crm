<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php';

$log_id     = intval($_GET['id'] ?? 0);
$cliente_id = intval($_GET['cliente_id'] ?? 0);

if (!$log_id) {
    die("Log no especificado.");
}

// Obtener los datos actuales del log
$stmt = $pdo->prepare("
    SELECT l.*, a.actividad AS nombre_actividad 
    FROM actividad_logs l
    INNER JOIN actividades a ON a.id = l.actividad_id
    WHERE l.id = ?
");
$stmt->execute([$log_id]);
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$log) {
    die("Log no encontrado.");
}

// Procesar el formulario al guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_mensaje = trim($_POST['mensaje'] ?? '');
    $nuevo_estado  = $_POST['estado_en_log'] ?? $log['estado_en_log'];
    $es_urgente    = isset($_POST['urgente']) ? 1 : 0;

    if (!empty($nuevo_mensaje)) {
        $stmtUpdate = $pdo->prepare("
            UPDATE actividad_logs 
            SET mensaje = ?, estado_en_log = ?, urgente = ? 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$nuevo_mensaje, $nuevo_estado, $es_urgente, $log_id]);

        // Redirigir de vuelta a la pantalla previa o al cliente
        if ($cliente_id > 0) {
            header("Location: cliente.php?id=" . $cliente_id);
        } else {
            header("Location: logs.php?actividad_id=" . $log['actividad_id']);
        }
        exit;
    } else {
        $error = "El mensaje o nombre del log no puede estar vacío.";
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Editar Log #<?= $log_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4" style="max-width: 600px;">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Editar Log de Actividad</h5>
        </div>
        <div class="card-body">

            <p class="text-muted">Actividad: <strong><?= htmlspecialchars($log['nombre_actividad']) ?></strong></p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-bold">Mensaje / Descripción del Log</label>
                    <textarea name="mensaje" class="form-control" rows="4" required><?= htmlspecialchars($log['mensaje']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Estado en el log</label>
                    <select name="estado_en_log" class="form-select">
                        <option value="Interesado" <?= $log['estado_en_log'] === 'Interesado' ? 'selected' : '' ?>>🔵 Interesado</option>
                        <option value="Gestionando" <?= $log['estado_en_log'] === 'Gestionando' ? 'selected' : '' ?>>🟡 Gestionando</option>
                        <option value="Terminada" <?= $log['estado_en_log'] === 'Terminada' ? 'selected' : '' ?>>🟢 Terminada</option>
                        <option value="Imposibilitado" <?= $log['estado_en_log'] === 'Imposibilitado' ? 'selected' : '' ?>>⚫ Imposibilitado</option>
                    </select>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="urgente" id="urgente" value="1" <?= $log['urgente'] ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="urgente">
                        🔥 Marcar como urgente
                    </label>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="javascript:history.back()" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>

        </div>
    </div>
</div>

</body>
</html>