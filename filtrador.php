<?php

session_start();

require 'conexion.php';



// Obtener estados únicos tal cual están en la BD

$estados = $pdo->query("SELECT DISTINCT estado FROM actividades ORDER BY estado")->fetchAll(PDO::FETCH_COLUMN);



// Obtener usuarios

$usuarios = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

?>



<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">

    <title>Filtrar actividades</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">



    <style>

        .fila-hover:hover { background-color: #f0f8ff; }

        .celda-clickable a {

            display: block; width: 100%; height: 100%;

            padding: 6px 4px; text-decoration: none; color: inherit;

        }

        .celda-clickable a:hover { background-color: #e6f2ff; }

    </style>



    <script>

        function abrirVentana(url) {

            window.open(url, "_blank",

                "width=1200,height=800,top=100,left=100,resizable=yes,scrollbars=yes");

        }

    </script>

</head>



<body class="p-4">



<a href="index.php" class="btn btn-secondary mb-3">Volver</a>



<h3>Filtrar actividades</h3>



<form method="get" action="filtrador.php" class="row g-3">



    <!-- BUSCADOR DE TIPOS -->

    <div class="col-md-4">

        <label class="form-label">Tipo de actividad</label>

        <input type="text" id="buscador_tipo" class="form-control" placeholder="Buscar tipo...">

        <select name="tipo_id" id="resultados_tipo" class="form-select mt-2" size="5" style="display:none;"></select>

    </div>



    <!-- ESTADO -->

    <div class="col-md-4">

        <label class="form-label">Estado</label>

        <select name="estado" class="form-select">

            <option value="">Todos</option>

            <?php foreach ($estados as $e): ?>

                <?php

                $value = ($e === null || $e === '') ? '__NULL__' : $e;

                $label = ($e === null || $e === '') ? 'Null' : $e;

                ?>

                <option value="<?= htmlspecialchars($value) ?>"

                    <?= (isset($_GET['estado']) && $_GET['estado'] === $value) ? 'selected' : '' ?>>

                    <?= htmlspecialchars($label) ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>





    <!-- USUARIO ASIGNADO -->

    <div class="col-md-4">

        <label class="form-label">Asignado a</label>

        <select name="usuario_id" class="form-select">

            <option value="">Todos</option>

            <?php foreach ($usuarios as $u): ?>

                <option value="<?= $u['id'] ?>"

                    <?= (isset($_GET['usuario_id']) && $_GET['usuario_id'] == $u['id']) ? 'selected' : '' ?>>

                    <?= htmlspecialchars($u['nombre']) ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>



    <!-- URGENTE -->

    <div class="col-md-4">

        <label class="form-label">Urgente</label>

        <select name="urgente" class="form-select">

            <option value="">Todos</option>

            <option value="1" <?= (isset($_GET['urgente']) && $_GET['urgente'] === "1") ? 'selected' : '' ?>>Sí</option>

            <option value="0" <?= (isset($_GET['urgente']) && $_GET['urgente'] === "0") ? 'selected' : '' ?>>No</option>

        </select>

    </div>



    <!-- FECHAS -->

    <div class="col-md-4">

        <label class="form-label">Fecha límite (desde)</label>

        <input type="date" name="desde" class="form-control"

               value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>">

    </div>



    <div class="col-md-4">

        <label class="form-label">Fecha límite (hasta)</label>

        <input type="date" name="hasta" class="form-control"

               value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>">

    </div>



    <div class="col-md-12">

        <button class="btn btn-primary">Filtrar</button>

        <a href="filtrador.php" class="btn btn-secondary">Limpiar</a>

    </div>



</form>



<hr>



<?php

// Construcción dinámica del SQL incorporando el último log para mostrarlo y ordenar por él

$query = "SELECT 

            a.*, 

            a.id AS actividad_id,

            c.Nombre_y_apellidos AS cliente,

            c.id AS cliente_id,

            u.nombre AS asignado_nombre,

            (SELECT MAX(l.fecha) FROM actividad_logs l WHERE l.actividad_id = a.id) AS ultimo_log

          FROM actividades a

          INNER JOIN clientes c ON c.id = a.cliente_id

          LEFT JOIN usuarios u ON u.id = a.usuario_asignado_id

          WHERE 1=1";



$params = [];



// Tipo

if (!empty($_GET['tipo_id'])) {

    $query .= " AND a.tipo_id = :tipo_id";

    $params[':tipo_id'] = $_GET['tipo_id'];

}



// Estado

if (isset($_GET['estado']) && $_GET['estado'] !== '') {

    if ($_GET['estado'] === '__NULL__') {

        $query .= " AND (a.estado IS NULL OR a.estado = '')";

    } else {

        $query .= " AND a.estado = :estado";

        $params[':estado'] = $_GET['estado'];

    }

}



// Usuario asignado

if (!empty($_GET['usuario_id'])) {

    $query .= " AND a.usuario_asignado_id = :usuario_id";

    $params[':usuario_id'] = $_GET['usuario_id'];

}



// Urgente

if (isset($_GET['urgente']) && $_GET['urgente'] !== "") {

    $query .= " AND a.urgente = :urgente";

    $params[':urgente'] = $_GET['urgente'];

}



// Fecha desde

if (!empty($_GET['desde'])) {

    $query .= " AND a.fecha_limite >= :desde";

    $params[':desde'] = $_GET['desde'];

}



// Fecha hasta

if (!empty($_GET['hasta'])) {

    $query .= " AND a.fecha_limite <= :hasta";

    $params[':hasta'] = $_GET['hasta'];

}



// Ordenación principal: Las gestiones modificadas más recientemente (último log) primero

$query .= " ORDER BY ultimo_log DESC, a.fecha_limite ASC";



$stmt = $pdo->prepare($query);

$stmt->execute($params);

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($resultados);

?>



<h4>Resultados (<?= $total ?> encontrados)</h4>



<table class="table table-bordered table-hover">

    <thead>

        <tr>

            <th>Cliente</th>

            <th>Actividad</th>

            <th>Estado</th>

            <th>Urgente</th>

            <th>Asignado a</th>

            <th>Fecha límite</th>

            <th>Último log</th>

        </tr>

    </thead>



    <tbody>

        <?php foreach ($resultados as $r): ?>

        <tr class="fila-hover">



            <td class="celda-clickable">

                <a href="#"

                   onclick="event.preventDefault(); abrirVentana('cliente.php?id=<?= $r['cliente_id'] ?>');">

                    <?= htmlspecialchars($r['cliente']) ?>

                </a>

            </td>



            <td class="celda-clickable">

                <a href="#"

                   onclick="event.preventDefault(); abrirVentana('logs.php?actividad_id=<?= $r['actividad_id'] ?>&cliente_id=<?= $r['cliente_id'] ?>');">

                    <?= htmlspecialchars($r['actividad']) ?>

                </a>

            </td>



            <td><?= $r['estado'] ?: 'Null' ?></td>



            <td><?= $r['urgente'] ? '🔥 Sí' : '—' ?></td>



            <td><?= htmlspecialchars($r['asignado_nombre'] ?: '—') ?></td>



            <td>

                <?php

                if (!empty($r['fecha_limite']) && $r['fecha_limite'] !== '0000-00-00') {

                    echo date('d/m/Y', strtotime($r['fecha_limite']));

                }

                ?>

            </td>



            <td>

                <?php

                if (!empty($r['ultimo_log']) && $r['ultimo_log'] !== '0000-00-00 00:00:00') {

                    echo date('d/m/Y H:i', strtotime($r['ultimo_log']));

                } else {

                    echo '—';

                }

                ?>

            </td>



        </tr>

        <?php endforeach; ?>

    </tbody>

</table>



<!-- BUSCADOR AJAX -->

<script>

document.getElementById('buscador_tipo').addEventListener('keyup', function() {

    let texto = this.value.trim();



    if (texto.length < 2) {

        document.getElementById('resultados_tipo').style.display = 'none';

        return;

    }



    fetch('buscar_tipos.php?texto=' + encodeURIComponent(texto))

        .then(res => res.json())

        .then(data => {

            let select = document.getElementById('resultados_tipo');

            select.innerHTML = '';



            if (data.length === 0) {

                select.style.display = 'none';

                return;

            }



            data.forEach(item => {

                let option = document.createElement('option');

                option.value = item.id;

                option.textContent = item.nombre;

                select.appendChild(option);

            });



            select.style.display = 'block';

        });

});



document.getElementById('resultados_tipo').addEventListener('change', function() {

    let id = this.value;

    let texto = this.options[this.selectedIndex].text;



    document.getElementById('buscador_tipo').value = texto;



    let hidden = document.querySelector('input[name="tipo_id"]');

    if (!hidden) {

        hidden = document.createElement('input');

        hidden.type = 'hidden';

        hidden.name = 'tipo_id';

        document.querySelector('form').appendChild(hidden);

    }

    hidden.value = id;



    this.style.display = 'none';

});

</script>



</body>

</html>