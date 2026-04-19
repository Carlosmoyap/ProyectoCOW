<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/app/client_data.php';
list($cities, $hotels, $dbWarning) = load_client_page_data();
?>
<!--
    CLIENT.PHP - PÀGINA DE RESERVA D'HOTEL
    
    Sessio 4: millores client-side amb JavaScript
    Funció: Mostrar el formulari per efectuar una reserva d'hotel
    
    Implementa:
    - Formulari HTML amb diversos tipus de controls (input, select, radio, textarea)
    - Validació client-side amb expressions regulars (REGEXP) en JavaScript
    - Enviament de dades per mètode POST a server.php
    - Framework Bootstrap per al disseny responsive
-->
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva d'Hotel</title>
    <!-- Bootstrap per al disseny responsive -->
    <link rel="stylesheet" href="bootstrap-3.3.7-dist/css/bootstrap.min.css">
    <!-- Fulls d'estil personalitzats (organitzats en fitxers CSS separats) -->
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/client.css">
</head>
<body>
    <!-- Barra de navegació amb Bootstrap -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="container-reserva">
            <div class="header-reserva">
                <h1><span class="glyphicon glyphicon-home"></span> Reserva del teu Hotel</h1>
                <p class="lead">Completa el formulari per confirmar la teva reserva</p>
            </div>

            <?php if (!empty($dbWarning)) { ?>
                <div class="alert alert-warning">
                    <?php echo htmlspecialchars($dbWarning); ?>
                </div>
            <?php } ?>

            <?php if (!empty($hotels)) { ?>
                <div class="panel panel-info panel-hotels">
                    <div class="panel-heading">
                        <strong>Hotels disponibles</strong>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed table-no-margin">
                            <thead>
                                <tr>
                                    <th>Hotel</th>
                                    <th>Ciutat</th>
                                    <th>Estrelles</th>
                                    <th>Piscina</th>
                                    <th>Spa</th>
                                    <th>Gimnàs</th>
                                    <th>Preu/Nit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hotels as $hotel) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($hotel['name']); ?></td>
                                        <td><?php echo htmlspecialchars($hotel['city_name']); ?></td>
                                        <td><?php echo (int)$hotel['stars']; ?></td>
                                        <td><?php echo ((int)$hotel['has_pool'] === 1) ? 'Si' : 'No'; ?></td>
                                        <td><?php echo ((int)$hotel['has_spa'] === 1) ? 'Si' : 'No'; ?></td>
                                        <td><?php echo ((int)$hotel['has_gym'] === 1) ? 'Si' : 'No'; ?></td>
                                        <td><?php echo number_format((float)$hotel['price_per_night'], 2); ?> EUR</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>

            <!-- 
                FORMULARI DE RESERVA
                Requisit 2: Formulari amb diversos controls i botons
                Requisit 3: Utilitza method="POST" per enviar dades a server.php
                novalidate: Desactiva la validació HTML5 per utilitzar la nostra validació REGEXP
            -->
            <form id="formReserva" action="server.php" method="POST" novalidate>
                
                <!-- Camp de text: Nom complet del client -->
                <div class="form-group">
                    <label for="nom">Nom Complet <span class="required">*</span></label>
                    <input type="text" class="form-control" id="nom" name="nom" placeholder="Joan García López" value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>" required>
                    <span class="error" id="errorNom">El nom ha de contenir només lletres i espais (mínim 3 caràcters)</span>
                </div>

                <!-- Camp email: Correu electrònic -->
                <div class="form-group">
                    <label for="email">Correu Electrònic <span class="required">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="exemple@correu.com" value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>" required>
                    <span class="error" id="errorEmail">Introdueix un correu electrònic vàlid</span>
                </div>

                <!-- Camp telèfon: Número de contacte -->
                <div class="form-group">
                    <label for="telefon">Telèfon <span class="required">*</span></label>
                    <input type="tel" class="form-control" id="telefon" name="telefon" placeholder="+34 600 123 456" required>
                    <span class="error" id="errorTelefon">Format vàlid: +34 600 123 456 o 600123456</span>
                </div>

                <div class="form-group autocomplete-group">
                    <label for="ciutatSearch">Auto-completar ciutat (Ajax)</label>
                    <input type="text" class="form-control" id="ciutatSearch" placeholder="Escriu les inicials de la ciutat..." autocomplete="off">
                    <ul id="ciutatSuggestions" class="suggestion-list"></ul>
                </div>

                <div class="form-group autocomplete-group">
                    <label for="hotelSearch">Auto-completar hotel (Ajax)</label>
                    <input type="text" class="form-control" id="hotelSearch" name="hotelNom" placeholder="Escriu les inicials de l'hotel..." autocomplete="off">
                    <ul id="hotelSuggestions" class="suggestion-list"></ul>
                </div>

                <!-- Select: Selecció de ciutat de destinació -->
                <div class="form-group">
                    <label for="ciutat">Ciutat de Destinació <span class="required">*</span></label>
                    <select class="form-control" id="ciutat" name="ciutat" required>
                        <option value="">Selecciona una ciutat</option>
                        <?php foreach ($cities as $city) { ?>
                            <option value="<?php echo (int)$city['id']; ?>">
                                <?php echo htmlspecialchars($city['name'] . ' (' . $city['country_code'] . ')'); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <span class="error" id="errorCiutat">Selecciona una ciutat</span>
                </div>

                <!-- Camps de data: Entrada i sortida -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dataEntrada">Data d'Entrada <span class="required">*</span></label>
                            <input type="date" class="form-control" id="dataEntrada" name="dataEntrada" required>
                            <span class="error" id="errorDataEntrada">Selecciona una data vàlida</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dataSortida">Data de Sortida <span class="required">*</span></label>
                            <input type="date" class="form-control" id="dataSortida" name="dataSortida" required>
                            <span class="error" id="errorDataSortida">La data de sortida ha de ser posterior a l'entrada</span>
                        </div>
                    </div>
                </div>

                <!-- Nombre de persones -->
                <div class="form-group">
                    <label for="persones">Nombre de Persones <span class="required">*</span></label>
                    <select class="form-control" id="persones" name="persones" required>
                        <option value="">Selecciona</option>
                        <option value="1">1 Persona</option>
                        <option value="2">2 Persones</option>
                        <option value="3">3 Persones</option>
                        <option value="4">4 Persones</option>
                        <option value="5">5+ Persones</option>
                    </select>
                    <span class="error" id="errorPersones">Selecciona el nombre de persones</span>
                </div>

                <!-- Tipus d'habitació -->
                <div class="form-group">
                    <label>Tipus d'Habitació <span class="required">*</span></label>
                    <div class="radio">
                        <label>
                            <input type="radio" name="tipusHabitacio" value="Individual" required> Individual
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="tipusHabitacio" value="Doble"> Doble
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="tipusHabitacio" value="Suite"> Suite
                        </label>
                    </div>
                    <span class="error" id="errorTipusHabitacio">Selecciona un tipus d'habitació</span>
                </div>

                <!-- Comentaris opcionals -->
                <div class="form-group">
                    <label for="comentaris">Comentaris o Peticions Especials</label>
                    <textarea class="form-control" id="comentaris" name="comentaris" rows="4" placeholder="Indica qualsevol petició especial..."></textarea>
                </div>

                <!-- Botó enviar -->
                <div class="form-group actions-group">
                    <button type="button" id="btnPreview" class="btn btn-info">
                        <span class="glyphicon glyphicon-eye-open"></span> Previsualitzar
                    </button>
                    <button type="reset" id="btnNetejar" class="btn btn-default btn-gap-left">
                        <span class="glyphicon glyphicon-refresh"></span> Netejar
                    </button>
                    <button type="submit" class="btn btn-primary btn-reservar btn-gap-left">
                        <span class="glyphicon glyphicon-ok"></span> Confirmar Reserva
                    </button>
                </div>

                <div id="previewReserva" class="alert alert-info preview-reserva">
                    <strong>Previsualitzacio:</strong>
                    <div id="previewContingut" class="preview-contingut"></div>
                </div>

                <div id="ajaxResult" class="ajax-result-box"></div>
            </form>
        </div>
    </div>

    <script src="jquery-ui-1.12.1/external/jquery/jquery.js"></script>
    <script src="bootstrap-3.3.7-dist/js/bootstrap.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/prototype/1.7.3.0/prototype.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/scriptaculous.js?load=effects"></script>
    <script src="js/client-form.js"></script>
</body>
</html>
