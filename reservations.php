<?php
session_start();

// Si no está registrado, redirigir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'app/db_config.php';

$reservas = [];
$error_msg = "";

try {
    $config = get_db_config();
    $conn = new PDO("mysql:host=" . $config['host'] . ";dbname=" . $config['name'] . ";charset=utf8", $config['user'], $config['pass']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta con PDO filtrando por el email del usuario en la tabla clients_reserves
    $stmt = $conn->prepare("SELECT id, reservation_code, checkin_date, checkout_date, room_type, guests, total_price, created_at FROM clients_reserves WHERE email = :email ORDER BY created_at DESC");
    $stmt->execute([':email' => $_SESSION['user_email']]);
    
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Base table or view not found') !== false) {
         // La tabla todavía no se ha creado (quizá no ha hecho la primera reserva)
         $reservas = [];
    } else {
        $error_msg = "Error al obtener las reservas: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reservas - Hotel Paradise</title>
    <link rel="stylesheet" href="bootstrap-3.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container" style="margin-top: 50px;">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><span class="glyphicon glyphicon-list-alt"></span> Historial de Mis Reservas</h3>
                    </div>
                    <div class="panel-body">
                        <?php if ($error_msg): ?>
                            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                        <?php endif; ?>

                        <?php if (count($reservas) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Habitación</th>
                                            <th>Personas</th>
                                            <th>Total</th>
                                            <th>Fecha Reserva</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reservas as $reserva): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($reserva['reservation_code'] ?? 'N/A'); ?></strong></td>
                                                <td><?php echo htmlspecialchars($reserva['checkin_date'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($reserva['checkout_date'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($reserva['room_type'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($reserva['guests'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($reserva['total_price'] ?? '0'); ?> €</td>
                                                <td><?php echo date('d-m-Y', strtotime($reserva['created_at'])); ?></td>
                                                <td>
                                                    <!-- Botón de modificar usando Bootstrap -->
                                                    <a href="edit_reservation.php?id=<?php echo urlencode($reserva['id']); ?>" class="btn btn-warning btn-sm">
                                                        <span class="glyphicon glyphicon-pencil"></span> Modificar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No tienes ninguna reserva todavía. <a href="client.php" class="alert-link">¡Anímate a hacer una reserva ahora!</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts al final para que cargue todo más rápido y sin conflictos con navbar -->
    <script src="jquery-ui-1.12.1/external/jquery/jquery.js"></script>
    <script src="bootstrap-3.3.7-dist/js/bootstrap.min.js"></script>
</body>
</html>