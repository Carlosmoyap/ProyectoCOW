<?php
session_start();

// Si el usuario no ha iniciado sesión, lo redirigimos al login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'app/db_config.php';
$update_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_name = trim($_POST['full_name']);
    $new_email = trim($_POST['email']);
    $user_id = $_SESSION['user_id'];

    if (empty($new_name) || empty($new_email)) {
        $error_message = "Los campos no pueden estar vacíos.";
    } else {
        $config = get_db_config();
        
        try {
            $conn = new PDO("mysql:host=" . $config['host'] . ";dbname=" . $config['name'] . ";charset=utf8", $config['user'], $config['pass']);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Validar que el correo nuevo no exista en otro usuario
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $checkStmt->execute([':email' => $new_email, ':id' => $user_id]);

            if ($checkStmt->rowCount() > 0) {
                $error_message = "El correo proporcionado ya está siendo usado por otra cuenta.";
            } else {
                try {
                    // Iniciamos una transacción (PDO) para asegurar que ambos cambios ocurren o ninguno
                    $conn->beginTransaction();

                    // Actualizar los datos del usuario
                    $updateStmt = $conn->prepare("UPDATE users SET full_name = :full_name, email = :email WHERE id = :id");
                    $updateStmt->bindParam(':full_name', $new_name);
                    $updateStmt->bindParam(':email', $new_email);
                    $updateStmt->bindParam(':id', $user_id);
                    $updateStmt->execute();

                    // Si el correo cambia, actualizamos automáticamente el correo en las reservas asociadas
                    $old_email = $_SESSION['user_email'];
                    if ($old_email !== $new_email) {
                        $updateReserves = $conn->prepare("UPDATE clients_reserves SET email = :new_email, client_name = :new_name WHERE email = :old_email");
                        $updateReserves->execute([':new_email' => $new_email, ':new_name' => $new_name, ':old_email' => $old_email]);
                    } elseif ($_SESSION['user_name'] !== $new_name) {
                        // Solo cambió el nombre, actualizamos el nombre en las reservas
                        $updateReserves = $conn->prepare("UPDATE clients_reserves SET client_name = :new_name WHERE email = :email");
                        $updateReserves->execute([':new_name' => $new_name, ':email' => $old_email]);
                    }

                    $conn->commit();

                    // Actualizar los datos también en la sesión
                    $_SESSION['user_name'] = $new_name;
                    $_SESSION['user_email'] = $new_email;
                    
                    $update_message = "Perfil y reservas actualizados correctamente con tus nuevos datos.";
                } catch(Exception $e) {
                    $conn->rollBack();
                    $error_message = "Ocurrió un error al actualizar la base de datos: " . $e->getMessage();
                }
            }

        } catch(PDOException $e) {
            $error_message = "Error en la base de datos: " . $e->getMessage();
        }
    }
}

// Cargar los datos actuales de la base de datos (seguro)
try {
    $config = get_db_config();
    $conn = new PDO("mysql:host=" . $config['host'] . ";dbname=" . $config['name'] . ";charset=utf8", $config['user'], $config['pass']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT full_name, email, created_at FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Si hay error mostramos datos de la sesión genéricamente
    $user_data = [
        'full_name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'created_at' => 'Desconocido'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - Hotel Paradise</title>
    <link rel="stylesheet" href="bootstrap-3.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container" style="margin-top: 50px;">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Mi Perfil (<?php echo htmlspecialchars($user_data['full_name']); ?>)</h3>
                    </div>
                    <div class="panel-body">
                        
                        <?php if ($update_message): ?>
                            <div class="alert alert-success"><?php echo $update_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="profile.php">
                            <div class="form-group">
                                <label for="full_name">Nombre Completo:</label>
                                <input type="text" class="form-control" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Dirección de correo electrónico:</label>
                                <input type="email" class="form-control" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                            </div>
                            
                            <hr>
                            <p><strong>Miembro desde:</strong> <?php echo htmlspecialchars($user_data['created_at']); ?></p>

                            <button type="submit" class="btn btn-success">Actualizar Perfil</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="jquery-ui-1.12.1/external/jquery/jquery.js"></script>
    <script src="bootstrap-3.3.7-dist/js/bootstrap.min.js"></script>
</body>
</html>