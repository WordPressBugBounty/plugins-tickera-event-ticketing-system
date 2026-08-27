<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file is used only on Tickera-specific admin-side custom settings or sections.
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $tc;

if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_api_settings_cap' ) ) { 
    // Allow access to this page
} else {
    wp_die(
        __( 'You do not have permission to access this page.', 'tickera-event-ticketing-system' ),
        'Access Denied',
        [ 'response' => 403 ]
    );
}

$api_keys = new \Tickera\TC_API_Keys();
$page = isset( $_GET[ 'page' ] ) ? sanitize_key( wp_unslash( $_GET[ 'page' ] ) ) : '';
$tab = isset( $_GET[ 'tab' ] ) ? sanitize_key( wp_unslash( $_GET[ 'tab' ] ) ) : '';
$can_delete_api_keys = current_user_can( 'manage_options' ) || current_user_can( 'delete_api_key_cap' );
$can_edit_api_keys = current_user_can( 'manage_options' ) || current_user_can( 'edit_api_key_cap' );
$can_add_api_keys = current_user_can( 'manage_options' ) || current_user_can( 'add_api_key_cap' );

$settings_api_url = add_query_arg(
    array(
        'post_type' => 'tc_events',
        'page' => $page,
        'tab' => $tab,
    ),
    admin_url( 'edit.php' )
);

/**
 * Add New API Keys
 */
if ( isset( $_POST[ 'add_new_api_key' ] ) ) {

    if ( check_admin_referer( 'tickera_save_api_key' ) ) {

        if ( $can_add_api_keys ) {
            $api_keys->add_new_api_key();
            $message = __( 'API Key data has been successfully saved.', 'tickera-event-ticketing-system' );

        } else {
            $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        }
    }
}

/**
 * Edit API Keys
 */
if ( isset( $_GET[ 'action' ] ) && 'edit' == sanitize_text_field( wp_unslash( $_GET[ 'action' ] ) ) && isset( $_GET[ 'ID' ] ) && $can_edit_api_keys ) {
    $id = (int) $_GET[ 'ID' ];
    $api_key = new \Tickera\TC_API_Key( $id );
    $post_id = $id;
}


/**
 * Delete API Keys
 */
if ( isset( $_GET[ 'action' ] ) && 'delete' == sanitize_text_field( wp_unslash( $_GET[ 'action' ] ) ) && isset( $_GET[ 'ID' ] ) && $can_delete_api_keys ) {

    if ( ! isset( $_POST[ '_wpnonce' ] ) ) {

        $id = (int) $_GET[ 'ID' ];
        check_admin_referer( 'delete_' . $id );

        if ( $can_delete_api_keys ) {
            $api_key = new \Tickera\TC_API_Key( $id );
            $api_key->delete_api_key();
            $message = __( 'API Key has been successfully deleted.', 'tickera-event-ticketing-system' );

        } else {
            $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        }
    }
}

/**
 * Bulk delete API Keys from the current table page.
 */
if ( isset( $_POST[ 'tc_bulk_api_keys_request' ] ) ) {

    check_admin_referer( 'tickera_bulk_delete_api_keys', 'tc_bulk_api_keys_nonce' );

    $bulk_page_num = isset( $_POST[ 'page_num' ] ) ? max( 1, absint( wp_unslash( $_POST[ 'page_num' ] ) ) ) : 1;
    $bulk_search = isset( $_POST[ 's' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 's' ] ) ) : '';
    $bulk_action = isset( $_POST[ 'bulk_action' ] ) ? sanitize_key( wp_unslash( $_POST[ 'bulk_action' ] ) ) : '';
    $api_key_ids = isset( $_POST[ 'api_key_ids' ] ) ? array_map( 'absint', (array) wp_unslash( $_POST[ 'api_key_ids' ] ) ) : array();
    $api_key_ids = array_values( array_unique( array_filter( $api_key_ids ) ) );

    $redirect_args = array(
        'post_type' => 'tc_events',
        'page' => $page,
        'tab' => $tab,
        'page_num' => $bulk_page_num,
    );

    if ( '' !== $bulk_search ) {
        $redirect_args[ 's' ] = $bulk_search;
    }

    if ( ! $can_delete_api_keys ) {
        $redirect_args[ 'tc_api_keys_bulk_error' ] = 'permission';

    } elseif ( 'delete' !== $bulk_action || empty( $api_key_ids ) ) {
        $redirect_args[ 'tc_api_keys_bulk_error' ] = 'no_selection';

    } else {
        $deleted_count = 0;
        $failed_count = 0;

        foreach ( $api_key_ids as $api_key_id ) {
            $api_key_post = get_post( $api_key_id );

            if ( ! $api_key_post || 'tc_api_keys' !== $api_key_post->post_type ) {
                $failed_count++;
                continue;
            }

            $api_key = new \Tickera\TC_API_Key( $api_key_id );

            if ( $api_key->delete_api_key( true ) ) {
                $deleted_count++;

            } else {
                $failed_count++;
            }
        }

        // If the final page became empty, return to the nearest remaining page.
        $remaining_api_keys = new \Tickera\TC_API_Keys_Search( $bulk_search, 1 );
        $last_page = max( 1, (int) ceil( $remaining_api_keys->get_count_of_all() / $remaining_api_keys->per_page ) );
        $redirect_args[ 'page_num' ] = min( $bulk_page_num, $last_page );
        $redirect_args[ 'tc_api_keys_bulk_deleted' ] = $deleted_count;

        if ( $failed_count ) {
            $redirect_args[ 'tc_api_keys_bulk_failed' ] = $failed_count;
        }
    }

    wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'edit.php' ) ) );
    exit;
}

