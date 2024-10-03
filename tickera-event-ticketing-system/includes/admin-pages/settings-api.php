<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $tc;
$api_keys = new \Tickera\TC_API_Keys();
$page = sanitize_key( $_GET[ 'page' ] );
$tab = sanitize_key( $_GET[ 'tab' ] );

/**
 * Add New API Keys
 */
if ( isset( $_POST[ 'add_new_api_key' ] ) ) {

    if ( check_admin_referer( 'tickera_save_api_key' ) ) {

        if ( current_user_can( 'manage_options' ) || current_user_can( 'add_api_key_cap' ) ) {
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
if ( isset( $_GET[ 'action' ] ) && 'edit' == $_GET[ 'action' ] ) {
    $id = (int) $_GET[ 'ID' ];
    $api_key = new \Tickera\TC_API_Key( $id );
    $post_id = $id;
}


/**
 * Delete API Keys
 */
if ( isset( $_GET[ 'action' ] ) && 'delete' == $_GET[ 'action' ] ) {

    if ( ! isset( $_POST[ '_wpnonce' ] ) ) {

        $id = (int) $_GET[ 'ID' ];
        check_admin_referer( 'delete_' . $id );

        if ( current_user_can( 'manage_options' ) || current_user_can( 'delete_api_key_cap' ) ) {
            $api_key = new \Tickera\TC_API_Key( $id );
            $api_key->delete_api_key();
            $message = __( 'API Key has been successfully deleted.', 'tickera-event-ticketing-system' );

        } else {
            $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        }
    }
}

$page_num = ( isset( $_GET[ 'page_num' ] ) ) ? (int) $_GET[ 'page_num' ] : 1;
$api_keys_search = ( isset( $_GET[ 's' ] ) ) ? sanitize_text_field( $_GET[ 's' ] ) : '';

$wp_api_keys_search = new \Tickera\TC_API_Keys_Search( $api_keys_search, $page_num );
$fields = $api_keys->get_api_keys_fields();
$columns = $api_keys->get_columns();

$settings_api_url = add_query_arg(
    array(
        'post_type' => 'tc_events',
        'page' => $page,
        'tab' => $tab,
    ),
    admin_url( 'edit.php' )
);
?>
<div class="wrap tc_wrap tc-api-access-content">
    <div id="poststuff" class="metabox-holder tc-api-form<?php echo esc_attr( isset( $post_id ) ? ' tc-edit' : '' ); ?>">
        <div class="postbox">
            <h3><span><?php esc_html_e( 'API Access', 'tickera-event-ticketing-system' ); ?></span></h3>
            <div class="inside">
                <?php if ( isset( $message ) ) : ?>
                    <div id="message" class="updated fade"><p><?php echo esc_html( $message ); ?></p></div>
                <?php endif; ?>
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
                                        <?php do_action( 'tc_before_api_keys_field_type_check' ); ?>
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
                                        do_action( 'tc_after_api_keys_field_type_check' ); ?>
                                    </td>
                                </tr>
                            <?php endif;
                        endforeach; ?>
                        </tbody>
                    </table>
                    <div class="tc-api-form-actions">
                        <?php submit_button( ( isset( $_REQUEST[ 'action' ] ) && 'edit' == $_REQUEST[ 'action' ] ? __( 'Update', 'tickera-event-ticketing-system' ) : __( 'Add New', 'tickera-event-ticketing-system' ) ), 'primary', 'add_new_api_key', false ); ?>
                        <a <?php echo wp_kses_post( ( isset( $_GET[ 'action' ] ) && 'edit' == $_GET[ 'action' ] ) ) ? 'href="' . esc_url( $settings_api_url ) . '"' : 'href="#"' . ' id="cancel_add_edit"'; ?> class="tc-tickera-secondary"><?php esc_html_e( 'Cancel', 'tickera-event-ticketing-system' ); ?></a>
                    </div>
                    <div class="clear"></div>
                </form>
            </div> <!-- .inside -->
        </div> <!-- .postbox -->
    </div> <!-- #poststuff -->
    <!-- API KEYS TABLE -->
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
    <div id="poststuff" class="metabox-holder tc-api-keys">
        <div class="postbox">
            <div class="tablenav">
                <h3><span><?php esc_html_e( 'API Keys', 'tickera-event-ticketing-system' ); ?></span></h3>
                <div class="alignright actions new-actions">
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
            <table cellspacing="0" class="widefat shadow-table">
                <thead>
                <tr>
                    <?php $n = 1; ?>
                    <?php foreach ( $columns as $key => $col ) : ?>
                        <th style="" class="manage-column column-<?php echo esc_attr( $key ); ?>" width="<?php echo esc_attr( isset( $col_sizes[ $n ] ) ? esc_attr( $col_sizes[ $n ] . '%' ) : '' ); ?>" id="<?php echo esc_attr( $key ); ?>" scope="col"><?php echo esc_attr( $col ); ?></th>
                        <?php $n++; ?>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php $style = ''; ?>
                <?php foreach ( $wp_api_keys_search->get_results() as $api_key ) :
                    $api_key_obj = new \Tickera\TC_API_Key( $api_key->ID );
                    $api_key_object = apply_filters( 'tc_api_key_object_details', $api_key_obj->details );
                    $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
                    ?>
                    <tr id='user-<?php echo esc_attr( $api_key_object->ID ); ?>' data-id="<?php echo esc_attr( (int) $api_key_object->ID ); ?>" <?php echo wp_kses_post($style); ?>>
                        <?php $n = 1; ?>
                        <?php foreach ( $columns as $key => $col ) : ?>
                            <?php if ( $key == 'edit' ) : ?>
                                <td>
                                    <a class="api_keys_edit_link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=tc_events&page=' . $tc->name . '_settings&tab=api&action=' . $key . '&ID=' . $api_key_object->ID ) ); ?>"><?php esc_html_e( 'Edit', 'tickera-event-ticketing-system' ); ?></a>
                                </td>
                            <?php elseif ( 'delete' == $key ) : ?>
                                <td>
                                    <a class="api_keys_edit_link tc_delete_link" href="<?php echo esc_url( wp_nonce_url( 'edit.php?post_type=tc_events&page=' . $tc->name . '_settings&tab=api&action=' . $key . '&ID=' . $api_key_object->ID, 'delete_' . $api_key_object->ID ) ); ?>"><?php esc_html_e( 'Delete', 'tickera-event-ticketing-system' ); ?></a>
                                </td>
                            <?php else : ?>
                                <td>
                                    <?php
                                    $post_field_type = $api_keys->check_field_property( $key, 'post_field_type' );
                                    echo wp_kses_post( ( isset( $post_field_type ) && 'post_meta' == $post_field_type )
                                        ? apply_filters( 'tc_api_key_field_value', $api_key_object->{$key}, $post_field_type, $key )
                                        : apply_filters( 'tc_api_key_field_value', ( isset( $api_key_object->{$post_field_type} ) ? $api_key_object->{$post_field_type} : $api_key_object->{$key} ), $post_field_type, $key )
                                    );
                                    ?>
                                </td>
                            <?php endif;
                        endforeach; ?>
                    </tr>
                <?php endforeach;
                if ( count( $wp_api_keys_search->get_results() ) == 0 ) : ?>
                    <tr>
                        <td colspan="6">
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
