<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php';

$cliente_id = $_GET['cliente_id'] ?? 0;
$ticket_id  = $_GET['ticket_id'] ?? 0;
$q          = $_GET['q'] ?? '';

// Definir URL de retorno si decide cancelar/volver
$volver_url = "cliente.php?id=" . $cliente_id;
if ($ticket_id > 0) {
    $volver_url = "ticket.php?id=" . $ticket_id . "&modal=actividad&cliente_id=" . $cliente_id . (!empty($q) ? "&q=" . urlencode($q) : "");
}

// Obtener lista de usuarios para asignar
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nueva Actividad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h3 class="mb-4">Nueva Actividad</h3>

    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Acción corregida a insertar_actividad.php -->
            <form action="insertar_actividad.php" method="post">

                <!-- Campos ocultos de contexto -->
                <input type="hidden" name="cliente_id" value="<?= htmlspecialchars($cliente_id) ?>">
                <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket_id) ?>">
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">

                <div class="mb-3">
                    <label class="form-label">Nombre de la actividad</label>
                    <input type="text" name="actividad" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="Interesado">Interesado</option>
                            <option value="Gestionando" selected>Gestionando</option>
                            <option value="Terminada">Terminada</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Asignado a</label>
                        <select name="usuario_asignado_id" class="form-select">
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Fecha límite</label>
                        <input type="date" name="fecha_limite" class="form-control">
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= htmlspecialchars($volver_url) ?>" class="btn btn-secondary">Volver</a>
                    <button type="submit" class="btn btn-primary">Guardar actividad</button>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>