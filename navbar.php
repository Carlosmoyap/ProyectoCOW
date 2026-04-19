<?php
// Comprobar si la sesión ya fue iniciada antes de llamar start para evitar errores
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="index.php">Hotel Paradise</a>
            </div>
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="index.php#inicio">Inicio</a></li>
                    <li><a href="client.php" class="btn-nav-reserva">Reservar Ahora</a></li>
                    <li><a href="index.php#ofertas">Ofertas</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Mostrado solo cuando el usuario está logueado -->
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                ¡Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="profile.php">Mi Perfil</a></li>
                                <li><a href="reservations.php">Mis Reservas</a></li>
                                <li role="separator" class="divider"></li>
                                <li><a href="logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Mostrado cuando el usuario NO está logueado -->
                        <li><a href="login.php"><strong>Iniciar Sesión</strong></a></li>
                        <li><a href="register.php">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>