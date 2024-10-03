<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_VC_Shortcodes' ) ) {

    class TC_VC_Shortcodes {

        function __construct() {
            add_action( 'vc_before_init', array( &$this, 'add_tickera_shortcodes' ) );
        }

        function add_tickera_shortcodes() {
            vc_map( array(
                'name' => __( 'Cart', 'tickera-event-ticketing-system' ),
                'description' => __( 'Display the cart contents', 'tickera-event-ticketing-system' ),
                'base' => 'tc_cart',
                'class' => 'tc_vc_icon',
                'category' => __( 'Tickera', 'tickera-event-ticketing-system' ),
                'show_settings_on_create' => false
            ) );
        }
    }

    $TC_VC_Shortcodes = new TC_VC_Shortcodes();
}
