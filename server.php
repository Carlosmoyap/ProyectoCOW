<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/app/reservation_service.php';
require_once __DIR__ . '/app/client_data.php';

if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1') {
    handle_ajax_request();
    exit;
}

function handle_ajax_request()
{
    header('Content-Type: application/json; charset=utf-8');

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
    if ($action === 'autocomplete') {
        echo json_encode(build_autocomplete_payload());
        return;
    }

    if ($action === 'reserve') {
        $result = process_reservation_request($_POST, $_SERVER);
        echo json_encode(build_reservation_ajax_payload($result));
        return;
    }

    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'Invalid action'));
}

function build_autocomplete_payload()
{
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';

    if ($query === '' || !in_array($type, array('city', 'hotel'), true)) {
        return array('ok' => true, 'items' => array());
    }

    list($cities, $hotels) = load_client_page_data();
    $items = array();

    if ($type === 'city') {
        foreach ($cities as $city) {
            if (stripos($city['name'], $query) === 0) {
                $items[] = array(
                    'id' => (int)$city['id'],
                    'name' => $city['name'],
                    'country_code' => $city['country_code'],
                    'label' => $city['name'] . ' (' . $city['country_code'] . ')',
                );
            }
            if (count($items) >= 8) {
                break;
            }
        }
    }

    if ($type === 'hotel') {
        foreach ($hotels as $hotel) {
            if (stripos($hotel['name'], $query) === 0) {
                $items[] = array(
                    'name' => $hotel['name'],
                    'city_name' => $hotel['city_name'],
                    'label' => $hotel['name'] . ' - ' . $hotel['city_name'],
                );
            }
            if (count($items) >= 8) {
                break;
            }
        }
    }

    return array('ok' => true, 'items' => $items);
}

function build_reservation_ajax_payload($result)
{
    if (!empty($result['errors'])) {
        return array(
            'ok' => true,
            'success' => false,
            'errors' => $result['errors'],
        );
    }

    $dadesReserva = $result['dadesReserva'];
    $localMode = !$result['saveOk'] && !empty($result['dbWarning']);

    return array(
        'ok' => true,
        'success' => true,
        'reservation' => array(
            'code' => $dadesReserva['codiReserva'],
            'nom' => $dadesReserva['nom'],
            'email' => $dadesReserva['email'],
            'telefon' => $dadesReserva['telefon'],
            'ciutat' => $dadesReserva['ciutat'],
            'countryCode' => $dadesReserva['countryCode'],
            'dataEntrada' => $dadesReserva['dataEntrada'],
            'dataSortida' => $dadesReserva['dataSortida'],
            'persones' => $dadesReserva['persones'],
            'tipusHabitacio' => $dadesReserva['tipusHabitacio'],
            'preuTotal' => number_format((float)$dadesReserva['preuTotal'], 2),
        ),
        'saveOk' => $result['saveOk'],
        'dbWarning' => $result['dbWarning'],
        'saveMessage' => $result['saveMessage'],
        'localMode' => $localMode,
    );
}

