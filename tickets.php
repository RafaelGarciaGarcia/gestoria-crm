<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php';

$ver_todos = isset($_GET['todos']) && $_GET['todos'] == '1';

// Parámetros de filtrado recibidos por GET
$filtro_texto   = trim($_GET['texto'] ?? '');
$filtro_estado  = $_GET['estado'] ?? '';
$filtro_cliente = intval($_GET['cliente_id'] ?? 0);
$filtro_desde   = $_GET['desde'] ?? '';
$filtro_hasta   = $_GET['hasta'] ?? '';

// 1. Construcción de la consulta SQL principal
$sql = "
    SELECT 
        t.id,
        t.nombre,
        t.email,
        t.telefono,
        t.preferencia,
        t.mensaje,
        t.fecha,
        t.estado,
        t.descuentos,
        t.actividad_id,
        c.id AS cliente_id_real,
        c.Nombre_y_apellidos AS cliente_nombre,
        a.actividad AS actividad_nombre
    FROM tickets t
    LEFT JOIN clientes c ON t.cliente_id = c.id
    LEFT JOIN actividades a ON t.actividad_id = a.id
    WHERE 1=1
";

$params = [];

// RESTRICCIÓN CLAVE: Si NO es "ver todos", excluimos estrictamente los tickets ya asociados o cerrados
if (!$ver_todos) {
    $sql .= " AND t.actividad_id IS NULL AND (t.estado IS NULL OR LOWER(t.estado) NOT IN ('cerrado', 'asignado', 'asociado', 'terminado'))";
}

if ($filtro_texto !== '') {
    $sql .= " AND (t.nombre LIKE :texto OR t.email LIKE :texto OR t.mensaje LIKE :texto)";
    $params[':texto'] = '%' . $filtro_texto . '%';
}

if ($filtro_estado !== '') {
    if ($filtro_estado === '__NULL__') {
        $sql .= " AND (t.estado IS NULL OR t.estado = '')";
    } else {
        $sql .= " AND t.estado = :estado";
        $params[':estado'] = $filtro_estado;
    }
}

if ($filtro_cliente > 0) {
    $sql .= " AND t.cliente_id = :cliente_id";
    $params[':cliente_id'] = $filtro_cliente;
}

if ($filtro_desde !== '') {
    $sql .= " AND t.fecha >= :desde";
    $params[':desde'] = $filtro_desde . ' 00:00:00';
}

if ($filtro_hasta !== '') {
    $sql .= " AND t.fecha <= :hasta";
    $params[':hasta'] = $filtro_hasta . ' 23:59:59';
}

$sql .= " ORDER BY t.fecha DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($tickets);

// 2. Obtener opciones del filtrador (también aplicando la exclusión de asociados en modo pendientes)
$estados_conteo = [];
$clientes_disponibles = [];

$sql_opciones = "
    SELECT t.estado, c.id AS cliente_id, c.Nombre_y_apellidos AS cliente_nombre, t.actividad_id
    FROM tickets t
    LEFT JOIN clientes c ON t.cliente_id = c.id
    WHERE 1=1
";

if (!$ver_todos) {
    $sql_opciones .= " AND t.actividad_id IS NULL AND (t.estado IS NULL OR LOWER(t.estado) NOT IN ('cerrado', 'asignado', 'asociado', 'terminado'))";
}

$params_opciones = [];
if ($filtro_texto !== '') {
    $sql_opciones .= " AND (t.nombre LIKE :texto OR t.email LIKE :texto OR t.mensaje LIKE :texto)";
    $params_opciones[':texto'] = '%' . $filtro_texto . '%';
}
$stmt_opc = $pdo->prepare($sql_opciones);
$stmt_opc->execute($params_opciones);
$resultados_opc = $stmt_opc->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultados_opc as $row) {
    $est = trim($row['estado'] ?? '');
    if ($est === '') {
        $est = 'Sin estado';
    }
    if (!isset($estados_conteo[$est])) {
        $estados_conteo[$est] = 0;
    }
    $estados_conteo[$est]++;

    if (!empty($row['cliente_id'])) {
        $clientes_disponibles[$row['cliente_id']] = $row['cliente_nombre'];
    }
}
ksort($estados_conteo);
asort($clientes_disponibles);

