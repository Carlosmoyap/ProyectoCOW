document.observe('dom:loaded', function() {
    var nomField = $('nom');
    var emailField = $('email');
    var telefonField = $('telefon');
    var ciutatField = $('ciutat');
    var dataEntradaField = $('dataEntrada');
    var dataSortidaField = $('dataSortida');
    var personesField = $('persones');
    var ciutatSearchField = $('ciutatSearch');
    var hotelSearchField = $('hotelSearch');
    var ciutatSuggestions = $('ciutatSuggestions');
    var hotelSuggestions = $('hotelSuggestions');
    var previewBox = $('previewReserva');
    var previewContent = $('previewContingut');
    var ajaxResult = $('ajaxResult');

    var ciutatTimer = null;
    var hotelTimer = null;

    var avui = new Date().toISOString().split('T')[0];
    dataEntradaField.setAttribute('min', avui);
    dataSortidaField.setAttribute('min', avui);

    var regexNom = /^[A-Za-zÀ-ÿ\s]{3,50}$/;
    var regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    var regexTelefon = /^(\+34|0034)?[\s]?[6-9][0-9]{8}$/;

    function hasEffects() {
        return typeof Effect !== 'undefined';
    }

    function revealPreview() {
        if (hasEffects()) {
            if (!previewBox.visible()) {
                Effect.BlindDown(previewBox, { duration: 0.35 });
            }
        } else {
            previewBox.show();
        }
    }

    function hidePreview() {
        if (hasEffects()) {
            if (previewBox.visible()) {
                Effect.BlindUp(previewBox, { duration: 0.2 });
            }
        } else {
            previewBox.hide();
        }
    }

    function emphasizeField(field) {
        if (hasEffects()) {
            Effect.Highlight(field, { startcolor: '#f2dede', endcolor: '#ffffff', duration: 0.7 });
        }
    }

    function markError(field, errorId, hasError) {
        var errorNode = $(errorId);
        if (hasError) {
            field.addClassName('has-error');
            errorNode.show();
            emphasizeField(field);
        } else {
            field.removeClassName('has-error');
            errorNode.hide();
        }
        return !hasError;
    }

    function requestAutocomplete(type, query) {
        new Ajax.Request('server.php', {
            method: 'get',
            parameters: {
                ajax: '1',
                action: 'autocomplete',
                type: type,
                q: query
            },
            onSuccess: function(response) {
                var data = response.responseJSON || response.responseText.evalJSON(true);
                if (!data || !data.ok) {
                    return;
                }

                var items = data.items || [];

                if (type === 'city') {
                    renderSuggestions(ciutatSuggestions, items, function(item) {
                        ciutatSearchField.value = item.name;
                        ciutatField.value = item.id;
                        validateCiutat();
                        ciutatSuggestions.hide();
                    });
                }

                if (type === 'hotel') {
                    renderSuggestions(hotelSuggestions, items, function(item) {
                        hotelSearchField.value = item.name;
                        hotelSuggestions.hide();
                    });
                }
            }
        });
    }

    function renderSuggestions(listNode, items, onPick) {
        if (!listNode) {
            return;
        }

        listNode.update('');
        if (!items || items.length === 0) {
            listNode.hide();
            return;
        }

        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var li = new Element('li', { className: 'suggestion-item' });
            li.update(item.label);
            li.observe('click', (function(selectedItem) {
                return function() {
                    onPick(selectedItem);
                };
            })(item));
            listNode.insert(li);
        }

        listNode.show();
    }

    function renderAjaxResult(payload) {
        if (!ajaxResult) {
            return;
        }

        var html = '';
        if (!payload.success) {
            html += '<div class="alert alert-danger">';
            html += '<strong>Error en la reserva:</strong><ul>';
            var errs = payload.errors || [];
            for (var i = 0; i < errs.length; i++) {
                var err = errs[i];
                html += '<li>' + err.escapeHTML() + '</li>';
            }
            html += '</ul></div>';
            ajaxResult.update(html);
            ajaxResult.show();
            if (hasEffects()) {
                Effect.Shake($('formReserva'), { distance: 10, duration: 0.45 });
            }
            return;
        }

        html += '<div class="alert alert-success"><strong>Reserva confirmada!</strong> Codi: ' + payload.reservation.code.escapeHTML() + '</div>';

        if (payload.localMode) {
            html += '<div class="alert alert-info">Reserva en mode local: base de dades no disponible, la reserva no s\'ha guardat.</div>';
        } else if (payload.saveMessage) {
            html += '<div class="alert alert-info">' + payload.saveMessage.escapeHTML() + '</div>';
        }

        if (payload.dbWarning && !payload.localMode) {
            html += '<div class="alert alert-warning">' + payload.dbWarning.escapeHTML() + '</div>';
        }

        html += '<div class="ajax-result-summary">';
        html += '<div><strong>Nom:</strong> ' + payload.reservation.nom.escapeHTML() + '</div>';
        html += '<div><strong>Email:</strong> ' + payload.reservation.email.escapeHTML() + '</div>';
        html += '<div><strong>Telefon:</strong> ' + payload.reservation.telefon.escapeHTML() + '</div>';
        html += '<div><strong>Destinacio:</strong> ' + payload.reservation.ciutat.escapeHTML() + ' (' + payload.reservation.countryCode.escapeHTML() + ')</div>';
        html += '<div><strong>Entrada:</strong> ' + payload.reservation.dataEntrada.escapeHTML() + '</div>';
        html += '<div><strong>Sortida:</strong> ' + payload.reservation.dataSortida.escapeHTML() + '</div>';
        html += '<div><strong>Persones:</strong> ' + String(payload.reservation.persones).escapeHTML() + '</div>';
        html += '<div><strong>Habitacio:</strong> ' + payload.reservation.tipusHabitacio.escapeHTML() + '</div>';
        html += '<div><strong>Total:</strong> ' + String(payload.reservation.preuTotal).escapeHTML() + ' EUR</div>';
        html += '</div>';

        ajaxResult.update(html);
        ajaxResult.show();
        if (hasEffects()) {
            Effect.Appear(ajaxResult, { duration: 0.35, from: 0.8 });
        }
    }

    function validateNom() {
        var value = nomField.value.trim();
        return markError(nomField, 'errorNom', !regexNom.test(value));
    }

    function validateEmail() {
        var value = emailField.value.trim();
        return markError(emailField, 'errorEmail', !regexEmail.test(value));
    }

    function validateTelefon() {
        var value = telefonField.value.trim();
        return markError(telefonField, 'errorTelefon', !regexTelefon.test(value));
    }

    function validateCiutat() {
        return markError(ciutatField, 'errorCiutat', ciutatField.value === '');
    }

    function validatePersones() {
        return markError(personesField, 'errorPersones', personesField.value === '');
    }

    function validateTipus() {
        var selected = $$('input[name="tipusHabitacio"]:checked').length > 0;
        if (!selected) {
            $('errorTipusHabitacio').show();
        } else {
            $('errorTipusHabitacio').hide();
        }
        return selected;
    }

    function validateDates() {
        var valid = true;
        var dataEntradaTxt = dataEntradaField.value;
        var dataSortidaTxt = dataSortidaField.value;
        var avuiDate = new Date();
        avuiDate.setHours(0, 0, 0, 0);

        if (dataEntradaTxt) {
            var entradaDate = new Date(dataEntradaTxt);
            if (entradaDate < avuiDate) {
                dataEntradaField.addClassName('has-error');
                $('errorDataEntrada').update('La data no pot ser anterior a avui').show();
                valid = false;
            } else {
                dataEntradaField.removeClassName('has-error');
                $('errorDataEntrada').hide();
            }
        }

        if (!dataEntradaTxt || !dataSortidaTxt) {
            $('errorDataSortida').show();
            return false;
        }

        if (new Date(dataSortidaTxt) <= new Date(dataEntradaTxt)) {
            dataSortidaField.addClassName('has-error');
            $('errorDataSortida').show();
            valid = false;
        } else {
            dataSortidaField.removeClassName('has-error');
            $('errorDataSortida').hide();
        }

        return valid;
    }

    nomField.observe('blur', validateNom);
    emailField.observe('blur', validateEmail);
    telefonField.observe('blur', validateTelefon);
    ciutatField.observe('change', validateCiutat);
    personesField.observe('change', validatePersones);
    dataEntradaField.observe('change', validateDates);
    dataSortidaField.observe('change', validateDates);

    if (hasEffects()) {
        Effect.Appear($('formReserva'), { duration: 0.4, from: 0.85 });
    }

    if (ciutatSearchField && ciutatSuggestions) {
        ciutatSearchField.observe('input', function() {
            ciutatField.value = '';
            var query = ciutatSearchField.value.trim();
            window.clearTimeout(ciutatTimer);
            if (query.length < 1) {
                ciutatSuggestions.hide();
                return;
            }
            ciutatTimer = window.setTimeout(function() {
                requestAutocomplete('city', query);
            }, 220);
        });
    }

    if (hotelSearchField && hotelSuggestions) {
        hotelSearchField.observe('input', function() {
            var query = hotelSearchField.value.trim();
            window.clearTimeout(hotelTimer);
            if (query.length < 1) {
                hotelSuggestions.hide();
                return;
            }
            hotelTimer = window.setTimeout(function() {
                requestAutocomplete('hotel', query);
            }, 220);
        });
    }

    if (ciutatSearchField) {
        ciutatField.observe('change', function() {
            var selectedIndex = ciutatField.selectedIndex;
            if (selectedIndex > 0) {
                var text = ciutatField.options[selectedIndex].text;
                ciutatSearchField.value = text.replace(/\s\([A-Z]{2}\)$/, '');
            }
        });
    }

    document.observe('click', function(event) {
        var clicked = event.element();
        if (ciutatSuggestions && (!clicked || (clicked !== ciutatSearchField && !clicked.up('#ciutatSuggestions')))) {
            ciutatSuggestions.hide();
        }
        if (hotelSuggestions && (!clicked || (clicked !== hotelSearchField && !clicked.up('#hotelSuggestions')))) {
            hotelSuggestions.hide();
        }
    });

    $$('input[name="tipusHabitacio"]').each(function(node) {
        node.observe('change', function() {
            $('errorTipusHabitacio').hide();
        });
    });

    $('btnPreview').observe('click', function() {
        var valid = validateNom() && validateEmail() && validateTelefon() && validateCiutat() && validatePersones() && validateTipus() && validateDates();
        if (!valid) {
            hidePreview();
            if (hasEffects()) {
                Effect.Shake($('formReserva'), { distance: 8, duration: 0.4 });
            }
            alert('No es pot previsualitzar fins corregir els errors del formulari.');
            return;
        }

        var selectedCityText = ciutatField.options[ciutatField.selectedIndex].text;
        var selectedTipus = $$('input[name="tipusHabitacio"]:checked')[0].value;

        var previewHtml = '';
        previewHtml += '<div><strong>Nom:</strong> ' + nomField.value.trim() + '</div>';
        previewHtml += '<div><strong>Email:</strong> ' + emailField.value.trim() + '</div>';
        previewHtml += '<div><strong>Telefon:</strong> ' + telefonField.value.trim() + '</div>';
        previewHtml += '<div><strong>Ciutat:</strong> ' + selectedCityText + '</div>';
        previewHtml += '<div><strong>Entrada:</strong> ' + dataEntradaField.value + '</div>';
        previewHtml += '<div><strong>Sortida:</strong> ' + dataSortidaField.value + '</div>';
        previewHtml += '<div><strong>Persones:</strong> ' + personesField.value + '</div>';
        previewHtml += '<div><strong>Habitacio:</strong> ' + selectedTipus + '</div>';

        previewContent.update(previewHtml);
        revealPreview();
    });

    $('btnNetejar').observe('click', function(event) {
        if (!confirm('Vols netejar totes les dades del formulari?')) {
            event.stop();
            return;
        }

        window.setTimeout(function() {
            $$('.error').each(function(node) {
                node.hide();
            });
            $$('.has-error').each(function(node) {
                node.removeClassName('has-error');
            });
            previewContent.update('');
            hidePreview();
        }, 0);
    });

    $('formReserva').observe('submit', function(event) {
        event.stop();
        var valid = validateNom() && validateEmail() && validateTelefon() && validateCiutat() && validatePersones() && validateTipus() && validateDates();
        if (!valid) {
            if (hasEffects()) {
                Effect.Shake($('formReserva'), { distance: 10, duration: 0.45 });
            }
            alert('Si us plau, corregeix els errors del formulari');
            return;
        }

        var params = Form.serialize('formReserva', true);
        params.ajax = '1';
        params.action = 'reserve';
        params.hotelNom = hotelSearchField ? hotelSearchField.value.trim() : '';

        new Ajax.Request('server.php', {
            method: 'post',
            parameters: params,
            onSuccess: function(response) {
                var data = response.responseJSON || response.responseText.evalJSON(true);
                if (!data || !data.ok) {
                    ajaxResult.update('<div class="alert alert-danger">Error processant la resposta Ajax.</div>');
                    ajaxResult.show();
                    return;
                }
                renderAjaxResult(data);
            },
            onFailure: function() {
                ajaxResult.update('<div class="alert alert-danger">No s\'ha pogut contactar amb el servidor (Ajax).</div>');
                ajaxResult.show();
            }
        });
    });
});