$result = process_reservation_request($_POST, $_SERVER);
$dadesReserva = $result['dadesReserva'];
$errors = $result['errors'];
$showLocalModeNotice = !$result['saveOk'] && !empty($result['dbWarning']);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmacio de Reserva</title>
    <link rel="stylesheet" href="bootstrap-3.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/server.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="container-confirmacio">
            <?php if (!$result['isPost']) { ?>
                <div class="header-confirmacio">
                    <span class="glyphicon glyphicon-warning-sign error-icon"></span>
                    <h1>Acces No Valid</h1>
                    <p class="lead">No s'han rebut dades de reserva</p>
                </div>

                <div class="alert alert-warning alert-custom">
                    Si us plau, accedeix a aquesta pagina a traves del formulari de reserva.
                </div>

                <div class="text-center">
                    <a href="client.php" class="btn btn-primary btn-lg btn-tornar">
                        <span class="glyphicon glyphicon-home"></span> Anar al formulari de reserva
                    </a>
                </div>
            <?php } elseif (empty($errors)) { ?>
                <div class="header-confirmacio">
                    <span class="glyphicon glyphicon-ok-circle success-icon"></span>
                    <h1>Reserva Confirmada!</h1>
                    <p class="lead">La teva reserva s'ha processat correctament</p>
                </div>

                <div class="codi-reserva">
                    Codi de Reserva: <?php echo htmlspecialchars($dadesReserva['codiReserva']); ?>
                </div>

                <div class="alert alert-success alert-custom">
                    <strong>Important:</strong> Guarda aquest codi per a futures consultes.
                    Email de contacte registrat:
                    <strong><?php echo htmlspecialchars($dadesReserva['email']); ?></strong>
                </div>

                <?php if ($showLocalModeNotice) { ?>
                    <div class="alert alert-info alert-custom">
                        Reserva confirmada en mode local: la base de dades no esta disponible ara mateix i la reserva no s'ha guardat.
                    </div>
                <?php } elseif (!empty($result['dbWarning'])) { ?>
                    <div class="alert alert-warning alert-custom"><?php echo htmlspecialchars($result['dbWarning']); ?></div>
                <?php } ?>

                <?php if (!empty($result['saveMessage']) && !$showLocalModeNotice) { ?>
                    <div class="alert <?php echo $result['saveOk'] ? 'alert-info' : 'alert-warning'; ?> alert-custom">
                        <?php echo htmlspecialchars($result['saveMessage']); ?>
                    </div>
                <?php } ?>

                <div class="info-reserva">
                    <h3><span class="glyphicon glyphicon-list-alt"></span> Detalls de la Reserva</h3>
                    <hr>

                    <div class="info-item">
                        <span class="info-label">Nom del client:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dadesReserva['nom']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dadesReserva['email']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Telefon:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dadesReserva['telefon']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Destinacio:</span>
                        <span class="info-value"><strong><?php echo htmlspecialchars($dadesReserva['ciutat']); ?> (<?php echo htmlspecialchars($dadesReserva['countryCode']); ?>)</strong></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Data d'entrada:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dadesReserva['dataEntrada']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Data de sortida:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dadesReserva['dataSortida']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Nombre de nits:</span>
                        <span class="info-value"><strong><?php echo (int)$dadesReserva['nits']; ?> nit<?php echo ((int)$dadesReserva['nits'] > 1) ? 's' : ''; ?></strong></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Nombre de persones:</span>
                        <span class="info-value"><?php echo (int)$dadesReserva['persones']; ?> persona<?php echo ((int)$dadesReserva['persones'] > 1) ? 'es' : ''; ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Tipus d'habitacio:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dadesReserva['tipusHabitacio']); ?></span>
                    </div>

                    <?php if (!empty($dadesReserva['comentaris'])) { ?>
                        <div class="info-item">
                            <span class="info-label">Comentaris:</span>
                            <span class="info-value"><?php echo nl2br(htmlspecialchars($dadesReserva['comentaris'])); ?></span>
                        </div>
                    <?php } ?>

                    <div class="info-item info-total">
                        <span class="info-label info-total-label">Preu Total:</span>
                        <span class="info-value info-total-value"><?php echo number_format((float)$dadesReserva['preuTotal'], 2); ?> EUR</span>
                    </div>
                </div>

                <?php if (!empty($result['recentReservations'])) { ?>
                    <div class="info-reserva recent-reservations">
                        <h3><span class="glyphicon glyphicon-time"></span> Ultimes reserves registrades</h3>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Codi</th>
                                        <th>Client</th>
                                        <th>Ciutat</th>
                                        <th>Entrada</th>
                                        <th>Sortida</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result['recentReservations'] as $row) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['reservation_code']); ?></td>
                                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['city_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['checkin_date']); ?></td>
                                            <td><?php echo htmlspecialchars($row['checkout_date']); ?></td>
                                            <td><?php echo number_format((float)$row['total_price'], 2); ?> EUR</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php } ?>

                <div class="text-center">
                    <a href="client.php" class="btn btn-primary btn-lg btn-tornar">
                        <span class="glyphicon glyphicon-arrow-left"></span> Fer una altra reserva
                    </a>
                </div>
            <?php } else { ?>
                <div class="header-confirmacio">
                    <span class="glyphicon glyphicon-remove-circle error-icon"></span>
                    <h1>Error en la Reserva</h1>
                    <p class="lead">S'han detectat errors en les dades enviades</p>
                </div>

                <div class="alert alert-danger alert-custom">
                    <h4><span class="glyphicon glyphicon-exclamation-sign"></span> Errors detectats:</h4>
                    <ul>
                        <?php foreach ($errors as $error) { ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php } ?>
                    </ul>
                </div>

                <div class="text-center">
                    <a href="client.php" class="btn btn-warning btn-lg btn-tornar">
                        <span class="glyphicon glyphicon-arrow-left"></span> Tornar al formulari
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>

    <script src="jquery-ui-1.12.1/external/jquery/jquery.js"></script>
    <script src="bootstrap-3.3.7-dist/js/bootstrap.min.js"></script>
</body>
</html>
