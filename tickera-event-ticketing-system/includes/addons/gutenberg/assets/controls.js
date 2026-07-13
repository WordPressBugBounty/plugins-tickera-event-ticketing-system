var tc_gutenberg_block_control = {

    init: function( elem, props ) {

        switch ( elem.field_type ) {

            case 'select':

                let options = [];

                elem.options.map( function( val, key ) {
                    options.push( { value: val[0], label: val[1] } );
                });

                return el(
                    SelectControl,
                    {
                        label: elem.field_title,
                        className: 'tc-gb-component',
                        value: props.attributes[ elem.field_name ],
                        onChange: function change_val( value ) {
                            return props.setAttributes( { [elem.field_name]: value } );
                        },
                        options: options
                    }
                );
                break;
        }
    }
};
