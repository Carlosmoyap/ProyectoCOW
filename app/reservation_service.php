<?php

require_once __DIR__ . '/db_config.php';

function process_reservation_request($post, $server)
{
    $result = array(
        'isPost' => isset($server['REQUEST_METHOD']) && $server['REQUEST_METHOD'] === 'POST',
        'errors' => array(),
        'dadesReserva' => array(),
        'dbWarning' => '',
        'saveOk' => false,
        'saveMessage' => '',
        'recentReservations' => array(),
    );

    if (!$result['isPost']) {
        return $result;
    }

    $conn = open_world_connection($result['dbWarning']);
    if ($conn !== null && !ensure_reservation_table($conn)) {
        $result['dbWarning'] = 'No s\'ha pogut crear/verificar la taula clients_reserves.';
    }

    $regexNom = "/^[A-Za-zÀ-ÿ\s]{3,50}$/";
    $regexEmail = "/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/";
    $regexTelefon = "/^(\+34|0034)?[\s]?[6-9][0-9]{8}$/";

    if (empty($post['nom'])) {
        $result['errors'][] = 'El nom és obligatori';
    } else {
        $nom = neteja_dades($post['nom']);
        if (!preg_match($regexNom, $nom)) {
            $result['errors'][] = 'El nom només pot contenir lletres i espais (mínim 3 caràcters)';
        } else {
            $result['dadesReserva']['nom'] = $nom;
        }
    }

    if (empty($post['email'])) {
        $result['errors'][] = 'L\'email és obligatori';
    } else {
        $email = neteja_dades($post['email']);
        if (!preg_match($regexEmail, $email)) {
            $result['errors'][] = 'El format de l\'email no és vàlid';
        } else {
            $result['dadesReserva']['email'] = $email;
        }
    }

    if (empty($post['telefon'])) {
        $result['errors'][] = 'El telèfon és obligatori';
    } else {
        $telefon = neteja_dades($post['telefon']);
        if (!preg_match($regexTelefon, $telefon)) {
            $result['errors'][] = 'El format del telèfon no és vàlid';
        } else {
            $result['dadesReserva']['telefon'] = $telefon;
        }
    }

    if (empty($post['ciutat'])) {
        $result['errors'][] = 'La ciutat és obligatòria';
    } else {
        $cityId = neteja_dades($post['ciutat']);
        if (!ctype_digit($cityId)) {
            $result['errors'][] = 'La ciutat seleccionada no és vàlida';
        } elseif ($conn === null) {
            $fallbackCities = get_fallback_cities_map();
            $cityIdInt = (int)$cityId;
            if (isset($fallbackCities[$cityIdInt])) {
                $result['dadesReserva']['cityId'] = $cityIdInt;
                $result['dadesReserva']['ciutat'] = $fallbackCities[$cityIdInt]['name'];
                $result['dadesReserva']['countryCode'] = $fallbackCities[$cityIdInt]['country_code'];
            } else {
                $result['errors'][] = 'La ciutat seleccionada no és vàlida';
            }
        } else {
            $stmtCity = $conn->prepare('SELECT id, name, country_code FROM cities WHERE id = ? LIMIT 1');
            if ($stmtCity) {
                $cityIdInt = (int)$cityId;
                $stmtCity->bind_param('i', $cityIdInt);
                $stmtCity->execute();
                $resultCity = $stmtCity->get_result();
                if ($resultCity && $resultCity->num_rows > 0) {
                    $cityRow = $resultCity->fetch_assoc();
                    $result['dadesReserva']['cityId'] = (int)$cityRow['id'];
                    $result['dadesReserva']['ciutat'] = $cityRow['name'];
                    $result['dadesReserva']['countryCode'] = $cityRow['country_code'];
                } else {
                    $result['errors'][] = 'La ciutat seleccionada no existeix a la base de dades';
                }
                if ($resultCity) {
                    $resultCity->free();
                }
                $stmtCity->close();
            } else {
                $result['errors'][] = 'Error intern validant la ciutat';
            }
        }
    }

    if (empty($post['dataEntrada']) || empty($post['dataSortida'])) {
        $result['errors'][] = 'Les dates són obligatòries';
    } else {
        $dataEntrada = neteja_dades($post['dataEntrada']);
        $dataSortida = neteja_dades($post['dataSortida']);

        $dateEntrada = strtotime($dataEntrada);
        $dateSortida = strtotime($dataSortida);
        $dateAvui = strtotime(date('Y-m-d'));

        if ($dateEntrada < $dateAvui) {
            $result['errors'][] = 'La data d\'entrada no pot ser anterior a avui';
        } elseif ($dateSortida <= $dateEntrada) {
            $result['errors'][] = 'La data de sortida ha de ser posterior a la data d\'entrada';
        } else {
            $result['dadesReserva']['dataEntradaDB'] = date('Y-m-d', $dateEntrada);
            $result['dadesReserva']['dataSortidaDB'] = date('Y-m-d', $dateSortida);
            $result['dadesReserva']['dataEntrada'] = date('d/m/Y', $dateEntrada);
            $result['dadesReserva']['dataSortida'] = date('d/m/Y', $dateSortida);
            $diferencia = $dateSortida - $dateEntrada;
            $result['dadesReserva']['nits'] = floor($diferencia / (60 * 60 * 24));
        }
    }

    if (empty($post['persones'])) {
        $result['errors'][] = 'El nombre de persones és obligatori';
    } else {
        $persones = neteja_dades($post['persones']);
        if (!in_array($persones, array('1', '2', '3', '4', '5'), true)) {
            $result['errors'][] = 'El nombre de persones no és vàlid';
        } else {
            $result['dadesReserva']['persones'] = $persones;
        }
    }

    if (empty($post['tipusHabitacio'])) {
        $result['errors'][] = 'El tipus d\'habitació és obligatori';
    } else {
        $tipusHabitacio = neteja_dades($post['tipusHabitacio']);
        if (!in_array($tipusHabitacio, array('Individual', 'Doble', 'Suite'), true)) {
            $result['errors'][] = 'El tipus d\'habitació no és vàlid';
        } else {
            $result['dadesReserva']['tipusHabitacio'] = $tipusHabitacio;
        }
    }

    if (!empty($post['comentaris'])) {
        $result['dadesReserva']['comentaris'] = neteja_dades($post['comentaris']);
    }

    if (!empty($result['errors'])) {
        if ($conn !== null) {
            $conn->close();
        }
        return $result;
    }

    $codiReserva = 'RES-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
    $result['dadesReserva']['codiReserva'] = $codiReserva;

    $preuBase = 80;
    if ($result['dadesReserva']['tipusHabitacio'] === 'Doble') {
        $preuBase = 120;
    } elseif ($result['dadesReserva']['tipusHabitacio'] === 'Suite') {
        $preuBase = 200;
    }
    $preuTotal = $preuBase * $result['dadesReserva']['nits'];
    $result['dadesReserva']['preuTotal'] = $preuTotal;

    if ($conn === null) {
        $result['saveMessage'] = 'Reserva validada, pero no s\'ha pogut guardar per falta de connexio a la base de dades.';
        return $result;
    }

    save_reservation($conn, $result, $codiReserva, $preuTotal);
    $result['recentReservations'] = fetch_recent_reservations($conn);
    $conn->close();

    return $result;
}

