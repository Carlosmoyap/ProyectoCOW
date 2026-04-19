<?php
// Iniciar la sesión al principio de todo
session_start();
require_once 'app/db_config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Por favor, introduce tu correo y contraseña.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido (ej. falta el @).";
    } else {
        $config = get_db_config();
        
        try {
            // Conexión usando PDO
            $conn = new PDO("mysql:host=" . $config['host'] . ";dbname=" . $config['name'] . ";charset=utf8", $config['user'], $config['pass']);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Buscar al usuario por correo electrónico
            $stmt = $conn->prepare("SELECT id, full_name, email, password FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar si el usuario existe y si la contraseña cifrada coincide
            if (!$user) {
                $error = "No existe ninguna cuenta registrada con este correo.";
            } elseif (!password_verify($password, $user['password'])) {
                $error = "La contraseña introducida es incorrecta.";
            } else {
                // Generar un nuevo ID de sesión por seguridad (Session Fixation Prevention)
                session_regenerate_id(true);

                // Guardar datos en la sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];

                // Redirigir al inicio o panel del usuario
                header("Location: index.php");
                exit();
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
    <title>Iniciar Sesión - Hotel Paradise</title>
    <link rel="stylesheet" href="bootstrap-3.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container" style="margin-top: 50px;">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Iniciar Sesión</h3>
                    </div>
                    <div class="panel-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="login.php">
                            <div class="form-group">
                                <label for="email">Correo electrónico:</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Contraseña:</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
                        </form>
                        <p class="text-center" style="margin-top: 15px;">¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="jquery-ui-1.12.1/external/jquery/jquery.js"></script>
    <script src="bootstrap-3.3.7-dist/js/bootstrap.min.js"></script>
</body>
</html>