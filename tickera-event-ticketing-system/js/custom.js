( function( $ ) {

    // Admin dashboard
    $( '#wpbody-content' ).ready( function() {
        $( '.tc-admin-notice' ).show();
    } );

    // Payment Gateway Page - Frontend
    $( document ).on( 'keypress', '.tc-numbers-only', function( e ) {
        if ( e.which != 8 && e.which != 0 && ( e.which < 48 || e.which > 57 ) ) {
            return false;
        }
    } );

} )( jQuery );
