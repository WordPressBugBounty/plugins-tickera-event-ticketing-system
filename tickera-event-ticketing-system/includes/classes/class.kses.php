<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Kses' ) ) {

    class TC_Kses {

        function __construct( $direct_access = false ) {

            if ( ! $direct_access ) {
                add_filter( 'wp_kses_allowed_html', array( $this, 'register' ), 20, 2 );
            }
        }

        function register( $allowed_tags, $context ) {

            $context = str_replace( 'tickera_', '', $context );

            $default_attributes = [
                'id'                    => [],
                'class'                 => [],
                'disabled'              => [],
                'style'                 => [],
                'data-column'           => [],
                'data-tc-check-value'   => []
            ];

            $default_form_attributes = [
                'name'              => [],
                'selected'          => [],
                'autocomplete'      => [],
                'tabindex'          => [],
                'type'              => [],
                'href'              => [],
                'rel'               => [],
                'readonly'          => [],
                'checked'           => [],
                'value'             => [],
                'placeholder'       => [],
                'number'            => [],
                'min'               => [],
                'max'               => [],
                'step'              => [],
                'target'            => [],
                'formnovalidate'    => [],
                'action'            => [],
                'method'            => []
            ];

            $default_conditional_attributes = [
                'data-condition-value'      => [],
                'data-condition-action'     => [],
                'data-condition-field_name' => [],
                'data-condition-field_type' => []
            ];

            $default_table_attributes = [
                'valign'        => [],
                'scope'         => [],
                'border'        => [],
                'cellspacing'   => [],
                'cellpadding'   => [],
                'width'         => [],
                'height'        => []
            ];

            $default_iframe_attributes = [
                'frameborder'       => [],
                'allowtransparency' => [],
                'title'             => [],
                'style'             => []
            ];

            $default_tags = [
                'b'             => [],
                'cite'          => [],
                'strong'        => [],
                'em'            => [],
                'tbody'         => [],
                'br'            => [],
                'i'             => $default_attributes,
                'div'           => $default_attributes,
                'label'         => array_merge( $default_attributes, [ 'for' => [] ] ),
                'a'             => array_merge( $default_attributes, $default_form_attributes, [ 'onclick' => [] ] ),
                'ul'            => $default_attributes,
                'li'            => $default_attributes,
                'ol'            => $default_attributes,
                'span'          => $default_attributes,
                'select'        => array_merge( $default_attributes, $default_form_attributes ),
                'input'         => array_merge( $default_attributes, $default_form_attributes, $default_conditional_attributes ),
                'textarea'      => array_merge( $default_attributes, $default_form_attributes ),
                'option'        => [ 'value' => [], 'selected' => [] ],
                'table'         => array_merge( $default_attributes, $default_table_attributes ),
                'tr'            => array_merge( $default_attributes, $default_table_attributes ),
                'th'            => $default_table_attributes,
                'td'            => $default_attributes,
                'button'        => $default_attributes,
                'p'             => $default_attributes,
                'fieldset'      => [],
                'legend'        => $default_attributes,
                'form'          => array_merge( $default_attributes, $default_form_attributes ),
                'img'           => array_merge( $default_attributes, [ 'decoding' => [], 'alt' => [], 'title' => [], 'src' => [] ] )
            ];

            switch( $context ) {

                case 'add_to_cart':
                    $allowed_tags = [
                        'form' => [ 'class' => [] ],
                        'span' => [ 'class' => [] ],
                        'a' => [ 'id' => [], 'class' => [], 'href' => [], 'data-button-type' => [], 'data-open-method' => [] ],
                        'input' => [ 'type' => [], 'name' => [], 'class' => [], 'value' => [] ],
                        'select' => [ 'class' => [] ],
                        'option' => [ 'value' => [], 'selected' => [] ]
                    ];
                    break;

                case 'quantity_selector':
                    $allowed_tags = [
                        'td'    => [ 'data-column' => [] ],
                        'div'   => [ 'class' => [] ],
                        'label' => [ 'class' => [], 'for' => [] ],
                        'input' => [
                            'type'          => [],
                            'id'            => [],
                            'class'         => [],
                            'name'          => [],
                            'value'         => [],
                            'aria-label'    => [],
                            'size'          => [],
                            'min'           => [],
                            'max'           => [],
                            'step'          => [],
                            'placeholder'   => [],
                            'inputmode'     => [],
                            'autocomplete'  => []
                        ],
                        'select' => [ 'class' => [] ],
                        'option' => [ 'value' => [], 'selected' => [] ]
                    ];
                    break;

                case 'setting':

                    $default_tags[ 'tr' ] = array_merge(
                        $default_attributes,
                        $default_table_attributes,
                        $default_conditional_attributes
                    );

                    $default_tags[ 'div' ] = array_merge( $default_attributes, [
                        'hidefocus' => [],
                        'tabindex' => [],
                        'role' => []
                    ] );

                    $default_tags[ 'iframe' ] = array_merge(
                        $default_attributes,
                        $default_iframe_attributes
                    );

                    $default_tags[ 'button' ] = array_merge( $default_tags[ 'button' ], [
                        'type' => [],
                        'data-wp-editor-id' => [],
                        'data-editor' => []
                    ] );

                    $default_tags[ 'label' ] = array_merge( $default_tags[ 'label' ], $default_conditional_attributes );
                    $default_tags[ 'select' ] = array_merge( $default_tags[ 'select' ], $default_conditional_attributes );
                    $default_tags[ 'input' ] = array_merge( $default_tags[ 'input' ], $default_conditional_attributes );

                    $allowed_tags = $default_tags;
                    break;

                case 'payment_form':
                    $default_tags[ 'input' ] = array_merge( $default_tags[ 'input' ], [ 'data-encrypted-name' => [] ] );
                    $default_tags[ 'select' ] = array_merge( $default_tags[ 'select' ], [ 'data-encrypted-name' => [] ] );
                    $allowed_tags = array_merge( $default_tags, [ 'script' => [] ] );
                    break;

                case 'toggle':
                    $default_tags[ 'div' ] = array_merge( $default_tags[ 'div' ], [ 'event_id' => [], 'ticket_id' => [] ] );
                    $allowed_tags = $default_tags;
                    break;

                case 'tickera':
                    $allowed_tags = $default_tags;
                    break;
            }

            return $allowed_tags;
        }

        function callback( $value ) {
            return wp_kses( $value, self::register( [], 'tickera' ) );
        }
    }

    new TC_Kses;
}