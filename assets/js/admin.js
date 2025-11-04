/**
 * JavaScript para el panel de administración
 * WooCommerce Product Scheduler
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Inicializar datepickers
        initDatePickers();

        // Manejar toggles de activación
        handleToggles();

        // Manejar botón de limpiar
        handleClearButton();

        // Validación en tiempo real
        handleValidation();
    });

    /**
     * Manejar toggles de activación/desactivación
     */
    function handleToggles() {
        // Toggle de despublicación
        $('#_scheduler_unpublish_enabled').on('change', function() {
            if ($(this).is(':checked')) {
                $('#unpublish-fields').slideDown(300);
            } else {
                $('#unpublish-fields').slideUp(300);
            }
        });

        // Toggle de republicación
        $('#_scheduler_republish_enabled').on('change', function() {
            if ($(this).is(':checked')) {
                $('#republish-fields').slideDown(300);
            } else {
                $('#republish-fields').slideUp(300);
            }
        });
    }

    /**
     * Inicializar datepickers de jQuery UI
     */
    function initDatePickers() {
        $('.scheduler-datepicker').datepicker({
            dateFormat: 'dd-mm-yy', // Formato DD-MM-YYYY para el usuario
            minDate: 0, // No permitir fechas pasadas
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            closeText: 'Cerrar',
            currentText: 'Hoy',
            monthNames: [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ],
            monthNamesShort: [
                'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
            ],
            dayNames: [
                'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'
            ],
            dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
            dayNamesMin: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'],
            firstDay: 1, // Lunes como primer día de la semana
            beforeShow: function(input, inst) {
                // Ajustar posición del datepicker
                setTimeout(function() {
                    inst.dpDiv.css({
                        top: $(input).offset().top + $(input).outerHeight(),
                        left: $(input).offset().left
                    });
                }, 0);
            }
        });
    }

    /**
     * Manejar botón de limpiar fechas
     */
    function handleClearButton() {
        $('#scheduler-clear-dates').on('click', function(e) {
            e.preventDefault();

            // Confirmar acción
            if (!confirm('¿Estás seguro de que quieres limpiar toda la programación de este producto?')) {
                return;
            }

            // Desactivar los toggles
            $('#_scheduler_unpublish_enabled').prop('checked', false).trigger('change');
            $('#_scheduler_republish_enabled').prop('checked', false).trigger('change');

            // Limpiar todos los campos
            $('#_scheduler_unpublish_date').val('');
            $('#_scheduler_unpublish_time').val('00:00');
            $('#_scheduler_republish_date').val('');
            $('#_scheduler_republish_time').val('00:00');

            // Quitar clases de validación
            $('.scheduler-datepicker, input[type="time"]').removeClass('scheduler-field-success scheduler-field-error');

            // Mostrar mensaje
            showMessage('Programación limpiada. Recuerda guardar los cambios.', 'info');
        });
    }

    /**
     * Validación en tiempo real
     */
    function handleValidation() {
        // Validar fechas al cambiar
        $('.scheduler-datepicker').on('change', function() {
            var $field = $(this);
            var dateValue = $field.val();

            if (dateValue === '') {
                $field.removeClass('scheduler-field-success scheduler-field-error');
                return;
            }

            // Validar formato DD-MM-YYYY
            if (!isValidDate(dateValue)) {
                $field.addClass('scheduler-field-error').removeClass('scheduler-field-success');
                showMessage('Formato de fecha incorrecto. Use DD-MM-YYYY', 'error');
                return;
            }

            // Convertir DD-MM-YYYY a objeto Date
            var selectedDate = convertDDMMYYYYtoDate(dateValue);
            var today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                $field.addClass('scheduler-field-error').removeClass('scheduler-field-success');
                showMessage('No se puede seleccionar una fecha pasada.', 'error');
                return;
            }

            $field.addClass('scheduler-field-success').removeClass('scheduler-field-error');
        });

        // NOTA: Ya no validamos que publicación deba ser posterior a despublicación
        // Se permite cualquier orden de fechas
    }

    /**
     * Validar formato de fecha DD-MM-YYYY
     */
    function isValidDate(dateString) {
        var regEx = /^\d{2}-\d{2}-\d{4}$/;
        if (!dateString.match(regEx)) {
            return false;
        }

        var parts = dateString.split('-');
        var day = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10);
        var year = parseInt(parts[2], 10);

        // Verificar que sea una fecha válida
        var d = new Date(year, month - 1, day);

        if (d.getFullYear() !== year || d.getMonth() !== (month - 1) || d.getDate() !== day) {
            return false;
        }

        return true;
    }

    /**
     * Convertir DD-MM-YYYY a objeto Date
     */
    function convertDDMMYYYYtoDate(dateString) {
        var parts = dateString.split('-');
        var day = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10) - 1; // Los meses en JavaScript son 0-11
        var year = parseInt(parts[2], 10);

        return new Date(year, month, day);
    }

    /**
     * Convertir DD-MM-YYYY a YYYY-MM-DD
     */
    function convertDDMMYYYYtoYYYYMMDD(dateString) {
        var parts = dateString.split('-');
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    }

    /**
     * Mostrar mensaje temporal
     */
    function showMessage(message, type) {
        // Tipos: success, error, warning, info
        var bgColor = '#f0f6fc';
        var borderColor = '#2271b1';

        switch(type) {
            case 'success':
                bgColor = '#edfaef';
                borderColor = '#00a32a';
                break;
            case 'error':
                bgColor = '#fcf0f1';
                borderColor = '#d63638';
                break;
            case 'warning':
                bgColor = '#fcf9e8';
                borderColor = '#dba617';
                break;
        }

        // Crear el mensaje
        var $message = $('<div class="scheduler-message">')
            .css({
                'position': 'fixed',
                'top': '32px',
                'right': '20px',
                'max-width': '400px',
                'padding': '15px 20px',
                'background': bgColor,
                'border-left': '4px solid ' + borderColor,
                'border-radius': '4px',
                'box-shadow': '0 2px 8px rgba(0,0,0,0.15)',
                'z-index': '999999',
                'animation': 'slideIn 0.3s ease-out'
            })
            .text(message);

        // Añadir al body
        $('body').append($message);

        // Quitar después de 4 segundos
        setTimeout(function() {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    }

    /**
     * Validar antes de enviar el formulario
     */
    $('#post').on('submit', function(e) {
        var hasErrors = false;

        // Verificar fechas de despublicación
        var unpublishDate = $('#_scheduler_unpublish_date').val();
        if (unpublishDate !== '' && !isValidDate(unpublishDate)) {
            showMessage('La fecha de despublicación tiene un formato incorrecto.', 'error');
            hasErrors = true;
        }

        // Verificar fechas de republicación
        var republishDate = $('#_scheduler_republish_date').val();
        if (republishDate !== '' && !isValidDate(republishDate)) {
            showMessage('La fecha de republicación tiene un formato incorrecto.', 'error');
            hasErrors = true;
        }

        // Si hay errores, prevenir envío
        if (hasErrors) {
            e.preventDefault();
            return false;
        }
    });

})(jQuery);