if ( isset( $_GET[ 'tc_api_keys_bulk_error' ] ) ) {
    $bulk_error = sanitize_key( wp_unslash( $_GET[ 'tc_api_keys_bulk_error' ] ) );

    if ( 'permission' === $bulk_error ) {
        $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        $message_class = 'notice notice-error';

    } elseif ( 'no_selection' === $bulk_error ) {
        $message = __( 'Select at least one API key to delete.', 'tickera-event-ticketing-system' );
        $message_class = 'notice notice-warning';
    }

} elseif ( isset( $_GET[ 'tc_api_keys_bulk_deleted' ] ) ) {
    $deleted_count = absint( $_GET[ 'tc_api_keys_bulk_deleted' ] );
    $failed_count = isset( $_GET[ 'tc_api_keys_bulk_failed' ] ) ? absint( $_GET[ 'tc_api_keys_bulk_failed' ] ) : 0;

    if ( $deleted_count ) {
        $message = sprintf(
            /* translators: %d: Number of API keys deleted. */
            _n( '%d API key has been successfully deleted.', '%d API keys have been successfully deleted.', $deleted_count, 'tickera-event-ticketing-system' ),
            $deleted_count
        );
        $message_class = 'notice notice-success';

    } elseif ( $failed_count ) {
        $message = sprintf(
            /* translators: %d: Number of API keys that could not be deleted. */
            _n( '%d API key could not be deleted.', '%d API keys could not be deleted.', $failed_count, 'tickera-event-ticketing-system' ),
            $failed_count
        );
        $message_class = 'notice notice-error';

    } else {
        $message = __( 'No API keys were deleted.', 'tickera-event-ticketing-system' );
        $message_class = 'notice notice-warning';
    }

    if ( $deleted_count && $failed_count ) {
        $message .= ' ' . sprintf(
            /* translators: %d: Number of API keys that could not be deleted. */
            _n( '%d API key could not be deleted.', '%d API keys could not be deleted.', $failed_count, 'tickera-event-ticketing-system' ),
            $failed_count
        );
    }
}

$page_num = isset( $_GET[ 'page_num' ] ) ? max( 1, absint( wp_unslash( $_GET[ 'page_num' ] ) ) ) : 1;
$api_keys_search = ( isset( $_GET[ 's' ] ) ) ? sanitize_text_field( wp_unslash( $_GET[ 's' ] ) ) : '';

