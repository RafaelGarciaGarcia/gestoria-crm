<?php 
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php';

$stmt = $pdo->prepare("
SELECT 
    c.id,
    c.`Nombre_y_apellidos`,
    c.`BSN`,
    c.`Tlf`,
    c.`Mail`,

    -- Nº de urgentes
    (
        SELECT COUNT(*) 
        FROM actividades a1
        WHERE a1.cliente_id = c.id
        AND a1.urgente = 1
    ) AS urgencias,

    -- Nº de gestionando
    (
        SELECT COUNT(*)
        FROM actividades a2
        WHERE a2.cliente_id = c.id
        AND a2.estado = 'Gestionando'
    ) AS gestionando,

    -- Fecha límite más cercana
    (
        SELECT MIN(a4.fecha_limite)
        FROM actividades a4
        WHERE a4.cliente_id = c.id
        AND a4.fecha_limite IS NOT NULL
    ) AS fecha_mas_cercana

FROM clientes c

GROUP BY c.id

ORDER BY 
    urgencias DESC,
    gestionando DESC,
    fecha_mas_cercana IS NULL ASC,
    fecha_mas_cercana ASC,
    c.`Nombre_y_apellidos` ASC
");

$stmt->execute();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function abrirVentana(url) {
            window.open(
                url,
                "_blank",
                "width=1200,height=800,top=100,left=100,resizable=yes,scrollbars=yes"
            );
        }
    </script>
</head>
<body class="bg-light">

<nav class="navbar navbar-light bg-white border-bottom mb-4">
    <div class="container-fluid d-flex justify-content-between">
        <span class="navbar-brand mb-0 h5">
            Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
        </span>

        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" 
                type="button" 
                data-bs-toggle="dropdown">
                    Cuenta
            </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="tickets.php" target="_blank">Tickets</a></li>
                    <li><a class="dropdown-item" href="logout.php">Cerrar sesión</a></li>
                </ul>
        </div>

    </div>
</nav>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Clientes</h1>
        <a href="filtrador.php" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
            Filtrar actividades
        </a>
        <a href="nuevo_cliente.php" class="btn btn-success" target="_blank" rel="noopener noreferrer">
            👤➕Nuevo cliente
        </a>
        <a href="contabilidad/index_contabilidad.php?id_usuario=<?= $_SESSION['usuario_id'] ?>" class="btn btn-warning btn-lg" target="_blank" rel="noopener noreferrer">
            📁 Contabilidad
        </a>
    </div>

    <div class="card shadow-sm mb-4 bg-white border-0">
        <div class="card-body py-2 px-3 d-flex align-items-center gap-4 flex-wrap">
            <span class="fw-bold text-muted small">Leyenda:</span>
            <div class="d-flex align-items-center gap-1 small">
                <span>🟡</span> <span>Gestionando</span>
            </div>
            <div class="d-flex align-items-center gap-1 small">
                <span>🔥</span> <span>Asignado</span>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <input type="text" id="buscador" class="form-control" placeholder="Buscar por nombre, BSN, teléfono o mail...">
    </div>

    <div id="resultado">
        <table class="table table-striped table-hover shadow-sm">
            <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th>BSN</th>
                <th>Teléfono</th>
                <th>Urgente</th>
                <th>Pendiente</th>
                <th>Fecha límite</th>
            </tr>
            </thead>
    
            <tbody id="tabla-clientes">
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr onclick="window.open('cliente.php?id=<?= $row['id'] ?>', '_blank');" style="cursor:pointer;">
                    <td><?= htmlspecialchars($row['Nombre_y_apellidos']) ?></td>
                    <td><?= htmlspecialchars($row['BSN']) ?></td>
                    <td><?= htmlspecialchars($row['Tlf']) ?></td>
                    
                    <td><?= $row['urgencias'] > 0 ? "🔥" : "—" ?></td>
                    <td><?= $row['gestionando'] > 0 ? "🟡" : "—" ?></td>
                    
                    <td>
                        <?= $row['fecha_mas_cercana']
                            ? date("d/m/Y", strtotime($row['fecha_mas_cercana']))
                            : '-' ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>


</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('buscador').addEventListener('keyup', function () {
    const q = this.value;

    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'buscar.php?q=' + encodeURIComponent(q), true);

    xhr.onload = function () {
        if (xhr.status === 200) {
            document.getElementById('tabla-clientes').innerHTML = xhr.responseText;
        }
    };

    xhr.send();
});
</script>
</body>
</html>