function open_world_connection(&$dbWarning)
{
    $dbConfig = get_db_config();
    $dbHost = $dbConfig['host'];
    $dbUser = $dbConfig['user'];
    $dbPass = $dbConfig['pass'];
    $dbName = $dbConfig['name'];

    try {
        $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    } catch (mysqli_sql_exception $e) {
        $dbWarning = 'No s\'ha pogut connectar a la base de dades world.';
        return null;
    }

    if ($conn->connect_error) {
        $dbWarning = 'No s\'ha pogut connectar a la base de dades world.';
        return null;
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function ensure_reservation_table($conn)
{
    $sqlReserves = "CREATE TABLE IF NOT EXISTS clients_reserves (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reservation_code VARCHAR(20) NOT NULL UNIQUE,
        client_name VARCHAR(100) NOT NULL,
        email VARCHAR(120) NOT NULL,
        phone VARCHAR(25) NOT NULL,
        city_id INT NOT NULL,
        city_name VARCHAR(120) NOT NULL,
        checkin_date DATE NOT NULL,
        checkout_date DATE NOT NULL,
        nights INT NOT NULL,
        guests INT NOT NULL,
        room_type VARCHAR(20) NOT NULL,
        comments TEXT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_city_id (city_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    return (bool)$conn->query($sqlReserves);
}

function save_reservation($conn, &$result, $codiReserva, $preuTotal)
{
    $insertSql = 'INSERT INTO clients_reserves
        (reservation_code, client_name, email, phone, city_id, city_name, checkin_date, checkout_date, nights, guests, room_type, comments, total_price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmtInsert = $conn->prepare($insertSql);
    if (!$stmtInsert) {
        $result['saveMessage'] = 'La reserva es valida, pero hi ha un error preparant la insercio SQL.';
        return;
    }

    $cityIdInsert = (int)$result['dadesReserva']['cityId'];
    $nitsInsert = (int)$result['dadesReserva']['nits'];
    $personesInsert = (int)$result['dadesReserva']['persones'];
    $comentarisInsert = isset($result['dadesReserva']['comentaris']) ? $result['dadesReserva']['comentaris'] : null;
    $preuInsert = (float)$preuTotal;

    $stmtInsert->bind_param(
        'ssssisssiissd',
        $codiReserva,
        $result['dadesReserva']['nom'],
        $result['dadesReserva']['email'],
        $result['dadesReserva']['telefon'],
        $cityIdInsert,
        $result['dadesReserva']['ciutat'],
        $result['dadesReserva']['dataEntradaDB'],
        $result['dadesReserva']['dataSortidaDB'],
        $nitsInsert,
        $personesInsert,
        $result['dadesReserva']['tipusHabitacio'],
        $comentarisInsert,
        $preuInsert
    );

    if ($stmtInsert->execute()) {
        $result['saveOk'] = true;
        $result['saveMessage'] = 'Reserva guardada correctament a la taula clients_reserves.';
    } else {
        $result['saveMessage'] = 'La reserva es valida, pero no s\'ha pogut inserir a la base de dades.';
    }

    $stmtInsert->close();
}

function fetch_recent_reservations($conn)
{
    $recentReservations = array();
    $recentSql = 'SELECT reservation_code, client_name, city_name, checkin_date, checkout_date, total_price
                  FROM clients_reserves
                  ORDER BY id DESC
                  LIMIT 5';

    $recentResult = $conn->query($recentSql);
    if ($recentResult && $recentResult->num_rows > 0) {
        while ($row = $recentResult->fetch_assoc()) {
            $recentReservations[] = $row;
        }
        $recentResult->free();
    }

    return $recentReservations;
}

function neteja_dades($dada)
{
    $dada = trim($dada);
    $dada = stripslashes($dada);
    $dada = htmlspecialchars($dada, ENT_QUOTES, 'UTF-8');
    return $dada;
}

function get_fallback_cities_map()
{
    return array(
        1001 => array('name' => 'Barcelona', 'country_code' => 'ES'),
        1002 => array('name' => 'Paris', 'country_code' => 'FR'),
        1003 => array('name' => 'Roma', 'country_code' => 'IT'),
        1004 => array('name' => 'Lisboa', 'country_code' => 'PT'),
        1005 => array('name' => 'Valencia', 'country_code' => 'ES'),
    );
}