$wp_api_keys_search = new \Tickera\TC_API_Keys_Search( $api_keys_search, $page_num );
$api_key_results = $wp_api_keys_search->get_results();
$fields = $api_keys->get_api_keys_fields();
$columns = $api_keys->get_columns();
?>
<div class="wrap tc_wrap tc-api-access-content">
    <?php if ( isset( $message ) ) : ?>
        <div id="message" class="<?php echo esc_attr( isset( $message_class ) ? $message_class : 'updated fade' ); ?>"><p><?php echo esc_html( $message ); ?></p></div>
    <?php endif; ?>
    <?php if ( $can_add_api_keys ) : ?>
    <div id="poststuff" class="metabox-holder tc-api-form<?php echo esc_attr( isset( $post_id ) ? ' tc-edit' : '' ); ?>">
        <div class="postbox">
            <h3><span><?php esc_html_e( 'API Access', 'tickera-event-ticketing-system' ); ?></span></h3>
            <div class="inside">
                <form action="" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'tickera_save_api_key' ); ?>
                    <?php if ( isset( $post_id ) ) : ?>
                        <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>"/>
                    <?php endif; ?>
                    <table class="event-table tc-api-access-table form-table">
                        <tbody>
                        <?php foreach ( $fields as $field ) : ?>
                            <?php if ( $api_keys->is_valid_api_key_field_type( $field[ 'field_type' ] ) ) : ?>
                                <tr valign="top">
                                    <th scope="row">
                                        <label for="<?php echo esc_attr( $field[ 'field_name' ] ); ?>"><?php echo esc_html( $field[ 'field_title' ] ); ?>
                                            <?php
                                                if ( isset( $field[ 'field_description' ] ) && $field[ 'field_description' ] ) {
                                                    echo wp_kses_post( tickera_tooltip( $field[ 'field_description' ] ) );
                                                }
                                            ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php tickera_do_action( 'tickera_before_api_keys_field_type_check' ); ?>
                                        <?php if ( 'function' == $field[ 'field_type' ] ) : ?>
                                            <?php
                                            if ( 'event_name' == $field[ 'field_name' ] ) {

                                                if ( isset( $post_id ) ) {
                                                    call_user_func( $field[ 'function' ], $field[ 'field_name' ], $post_id, true );

                                                } else {
                                                    call_user_func( $field[ 'function' ], $field[ 'field_name' ], '', true );
                                                }

                                            } else {

                                                if ( isset( $post_id ) ) {
                                                    call_user_func( $field[ 'function' ], $field[ 'field_name' ], $post_id, true );

                                                } else {
                                                    call_user_func( $field[ 'function' ], $field[ 'field_name' ], '', true );
                                                }
                                            }
                                            ?>
                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                        <?php endif;
                                        if ( 'text' == $field[ 'field_type' ] ) : ?>
                                            <input type="text" class="regular-<?php echo esc_attr( $field[ 'field_type' ] ); ?>" value="<?php
                                            if ( isset( $api_key ) ) {

                                                if ( 'post_meta' == $field[ 'post_field_type' ] ) {
                                                    echo esc_attr( stripslashes( isset( $api_key->details->{$field[ 'field_name' ]} ) ? $api_key->details->{$field[ 'field_name' ]} : '' ) );

                                                } else {
                                                    echo esc_attr( stripslashes( $api_key->details->{$field[ 'post_field_type' ]} ) );
                                                }

                                            } else {
                                                echo esc_attr( stripslashes( isset( $field[ 'default_value' ] ) ? $field[ 'default_value' ] : '' ) );
                                            }
                                            ?>" id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" name="<?php echo esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>">
                                        <?php endif;
                                        if ( $field[ 'field_type' ] == 'textarea' ) : ?>
                                            <textarea class="regular-<?php echo esc_attr( $field[ 'field_type' ] ); ?>" id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" name="<?php echo esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"><?php
                                                if ( isset( $api_key ) ) {
                                                    if ( 'post_meta' == $field[ 'post_field_type' ] ) {
                                                        echo esc_textarea( isset( $api_key->details->{$field[ 'field_name' ]} ) ? $api_key->details->{$field[ 'field_name' ]} : '' );

                                                    } else {
                                                        echo esc_textarea( $api_key->details->{$field[ 'post_field_type' ]} );
                                                    }
                                                }
                                                ?>
                                            </textarea>
                                            <br/>
                                            <?php echo wp_kses_post( $field[ 'field_description' ] ); ?>
                                        <?php endif;
                                        tickera_do_action( 'tickera_after_api_keys_field_type_check' ); ?>
                                    </td>
                                </tr>
                            <?php endif;
                        endforeach; ?>
                        </tbody>
                    </table>
                    <div class="tc-api-form-actions">
                        <?php 
                            if ( $can_add_api_keys ) :
                                submit_button( ( isset( $_REQUEST[ 'action' ] ) && 'edit' == sanitize_text_field( wp_unslash( $_REQUEST[ 'action' ] ) ) ? __( 'Update', 'tickera-event-ticketing-system' ) : __( 'Add New', 'tickera-event-ticketing-system' ) ), 'primary', 'add_new_api_key', false ); 
                            endif;
                            if ( $can_edit_api_keys ) : ?>
                                <a <?php echo wp_kses_post( ( isset( $_GET[ 'action' ] ) && 'edit' == sanitize_text_field( wp_unslash( $_GET[ 'action' ] ) ) ) ) ? 'href="' . esc_url( $settings_api_url ) . '"' : 'href="#"' . ' id="cancel_add_edit"'; ?> class="tc-tickera-secondary"><?php esc_html_e( 'Cancel', 'tickera-event-ticketing-system' ); ?></a>
                            <?php endif;
                        ?>
                    </div>
                    <div class="clear"></div>
                </form>
            </div> <!-- .inside -->
        </div> <!-- .postbox -->
    </div> <!-- #poststuff -->
    <?php endif; ?>
    <!-- API KEYS TABLE -->
    <?php if ( $can_add_api_keys ) : ?>
    <div id="poststuff" class="metabox-holder tc-api-actions">
        <div class="postbox">
            <table class="event-table tc-api-access-table form-table">
                <tbody>
                <?php foreach ( $fields as $field ) {
                    if ( 'api_url' == $field[ 'field_name' ] ) : ?>
                        <tr valign="top">
                        <th scope="row">
                            <div class="actions">
                                <input type="button" id="add_new_api_key" class="button button-primary" value="<?php esc_attr_e( 'Add New', 'tickera-event-ticketing-system' ); ?>">
                            </div>
                        </th>
                        <td>
                            <label for="<?php echo esc_attr( $field[ 'field_name' ] ); ?>"><?php echo esc_html( $field[ 'field_title' ] ); ?>
                                <?php echo wp_kses_post( tickera_tooltip( $field[ 'field_description' ] ) ); ?>
                            </label>
                            <?php call_user_func( $field[ 'function' ], $field[ 'field_name' ], '', true ); ?>
                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                        </td>
                        </tr><?php
                        break;
                    endif;
                } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <div id="poststuff" class="metabox-holder tc-api-keys">
        <div class="postbox">
            <div class="tablenav">
                <h3><span><?php esc_html_e( 'API Keys', 'tickera-event-ticketing-system' ); ?></span></h3>
                <div class="alignright actions new-actions">
                    <?php if ( $can_delete_api_keys ) : ?>
                        <form id="tc-api-keys-bulk-form" method="post" action="<?php echo esc_url( $settings_api_url ); ?>">
                            <?php wp_nonce_field( 'tickera_bulk_delete_api_keys', 'tc_bulk_api_keys_nonce' ); ?>
                            <input type="hidden" name="tc_bulk_api_keys_request" value="1"/>
                            <input type="hidden" name="page_num" value="<?php echo esc_attr( $page_num ); ?>"/>
                            <input type="hidden" name="s" value="<?php echo esc_attr( $api_keys_search ); ?>"/>
                            <label for="tc-api-keys-bulk-action" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'tickera-event-ticketing-system' ); ?></label>
                            <select name="bulk_action" id="tc-api-keys-bulk-action" class="tc-regular-select tc-bulk-action-select">
                                <option value="-1"><?php esc_html_e( 'Bulk actions', 'tickera-event-ticketing-system' ); ?></option>
                                <option value="delete"><?php esc_html_e( 'Delete permanently', 'tickera-event-ticketing-system' ); ?></option>
                            </select>
                            <input type="submit" id="tc-api-keys-bulk-apply" class="button action" value="<?php esc_attr_e( 'Apply', 'tickera-event-ticketing-system' ); ?>"/>
                        </form>
                    <?php endif; ?>
                    <form method="get" action="?page=<?php echo esc_attr( $page ); ?>" class="search-form">
                        <input type='hidden' name='post_type' value='tc_events'/>
                        <input type='hidden' name='page' value='<?php echo esc_attr( $page ); ?>'/>
                        <input type='hidden' name='tab' value='<?php echo esc_attr( $tab ); ?>'/>
                        <label class="screen-reader-text"><?php esc_html_e( 'Search API Keys', 'tickera-event-ticketing-system' ); ?>:</label>
                        <input type="text" value="<?php echo esc_attr( $api_keys_search ); ?>" name="s">
                        <input type="submit" class="button" value="<?php esc_html_e( 'Search API Keys', 'tickera-event-ticketing-system' ); ?>">
                    </form>
                </div> <!--/alignright-->
            </div> <!--/tablenav-->
            <table cellspacing="0" class="wp-list-table widefat shadow-table">
                <thead>
                <tr>
                    <?php if ( $can_delete_api_keys ) : ?>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="tc-api-keys-select-all"><?php esc_html_e( 'Select all API keys on this page', 'tickera-event-ticketing-system' ); ?></label>
                            <input id="tc-api-keys-select-all" class="tc-api-keys-select-all" type="checkbox" form="tc-api-keys-bulk-form"/>
                        </td>
                    <?php endif; ?>
                    <?php $n = 1; ?>
                    <?php foreach ( $columns as $key => $col ) : 
                        if ( ( 'edit' == $key && ! $can_edit_api_keys ) || ( 'delete' == $key && ! $can_delete_api_keys ) ) :
                        else : ?>
                            <th style="" class="manage-column column-<?php echo esc_attr( $key ); ?>" width="<?php echo esc_attr( isset( $col_sizes[ $n ] ) ? esc_attr( $col_sizes[ $n ] . '%' ) : '' ); ?>" id="<?php echo esc_attr( $key ); ?>" scope="col"><?php echo esc_attr( $col ); ?></th>
                        <?php endif; ?>
                        <?php $n++; ?>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php $style = ''; ?>
                <?php foreach ( $api_key_results as $api_key ) :
                    $api_key_obj = new \Tickera\TC_API_Key( $api_key->ID );
                    $api_key_object = tickera_apply_filters( 'tickera_api_key_object_details', $api_key_obj->details );
                    $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
                    ?>
                    <tr id='user-<?php echo esc_attr( $api_key_object->ID ); ?>' data-id="<?php echo esc_attr( (int) $api_key_object->ID ); ?>" <?php echo wp_kses_post($style); ?>>
                        <?php if ( $can_delete_api_keys ) : ?>
                            <th scope="row" class="check-column">
                                <label class="screen-reader-text" for="api-key-<?php echo esc_attr( (int) $api_key_object->ID ); ?>"><?php echo esc_html( sprintf( /* translators: %d: API key ID. */ __( 'Select API key %d', 'tickera-event-ticketing-system' ), (int) $api_key_object->ID ) ); ?></label>
                                <input id="api-key-<?php echo esc_attr( (int) $api_key_object->ID ); ?>" class="tc-api-key-select" type="checkbox" name="api_key_ids[]" value="<?php echo esc_attr( (int) $api_key_object->ID ); ?>" form="tc-api-keys-bulk-form"/>
                            </th>
                        <?php endif; ?>
                        <?php $n = 1; ?>
                        <?php foreach ( $columns as $key => $col ) : ?>
                            <?php 
                            if ( $key == 'edit' ) : 
                                if ( $can_edit_api_keys ) : ?>
                                    <td>
                                        <a class="api_keys_edit_link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=tc_events&page=' . $tc->name . '_settings&tab=api&action=' . $key . '&ID=' . $api_key_object->ID ) ); ?>"><?php esc_html_e( 'Edit', 'tickera-event-ticketing-system' ); ?></a>
                                    </td><?php
                                else :
                                endif;           
                            elseif ( 'delete' == $key ) : 
                                if ( $can_delete_api_keys ) : ?>
                                    <td>
                                        <a class="api_keys_edit_link tc_delete_link" href="<?php echo esc_url( wp_nonce_url( 'edit.php?post_type=tc_events&page=' . $tc->name . '_settings&tab=api&action=' . $key . '&ID=' . $api_key_object->ID, 'delete_' . $api_key_object->ID ) ); ?>"><?php esc_html_e( 'Delete', 'tickera-event-ticketing-system' ); ?></a>
                                    </td><?php
                                else :
                                endif;
                            else : ?>
                                <td>
                                    <?php
                                    $post_field_type = $api_keys->check_field_property( $key, 'post_field_type' );
                                    echo wp_kses_post( ( isset( $post_field_type ) && 'post_meta' == $post_field_type )
                                        ? tickera_apply_filters( 'tickera_api_key_field_value', $api_key_object->{$key}, $post_field_type, $key )
                                        : tickera_apply_filters( 'tickera_api_key_field_value', ( isset( $api_key_object->{$post_field_type} ) ? $api_key_object->{$post_field_type} : $api_key_object->{$key} ), $post_field_type, $key )
                                    );
                                    ?>
                                </td>
                            <?php endif;
                        endforeach; ?>
                    </tr>
                <?php endforeach;
                if ( count( $api_key_results ) == 0 ) : ?>
                    <tr>
                        <td colspan="<?php echo esc_attr( count( $columns ) + ( $can_delete_api_keys ? 1 : 0 ) ); ?>">
                            <div class="zero-records"><?php esc_html_e( 'No API Keys found.', 'tickera-event-ticketing-system' ) ?></div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table> <!--/widefat shadow-table-->
        </div> <!-- .postbox -->
        <div class="tablenav">
            <div class="tablenav-pages"><?php esc_html( $wp_api_keys_search->page_links() ); ?></div>
        </div> <!--/tablenav-->
    </div>
</div>
