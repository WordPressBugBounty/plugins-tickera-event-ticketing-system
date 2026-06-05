( () => {

    const editor = wp.data.select( 'core/editor' );
    let lockProcess = false;

    wp.data.subscribe( () => {

        let status = editor.getEditedPostAttribute( 'status' );

        if ( typeof status !== 'undefined' ) {

            if ( ! lockProcess && ( 'draft' == status || 'publish' == status ) ) {
                lockProcess = true;

                if ( tc_gutenberg.no_ticket_types ) {

                    wp.data.dispatch( 'core/notices' ).createNotice(
                        'success',
                        tc_gutenberg.no_ticket_types_message,
                        {
                            actions: [
                                {
                                    label: tc_gutenberg.no_ticket_types_action_message,
                                    url: tc_gutenberg.no_ticket_types_action_url
                                }
                            ]
                        }
                    );
                }
            }
        }
    } );
} )();
