<?php
session_start();

// Validar que el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'app/db_config.php';

$reserva = null;
$error_msg = "";
$success_msg = "";

$config = get_db_config();
try {
    $conn = new PDO("mysql:host=" . $config['host'] . ";dbname=" . $config['name'] . ";charset=utf8", $config['user'], $config['pass']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener la reserva si se pasa el ID correcto en modo vista (GET)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $stmt = $conn->prepare("SELECT * FROM clients_reserves WHERE id = :id AND email = :email");
        $stmt->execute([':id' => $id, ':email' => $_SESSION['user_email']]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reserva) {
            $error_msg = "Reserva no encontrada o no tienes permisos para modificarla.";
        }
    }
    
    // Guardar los datos si es un POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        // Validación de datos
        $new_checkin = trim($_POST['dataEntrada']);
        $new_checkout = trim($_POST['dataSortida']);
        $new_room = trim($_POST['tipusHabitacio']);
        $new_guests = (int)trim($_POST['persones']);
        
        // Convertir string de fechas a timestamp para comparar
        $ts_checkin = strtotime($new_checkin);
        $ts_checkout = strtotime($new_checkout);
        
        if ($ts_checkout <= $ts_checkin) {
             $error_msg = "La fecha de salida debe ser posterior a la fecha de entrada.";
        } elseif ($new_guests < 1 || $new_guests > 10) {
             $error_msg = "El número de personas no es válido.";
        } else {
             // Calcular el nuevo precio aproximado: 
             // Diferencia en días
             $nights = floor(($ts_checkout - $ts_checkin) / 86400);
             
             // Precio base de la ciudad y el hotel ignorado por sencillez para este modulo (ya que no viene del POST). 
             // Simulación de nuevo coste: (En un entorno real llamarías la función de server.php de nuevo)
             // Precio Base: Individual (50), Doble (80), Suite (150)
             $precio_hab = 50; 
             if ($new_room == 'Doble') $precio_hab = 80;
             if ($new_room == 'Suite') $precio_hab = 150;
             
             $new_total = $nights * $precio_hab * $new_guests;
             
             // Actualizar reserva mediante consulta parametrizada (PDO SQLi protection)
             $updateStmt = $conn->prepare("UPDATE clients_reserves SET checkin_date = :checkin, checkout_date = :checkout, nights = :nights, guests = :guests, room_type = :room_type, total_price = :total WHERE id = :id AND email = :email");
             
             // Enlazar parámetros
             $updateStmt->bindParam(':checkin', $new_checkin);
             $updateStmt->bindParam(':checkout', $new_checkout);
             $updateStmt->bindParam(':nights', $nights);
             $updateStmt->bindParam(':guests', $new_guests);
             $updateStmt->bindParam(':room_type', $new_room);
             $updateStmt->bindParam(':total', $new_total);
             $updateStmt->bindParam(':id', $id);
             $updateStmt->bindParam(':email', $_SESSION['user_email']);
             
             if ($updateStmt->execute()) {
                 $success_msg = "¡Reserva modificada con éxito!";
                 // Refrescar el registro mostrado con la nueva información
                 $stmt = $conn->prepare("SELECT * FROM clients_reserves WHERE id = :id AND email = :email");
                 $stmt->execute([':id' => $id, ':email' => $_SESSION['user_email']]);
                 $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
             } else {
                 $error_msg = "Ocurrió un error al actualizar la base de datos.";
             }
        }
        
        // Si hay error en POST, re-rellenar los datos con el array POST temporalmente pero con el formato de BBDD
        if ($error_msg) {
            $reserva = [
                'id' => $id,
                'reservation_code' => $_POST['res_code'],
                'checkin_date' => $new_checkin,
                'checkout_date' => $new_checkout,
                'guests' => $new_guests,
                'room_type' => $new_room,
                'total_price' => $_POST['prev_price']
            ];
        }
    }

} catch(PDOException $e) {
    $error_msg = "Error del sistema de base de datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Reserva - Hotel Paradise</title>
    <link rel="stylesheet" href="bootstrap-3.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <!-- Enlazado script de validaciones original si se desea -->
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container" style="margin-top: 40px;">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <span class="glyphicon glyphicon-edit"></span> Modificar Configuración de la Reserva
                        </h3>
                    </div>
                    <div class="panel-body">
                        <?php if ($error_msg): ?>
                            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success_msg): ?>
                            <div class="alert alert-success">
                                <?php echo $success_msg; ?>
                                <br><br>
                                <a href="reservations.php" class="btn btn-default">Volver a mis reservas</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($reserva && empty($success_msg)): ?>
                            <form action="edit_reservation.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($reserva['id']); ?>">
                                <input type="hidden" name="res_code" value="<?php echo htmlspecialchars($reserva['reservation_code']); ?>">
                                <input type="hidden" name="prev_price" value="<?php echo htmlspecialchars($reserva['total_price']); ?>">
                                
                                <p class="lead">Codi de Reserva Original: <strong><?php echo htmlspecialchars($reserva['reservation_code']); ?></strong></p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dataEntrada">Nueva Fecha de Entrada:</label>
                                            <input type="date" class="form-control" name="dataEntrada" id="dataEntrada" value="<?php echo htmlspecialchars($reserva['checkin_date']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dataSortida">Nueva Fecha de Salida:</label>
                                            <input type="date" class="form-control" name="dataSortida" id="dataSortida" value="<?php echo htmlspecialchars($reserva['checkout_date']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="persones">Número de Personas:</label>
                                            <input type="number" class="form-control" name="persones" id="persones" value="<?php echo htmlspecialchars($reserva['guests']); ?>" min="1" max="10" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Tipus d'Habitació:</label>
                                        <select name="tipusHabitacio" class="form-control" required>
                                            <option value="Individual" <?php echo ($reserva['room_type'] == 'Individual') ? 'selected' : ''; ?>>Individual</option>
                                            <option value="Doble" <?php echo ($reserva['room_type'] == 'Doble') ? 'selected' : ''; ?>>Doble</option>
                                            <option value="Suite" <?php echo ($reserva['room_type'] == 'Suite') ? 'selected' : ''; ?>>Suite</option>
                                        </select>
                                    </div>
                                </div>
                                <hr>
                                
                                <p class="text-muted"><small>Nota: Al cambiar estos valores, tu tarifa total se recalculará en el servidor en función a las tarifas base aplicables, y se verá reflejado en la factura.</small></p>
                                
                                <button type="submit" class="btn btn-warning"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar Cambios</button>
                                <a href="reservations.php" class="btn btn-link">Cancelar y Volver</a>
                            </form>
                        <?php elseif (!$reserva && empty($error_msg)): ?>
                            <div class="alert alert-warning">No se ha especificado la reserva a modificar.</div>
                            <a href="reservations.php" class="btn btn-default">Volver</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="jquery-ui-1.12.1/external/jquery/jquery.js"></script>
    <script src="bootstrap-3.3.7-dist/js/bootstrap.min.js"></script>
</body>
</html>