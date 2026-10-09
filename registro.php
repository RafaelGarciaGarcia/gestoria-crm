<?php
require 'conexion.php';

$correo_admin = "garciagarciarafael.2000@gmail.com";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombre === '' || $email === '' || $password === '') {
        $error = "Todos los campos son obligatorios.";
    } else {

        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $error = "Ya existe un usuario con ese correo.";
        } else {

            $hash  = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(16));

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, email, password_hash, token_verificacion, verificado, rol)
                VALUES (:nombre, :email, :hash, :token, 0, 'usuario')
            ");
            $stmt->execute([
                ':nombre' => $nombre,
                ':email'  => $email,
                ':hash'   => $hash,
                ':token'  => $token
            ]);

            $enlace = "https://panel.taxvisionbc.com/verificar.php?token=$token";

            $mensaje_admin =
                "Nuevo registro pendiente de verificación:\n\n".
                "Nombre: $nombre\n".
                "Email: $email\n\n".
                "Enlace de verificación:\n$enlace\n\n".
                "Puedes activar la cuenta manualmente si lo deseas.";

            @mail($correo_admin, "Nuevo registro pendiente", $mensaje_admin, "From: no-reply@panel.taxvisionbc.com");

            $exito = "Registro completado. Revisa tu correo.";
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container" style="max-width: 500px; margin-top: 60px;">

    <div class="card shadow-sm">
        <div class="card-header text-center">
            <h4 class="mb-0">Crear cuenta</h4>
        </div>

        <div class="card-body">

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($exito)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($exito) ?></div>
            <?php endif; ?>

            <form method="post">

                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input name="nombre" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input name="password" type="password" class="form-control" required>
                </div>

                <button class="btn btn-primary w-100">Registrar</button>

            </form>

        </div>
    </div>

</div>

</body>
</html>