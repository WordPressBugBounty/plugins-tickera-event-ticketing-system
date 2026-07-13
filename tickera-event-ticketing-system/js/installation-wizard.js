( function( $ ) {

    $( document ).ready( function() {

        $( '.tc-skip-button' ).click( function( e ) {
            event.preventDefault( e );
        } );

        $( '.tc_show_tax_rate' ).change( function() {

            var selected_value = $( '.tc_show_tax_rate:checked' ).val();

            if ( selected_value == 'yes' ) {
                $( '.tc-taxes-fields-wrap' ).show();

            } else {
                $( '.tc-taxes-fields-wrap' ).hide();
            }
        } );

        /**
         * Event: Click
         * Elements: Skip button
         * Actions: Save next step
         */
        $( '.tc-skip-button' ).on( 'click', function( e ) {
            e.preventDefault();

            var thisButton = $( this ),
                tc_step = $( '.tc_step' ).val(),
                input_data = {};

            switch ( tc_step ) {

                case 'start':
                    input_data.step = 'finish';
                    break;

                case 'setting':
                    input_data.step = 'checkin-apps';
                    break;
            }

            $.post( tc_ajax.ajaxUrl, {
                action: 'tc_installation_wizard_save_step_data',
                data: input_data,
                nonce: tc_ajax.ajaxNonce
            }, function() {
                window.location = thisButton.data( 'href' );
            } );
        } );

        /**
         * Event: Click
         * Elements: Continue and Finish button
         * Actions: Save step and settings
         */
        $( '.tc-continue-button, .tc-finish-button' ).on( 'click', function( e ) {
            e.preventDefault();
            $( '.tc-wiz-screen-content' ).fadeTo( "slow", 0.5 );
            $( '.tc-wiz-screen-footer' ).fadeTo( "slow", 0.5 );
            $( '.tc-continue-button, .tc-skip-button' ).attr( 'disabled', true );

            var thisButton = $( this ),
                tc_step = $( '.tc_step' ).val(),
                input_data = {};

            input_data.step = tc_step;

            if ( thisButton.hasClass( 'tc-finish-button' ) ) {
                input_data.step = 'finish';
            }

            switch ( tc_step ) {

                case 'start':
                    input_data.mode = $( 'input[name=mode]:checked' ).val();
                    break;

                case 'license-key':
                    input_data.license_key = $( '#tc-license-key' ).val();
                    break;

                case 'settings':
                    input_data.currencies = $( '.tc_select_currency' ).val();
                    input_data.currency_symbol = $( '.tc_currency_symbol' ).val();
                    input_data.currency_position = $( '.tc_currency_position' ).val();
                    input_data.price_format = $( '.tc_price_format' ).val();
                    input_data.show_tax_rate = $( '.tc_show_tax_rate:checked' ).val();
                    input_data.tax_rate = $( '.tc_tax_rate' ).val();
                    input_data.tax_inclusive = $( '.tc_tax_inclusive:checked' ).val();
                    input_data.tax_label = $( '.tc_tax_label' ).val();
                    break;
            }

            $.post( tc_ajax.ajaxUrl, {
                action: 'tc_installation_wizard_save_step_data',
                data: input_data,
                nonce: tc_ajax.ajaxNonce
            }, function() {

                if ( 'start' == tc_step ) {
                    $( '.tc-continue-button, .tc-skip-button' ).attr( 'disabled', false );
                    $( '#tc_wizard_start_form' ).submit();

                } else {
                    window.location = thisButton.data( 'href' );
                }
            } );
        } );

        $( ".tc-wiz-wrapper select" ).chosen( { disable_search_threshold: 5, allow_single_deselect: false } );
    } );

} )( jQuery );
