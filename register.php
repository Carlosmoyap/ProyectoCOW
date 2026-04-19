<?php
// Iniciar la sesión al principio de todo
session_start();
require_once 'app/db_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($fullName) || empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, introduce un correo electrónico válido (ej. usuario@dominio.com).";
    } elseif (strlen($password) < 6) {
        $error = "Por seguridad, la contraseña debe tener al menos 6 caracteres.";
    } else {
        $config = get_db_config();
        
        try {
            // Conexión usando PDO para máxima seguridad (prevenir SQL Injection)
            $conn = new PDO("mysql:host=" . $config['host'] . ";dbname=" . $config['name'] . ";charset=utf8", $config['user'], $config['pass']);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Comprobar si el email ya existe
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Este correo electrónico ya tiene una cuenta asociada. ¿Has olvidado tu contraseña o quieres iniciar sesión?";
            } else {
                // Encriptar la contraseña usando bcrypt
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insertar el nuevo usuario en la base de datos
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (:full_name, :email, :password)");
                $stmt->bindParam(':full_name', $fullName);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                
                if ($stmt->execute()) {
                    $success = "¡Registro completado con éxito! Ahora puedes <a href='login.php'>iniciar sesión</a>.";
                } else {
                    $error = "Ocurrió un error inesperado al registrar el usuario. Inténtalo de nuevo más tarde.";
                }
            }
        } catch(PDOException $e) {
            $error = "Error de servidor: No hemos podido conectar con la base de datos en este momento.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Hotel Paradise</title>
    <link rel="stylesheet" href="bootstrap-3.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'navbar.php'; // Lo crearemos en un momento ?>
    
    <div class="container" style="margin-top: 50px;">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Registro de Usuario</h3>
                    </div>
                    <div class="panel-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="register.php">
                            <div class="form-group">
                                <label for="full_name">Nombre completo:</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Correo electrónico:</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Contraseña:</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Registrarse</button>
                        </form>
                        <p class="text-center" style="margin-top: 15px;">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="jquery-ui-1.12.1/external/jquery/jquery.js"></script>
    <script src="bootstrap-3.3.7-dist/js/bootstrap.min.js"></script>
</body>
</html>