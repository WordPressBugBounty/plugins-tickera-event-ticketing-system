<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $tc;
$tc_general_settings = get_option( 'tickera_general_setting', false );
$cart_contents = apply_filters( 'tc_cart_contents', array() );
?>
<div class="tickera_additional_info">
    <?php include_once $tc->plugin_dir . 'includes/templates/shortcode-cart-additional-buyer-fields.php';  ?>
    <?php include_once $tc->plugin_dir . 'includes/templates/shortcode-cart-additional-owner-fields.php';  ?>
</div>