function bolaEstadoTicket($estado, $actividad_id) {
    $estado_lower = strtolower(trim($estado));
    $html_iconos = '';
    
    // 1. Si está asociado a una actividad o su estado es asociado/asignado/cerrado, añadimos el sobre
    if (!empty($actividad_id) || str_contains($estado_lower, 'asignado') || str_contains($estado_lower, 'asociado') || str_contains($estado_lower, 'cerrado')) {
        $html_iconos .= '<span title="Ticket: Asociado" style="font-size: 1.2rem; cursor: default; margin-right: 2px;" aria-label="Ticket">📨</span>';
    }

    // 2. Si además su estado o condición indica que está respondido, acumulamos la flecha/icono de respondido
    if (str_contains($estado_lower, 'respondido')) {
        $html_iconos .= '<span title="Respondido" style="font-size: 1.2rem; cursor: default;" aria-label="Respondido">↩️</span>';
    }

    // 3. Si no se acumuló ninguno de los anteriores, evaluamos los demás estados base
    if ($html_iconos === '') {
        return match(true) {
            str_contains($estado_lower, 'nuevo') => '<span title="Nuevo" style="font-size: 1.2rem; cursor: default;">🔵</span>',
            str_contains($estado_lower, 'terminado') => '<span title="Terminado" style="font-size: 1.2rem; cursor: default;">🟢</span>',
            default => '<span title="Estado: ' . htmlspecialchars($estado) . '" style="font-size: 1.2rem; cursor: default;">🔵</span>'
        };
    }

    return $html_iconos;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Gestión de Tickets</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
body { background:#f8f9fa; }
.table td { vertical-align: middle; }
.fila-hover:hover { background-color: #f0f8ff; }
</style>
</head>

<body class="p-4">

<div class="container-fluid py-2">

    <!-- CABECERA Y BOTONES DE MODO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <?= $ver_todos ? 'Histórico completo de tickets' : 'Tickets pendientes' ?>
        </h2>

        <div class="d-flex gap-2">
            <?php if ($ver_todos): ?>
                <a href="tickets.php" class="btn btn-primary">
                    ↩ Ver tickets pendientes
                </a>
            <?php else: ?>
                <a href="tickets.php?todos=1" class="btn btn-primary">
                    📋 Ver todos los tickets
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- FORMULARIO DE FILTROS DINÁMICOS -->
    <div class="card shadow-sm mb-4 bg-white border-0">
        <div class="card-body">
            <form method="get" action="tickets.php" class="row g-3">
                <?php if ($ver_todos): ?>
                    <input type="hidden" name="todos" value="1">
                <?php endif; ?>

                <!-- BUSCAR TEXTO -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Buscar (Nombre, Email, Mensaje)</label>
                    <input type="text" name="texto" class="form-control form-control-sm" placeholder="Escribe para buscar..." value="<?= htmlspecialchars($filtro_texto) ?>" onchange="this.form.submit()">
                </div>

                <!-- ESTADO (DINÁMICO CON CONTADOR) -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Estado</label>
                    <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos (<?= count($resultados_opc) ?>)</option>
                        <?php foreach ($estados_conteo as $nombre_est => $cantidad): ?>
                            <?php 
                            $val = ($nombre_est === 'Sin estado') ? '__NULL__' : $nombre_est;
                            ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= ($filtro_estado === $val) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($nombre_est)) ?> (<?= $cantidad ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- CLIENTE ASOCIADO (DINÁMICO) -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Cliente asociado</label>
                    <select name="cliente_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos los clientes</option>
                        <?php foreach ($clientes_disponibles as $cid5 => $cnom5): ?>
                            <option value="<?= $cid5 ?>" <?= ($filtro_cliente == $cid5) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cnom5) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- FECHAS (Sin onchange automático para permitir escribir el año con calma) -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Desde fecha</label>
                    <input type="date" name="desde" class="form-control form-control-sm" value="<?= htmlspecialchars($filtro_desde) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold">Hasta fecha</label>
                    <input type="date" name="hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($filtro_hasta) ?>">
                </div>

                <div class="col-md-12 d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm px-4">Filtrar</button>
                    <a href="tickets.php<?= $ver_todos ? '?todos=1' : '' ?>" class="btn btn-secondary btn-sm">Limpiar filtros</a>
                </div>
            </form>
        </div>
    </div>

    <!-- LEYENDA DE ESTADOS -->
    <div class="card shadow-sm mb-4 bg-white border-0">
        <div class="card-body py-2 px-3 d-flex align-items-center gap-4 flex-wrap">
            <span class="fw-bold text-muted small">Leyenda:</span>
            <div class="d-flex align-items-center gap-1 small">
                <span>🔵</span> <span>Pendiente</span>
            </div>
            <div class="d-flex align-items-center gap-1 small">
                <span>🟡</span> <span>Respondido</span>
            </div>
            <div class="d-flex align-items-center gap-1 small">
                <span>📨</span> <span>Asociado</span>
            </div>
        </div>
    </div>

    <h4 class="mb-3">Resultados (<?= $total ?> tickets encontrados)</h4>

    <table class="table table-striped table-hover shadow-sm bg-white rounded">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Cliente asociado</th>
                <th>Actividad asociada</th>
                <th class="text-center">Estado</th>
                <th>Descuentos</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
        <?php if (empty($tickets)): ?>
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    No se encontró ningún ticket con los filtros seleccionados.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
                <tr class="fila-hover">
                    <td><?= $t['id'] ?></td>
                    <td><?= $t['fecha'] ?></td>
                    <td><?= htmlspecialchars($t['nombre']) ?></td>
                    <td><?= htmlspecialchars($t['email']) ?></td>

                    <td>
                        <?php if (!empty($t['cliente_nombre'])): ?>
                            <a href="cliente.php?id=<?= $t['cliente_id_real'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($t['cliente_nombre']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Sin cliente</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= $t['actividad_nombre'] 
                            ? htmlspecialchars($t['actividad_nombre']) 
                            : '<span class="text-muted">Sin actividad</span>' ?>
                    </td>

                    <!-- BOLITA O ICONO ESPECIAL DE TICKET -->
                    <td class="text-center">
                        <?= bolaEstadoTicket($t['estado'], $t['actividad_id'] ?? null) ?>
                    </td>

                    <td><?= htmlspecialchars($t['descuentos']) ?></td>

                    <!-- ACCIONES -->
                    <td>
                        <a href="ticket.php?id=<?= $t['id'] ?>" class="btn btn-primary btn-sm">
                            Ver
                        </a>
                    </td>

                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>