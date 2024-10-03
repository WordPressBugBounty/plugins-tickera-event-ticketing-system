<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

esc_html_e( 'receiving IPN message...', 'tickera-event-ticketing-system' );
http_response_code( 200 );
