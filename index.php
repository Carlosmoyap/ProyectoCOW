<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Paradise</title>
    <link rel="stylesheet" href="bootstrap-3.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container main-container">
        <main class="col-md-9">
            <section id="inicio" class="hero jumbotron">
                <h1>Encuentra tu próximo alojamiento</h1>
                <p>Busca ofertas en hoteles, casas y mucho más...</p>
                <div class="text-center hero-cta-wrapper">
                    <a href="client.php" class="btn btn-primary btn-lg hero-cta-btn">
                        <span class="glyphicon glyphicon-calendar"></span> Hacer una Reserva
                    </a>
                </div>
            </section>
        </main>
        <main class="col-md-3">
            <div class="busqueda-externa">
                <h3>Buscadores Externos</h3>
                <form action="https://www.google.com/search" method="GET" target="_blank">
                    <label for="google-search">Google:</label>
                    <input type="text" name="q" id="google-search" placeholder="Buscar en Google...">
                    <input type="submit" value="Buscar en Google">
                </form>
                <form action="https://es.wikipedia.org/w/index.php" method="GET" target="_blank">
                    <label for="wiki-search">Wikipedia:</label>
                    <input type="text" name="search" id="wiki-search" placeholder="Buscar en Wikipedia...">
                    <input type="submit" value="Buscar en Wikipedia">
                </form>
            </div>
        </main>
    </div>

    <h2>Explora estas propiedades únicas</h2>
    <div class="col-md-offset-2 col-md-12">
        <div class="row habitacion-grid">
            <div class="col-md-4">
                <article class="habitacion thumbnail">
                    <img src="images/hotel-espanya.jpg" alt="Hotel en Barcelona">
                    <div class="caption">
                        <h3>Grand Hotel Central - BCN</h3>
                        <p>Lujo en el corazón del Barrio Gótico.</p>
                        <p><a href="client.php" class="btn btn-primary" role="button">Reservar ahora</a></p>
                    </div>
                </article>
            </div>
            <div class="col-md-4">
                <article class="habitacion thumbnail">
                    <img src="images/hotel-paris.jpg" alt="Apartamento en París">
                    <div class="caption">
                        <h3>Le Marais Apartment</h3>
                        <p>Encanto parisino con vistas a la Torre Eiffel.</p>
                        <p><a href="client.php" class="btn btn-primary" role="button">Reservar ahora</a></p>
                </article>
            </div>
        </div>
    </div>

    <h2>¿Por qué reservar con nosotros?</h2>
    <div class="col-md-12 servicios-lista">
        <ul class="list-group">
            <li class="list-group-item">Precios igualados</li>
            <li class="list-group-item">Cancelación GRATIS en la mayoría de habitaciones</li>
            <li class="list-group-item">Atención al cliente 24/7</li>
            <li class="list-group-item">Más de 2 millones de alojamientos</li>
            <li class="list-group-item">Sin cargos de gestión</li>
        </ul>
    </div>

    <!-- Pie de Página -->
    <footer id="contacto" class="col-md-12">
        <p>Contacto: info@hotelparadise.com | Tel: +34 123 456 789</p>
        <p>Síguenos en nuestras redes sociales:</p>
        <div class="social-links">
            <a href="https://facebook.com">Facebook</a> |
            <a href="https://instagram.com">Instagram</a>
        </div>
        <p>&copy; 2026 Hotel Paradise. Todos los derechos reservados.</p>
    </footer>
    <script src="jquery-ui-1.12.1/external/jquery/jquery.js"></script>
    <script src="bootstrap-3.3.7-dist/js/bootstrap.min.js"></script>
</body>
</html>