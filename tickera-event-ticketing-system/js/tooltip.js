( function ( $ ) {

    $.fn.tc_tooltip = function( options ) {

        var render = ( element ) => {

            let tooltip = ( options && typeof options.tooltip !== 'undefined' ) ? options.tooltip : element.data( 'tooltip' ),
                content = '<p class="tc-tooltip-content">' + tooltip + '</p>';

            let position = element.offset(),
                top = position.top,
                toOffset = 0,
                left = position.left,
                width = element.outerWidth();

            $( 'body' ).append( content );
            let contentContainer = $( '.tc-tooltip-content' ),
                contentWidth = contentContainer.outerWidth(),
                contentHeight = contentContainer.outerHeight();

            // Center
            // contentContainer.css( {
            //     'position': 'absolute',
            //     'top': ( top - parseInt( contentHeight ) - toOffset ) + 'px',
            //     'left': ( left - ( parseInt( contentWidth ) / 2 ) + ( parseInt( width ) / 2 ) ) + 'px'
            // });

            // Top Right
            contentContainer.css( {
                'position': 'absolute',
                'top': ( top - parseInt( contentHeight ) - toOffset ) + 'px',
                'left': ( left + parseInt( width ) ) + 'px'
            });
        };

        var clicked = false;
        $( this ).on( 'mousedown', function() {
            clicked = true;
        })

        $( this ).on( 'focus', function() {
            if ( ! clicked ) {
                render( $( this ) );
            }
        } )

        $( this ).on( 'blur tcTooltipDetached', function() {
            $( '.tc-tooltip-content' ).remove();
        } )

        if ( options && typeof options.detach !== 'undefined' && options.detach ) {
            $( this ).trigger( 'tcTooltipDetached' );
        }

        if ( options && typeof options.focus !== 'undefined' && options.focus ) {
            $( this ).trigger( 'focus' );
        }
    };

    $( document ).ready( function() {
        if ( tc_tooltip.frontend_tooltip ) {
            $( '.tc-tooltip' ).each( function() {
                $( this ).tc_tooltip();
            } );
        }
    } );

} )( jQuery );