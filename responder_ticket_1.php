<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    die("No autorizado");
}

require_once 'conexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$dir_phpmailer = __DIR__ . '/PHPMailer-7.1.1/src/';
require_once $dir_phpmailer . 'Exception.php';
require_once $dir_phpmailer . 'PHPMailer.php';
require_once $dir_phpmailer . 'SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
    $mensaje   = trim($_POST['mensaje'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];

    if (!$ticket_id || empty($mensaje)) {
        die("Faltan datos obligatorios");
    }

    $stmtTicket = $pdo->prepare("SELECT * FROM tickets WHERE id = ? LIMIT 1");
    $stmtTicket->execute([$ticket_id]);
    $ticket = $stmtTicket->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("El ticket no existe");
    }

    try {
        $stmtInsert = $pdo->prepare("INSERT INTO ticket_respuestas (ticket_id, usuario_id, fecha, mensaje) VALUES (:ticket_id, :usuario_id, NOW(), :mensaje)");
        $stmtInsert->execute([
            ':ticket_id'  => $ticket_id,
            ':usuario_id' => $usuario_id,
            ':mensaje'    => $mensaje
        ]);

        $stmtUpdate = $pdo->prepare("UPDATE tickets SET estado = 'Respondido' WHERE id = ?");
        $stmtUpdate->execute([$ticket_id]);
        
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'host';        
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@tu-dominio.com';         
        $mail->Password   = 'pass';              
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;   
        $mail->Port       = 465;                          
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('info@taxvisionbc.com', 'Soporte CRM');
        $mail->addAddress($ticket['email'], $ticket['nombre']);

        $mail->isHTML(true);
        $mail->Subject = "Respuesta a su consulta (Ticket #" . $ticket_id . ")";
        
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
          <h3>Hola " . htmlspecialchars($ticket['nombre']) . ",</h3>
          <p>Hemos respondido a su ticket de soporte (#" . $ticket_id . "):</p>
          <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #198754; margin: 15px 0;'>
            " . nl2br(htmlspecialchars($mensaje)) . "
          </div>
          <p>Atentamente,<br>El equipo de soporte.</p>
        </body>
        </html>";

        $mail->send();
        
        $mail->AltBody = "Hola " . $ticket['nombre'] . ",\n\n" .
                 "Hemos respondido a su ticket de soporte (#" . $ticket_id . "):\n\n" .
                 $mensaje . "\n\n" .
                 "Atentamente,\nEl equipo de soporte.";

        echo "OK";

    } catch (Exception $e) {
        echo "Error al enviar correo: " . $mail->ErrorInfo;
    } catch (PDOException $e) {
        echo "Error en base de datos: " . $e->getMessage();
    }
} else {
    echo "Método no permitido";
}