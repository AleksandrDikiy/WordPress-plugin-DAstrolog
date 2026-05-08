/**
 * Скрипти для Адміністративної панелі DAstrolog.
 * Відповідає за обробку подій натискання кнопок імпорту бази даних.
 */
( function( $ ) {
    'use strict';

    // Кешування DOM елементів
    var cacheDom = function() {
        return {
            importBtns: $( '.da-import-btn' ),
            aspectInputs: $( '.da-aspect-edit' ) // Нові інпути для аспектів
        };
    };

    var bindEvents = function() {
        var dom = cacheDom();

        dom.importBtns.on( 'click', function( e ) {
            e.preventDefault();
            
            var $btn = $( this );
            var importType = $btn.data( 'type' );
            var $row = $btn.closest( 'tr' ); // Якщо кнопки у таблиці
            var $spinner = $btn.siblings( '.spinner' );
            var $messageBox = $( '#da-admin-message' ); // Глобальний або локальний блок повідомлень

            // Якщо є кастомний лоадер
            $btn.prop( 'disabled', true ).text( 'Завантаження...' );
            if ( $spinner.length ) {
                $spinner.addClass( 'is-active' );
            }

            $.ajax({
                url: ajaxurl, // Вбудована змінна WP в адмінці
                type: 'POST',
                data: {
                    action: 'da_import_zet_data',
                    nonce: da_admin_vars.nonce, // Потрібно локалізувати в AdminController
                    import_type: importType
                },
                success: function( response ) {
                    if ( response.success ) {
                        alert( response.data.message ); // Або вивід у красивий div
                    } else {
                        alert( 'Помилка: ' + response.data.message );
                    }
                },
                error: function( jqXHR, textStatus, errorThrown ) {
                    alert( 'Критична помилка сервера: ' + textStatus );
                },
                complete: function() {
                    $btn.prop( 'disabled', false ).text( 'Запустити імпорт' );
                    if ( $spinner.length ) {
                        $spinner.removeClass( 'is-active' );
                    }
                }
            });
        });
        // Обробка зміни кольору або чекбоксу в таблиці Аспектів
        dom.aspectInputs.on( 'change', function() {
            var $input = $( this );
            var aspectId = $input.data( 'id' );
            var fieldName = $input.data( 'field' );
            var newValue;

            // Якщо це чекбокс - беремо 1 або 0, якщо колір - беремо значення (HEX)
            if ( $input.attr( 'type' ) === 'checkbox' ) {
                newValue = $input.is( ':checked' ) ? 1 : 0;
            } else {
                newValue = $input.val();
                // Оновлюємо текст HEX поруч з інпутом кольору
                $input.next('span').text(newValue);
            }

            // Додаємо візуальний ефект "завантаження" (напівпрозорість)
            $input.css('opacity', '0.5');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'da_update_aspect',
                    nonce: da_admin_vars.nonce,
                    aspect_id: aspectId,
                    field: fieldName,
                    value: newValue
                },
                success: function( response ) {
                    if ( !response.success ) {
                        alert( 'Помилка збереження: ' + response.data.message );
                    }
                },
                error: function() {
                    alert( 'Помилка з\'єднання з сервером.' );
                },
                complete: function() {
                    // Повертаємо нормальну прозорість
                    $input.css('opacity', '1');
                }
            });
        });
    };

    // Ініціалізація
    $( function() {
        bindEvents();
    });

} )( jQuery );