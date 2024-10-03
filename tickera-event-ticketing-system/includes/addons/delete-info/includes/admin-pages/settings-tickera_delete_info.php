<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $tc;

if ( isset( $_POST[ 'tc_delete_selected_data_permanently' ] ) && current_user_can( 'manage_options' ) ) {

    if ( check_admin_referer( 'delete_info' ) && isset( $_POST[ 'tc_delete_plugin_data' ] ) ) {

        ini_set( 'max_input_time', 0 );
        ini_set( 'max_execution_time', 0 );
        set_time_limit( 0 );
        @ini_set( 'memory_limit', '1024M' );

        do_action( 'tc_delete_plugins_data', tickera_sanitize_array( $_POST[ 'tc_delete_plugin_data' ] ) );
        $message = __( 'All selected data has been permanently deleted successfully.', 'tickera-event-ticketing-system' );
    }
}

$tickera_plugins_and_addons = apply_filters( 'tc_delete_info_plugins_list', array( 'tickera' => $tc->title ) );

$action_url = add_query_arg( array(
    'post_type' => 'tc_events',
    'page' => 'tc_settings',
    'tab' => 'tickera_delete_info'
), admin_url( 'edit.php' ) );
?>
<div class="wrap tc_wrap" id="tc_delete_info">
    <?php if ( isset( $message ) ) { ?>
        <div id="message" class="updated fade"><p><?php echo esc_html( $message ); ?></p></div>
    <?php } ?>
    <div id="poststuff" class="metabox-holder tc-settings">
        <form id="tc-delete-info" method='post' action='<?php echo esc_url( $action_url ); ?>'>
            <?php wp_nonce_field( 'delete_info' ); ?>
            <div class="postbox">
                <h3><span><?php esc_html_e( 'Delete Information stored by the plugin and its add-ons', 'tickera-event-ticketing-system' ); ?></span></h3>
                <div class="inside">
                    <span class="description"></span>
                    <table class="form-table" cellspacing="0" id="status">
                        <tbody>
                        <tr>
                            <th><?php esc_html_e( 'Plugin', 'tickera-event-ticketing-system' ); ?></th>
                            <th><?php esc_html_e( 'Confirm', 'tickera-event-ticketing-system' ); ?></th>
                        </tr>
                        <?php foreach ( $tickera_plugins_and_addons as $plugin_name => $plugin_title ) { ?>
                            <tr>
                                <td><?php echo esc_html( $plugin_title ); ?></td>
                                <td><input type="checkbox" value="yes" name="tc_delete_plugin_data[<?php echo esc_attr( $plugin_name ); ?>]"/><?php esc_html_e( 'Delete', 'tickera-event-ticketing-system' ); ?>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php submit_button( __( 'Delete selected data permanently', 'tickera-event-ticketing-system' ), 'primary', 'tc_delete_selected_data_permanently', true ); ?>
            </div>
        </form>
        <?php do_action( 'tc_after_delete_info' ); ?>
    </div>
    <div id="poststuff" class="metabox-holder tc-settings">
        <div class="postbox">
            <h3>
                <span><?php esc_html_e('Bulk Delete Tickets', 'tickera-event-ticketing-system'); ?></span>
                <span class="description"><?php esc_html_e( 'Action is non-reversible, please make sure to backup the database first.', 'tickera-event-ticketing-system' ); ?></span>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tbody>
                    <tr id="tc_dl_event_filter">
                        <th scope="row"><label><?php esc_html_e('Events', 'tickera-event-ticketing-system') ?></label></th>
                        <td>
                            <div class="tc-dl-inner-container">
                                <?php $wp_events_search = new \Tickera\TC_Events_Search( '', '', -1 ); ?>
                                <select name="event_ids" class="regular-text" data-placeholder="<?php esc_html_e( 'Select some events to delete all associated tickets' ,'tickera-event-ticketing-system' ); ?>" multiple="true">
                                    <?php foreach ( $wp_events_search->get_results() as $event ) :
                                        $event = new \Tickera\TC_Event( $event->ID );
                                        $event_date = $event->get_event_date();
                                        ?>
                                        <option value="<?php echo esc_attr( $event->details->ID ); ?>"><?php echo esc_html( $event->details->post_title . ' (' . $event_date . ')' ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <tr id="tc_dl_delete_order_filter">
                        <th scope="row"><label><?php esc_html_e('Delete Associated Orders', 'tickera-event-ticketing-system') ?></label></th>
                        <td>
                            <div class="tc-dl-inner-container">
                                <label>
                                    <input type="radio" class="" name="delete_orders" value="yes"/>Yes
                                </label>
                                <label>
                                    <input type="radio" class="" name="delete_orders" value="no" checked="checked"/>No
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <span class="tc_dl_notice"></span>
                            <div class="tccrr-loader hidden"><div></div><div></div><div></div><div></div></div><!-- Spinner -->
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <input type="button" id="tc_dl_delete_btn" class="button button-primary" value="<?php esc_html_e( 'Delete tickets permanently', 'tickera-event-ticketing-system' ); ?>"/>
                        </th>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div><?php
