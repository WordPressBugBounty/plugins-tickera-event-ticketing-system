<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $wpdb;

$discounts = new \Tickera\TC_Discounts();
$page = sanitize_key( $_GET[ 'page' ] );

/**
 * Add new discount code
 */
if ( isset( $_POST[ 'add_new_discount' ] ) ) {

    if ( check_admin_referer( 'tickera_save_discount' ) ) {

        if ( current_user_can( 'manage_options' ) || current_user_can( 'add_discount_cap' ) ) {
            $discounts->add_new_discount();
            $message = __( 'Discount Code data has been saved successfully.', 'tickera-event-ticketing-system' );

        } else {
            $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        }
    }
}

/**
 * Edit existing discount code
 */
if ( isset( $_GET[ 'action' ] ) && 'edit' == $_GET[ 'action' ] ) {
    $id = (int) $_GET[ 'ID' ];
    $discount = new \Tickera\TC_Discount( $id );
    $post_id = $id;
}

/**
 * Delete discount code
 */
if ( isset( $_GET[ 'action' ] ) && 'delete' == $_GET[ 'action' ] ) {

    if ( ! isset( $_POST[ '_wpnonce' ] ) ) {

        $id = (int) $_GET[ 'ID' ];

        if ( check_admin_referer( 'delete_' . $id ) ) {

            if ( current_user_can( 'manage_options' ) || current_user_can( 'delete_discount_cap' ) ) {
                $discount = new \Tickera\TC_Discount( $id );
                $discount->delete_discount();
                $message = __( 'Discount Code has been successfully deleted.', 'tickera-event-ticketing-system' );

            } else {
                $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
            }
        }
    }
}

$page_num = ( isset( $_GET[ 'page_num' ] ) ) ? (int) $_GET[ 'page_num' ] : 1;
$discountssearch = ( isset( $_GET[ 's' ] ) ) ? sanitize_text_field( $_GET[ 's' ] ) : '';

$wp_discounts_search = new \Tickera\TC_Discounts_Search( $discountssearch, $page_num );
$fields = $discounts->get_discount_fields();
$columns = $discounts->get_columns();

$settings_discount_url = add_query_arg(
    array(
        'post_type' => 'tc_events',
        'page' => $page,
    ),
    admin_url( 'edit.php' )
);
?>
<div class="wrap tc_wrap tc-discount-codes-content">
    <div id="poststuff" class="metabox-holder tc-discount-form<?php echo esc_attr( isset( $post_id ) ? ' tc-edit' : '' ); ?>">
        <div class="postbox">
            <h3><span><?php echo esc_html( $discounts->form_title ); ?></span></h3>
            <div class="inside">
                <?php
                if ( isset( $message ) ) { ?>
                    <div id="message" class="updated fade"><p><?php echo esc_html( $message ); ?></p></div>
                <?php } ?>
                <form action="" method="post" enctype="multipart/form-data" id="tc_discount_code_form">
                    <?php wp_nonce_field( 'tickera_save_discount' ); ?>
                    <?php if ( isset( $post_id ) ) { ?>
                        <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>"/>
                    <?php } ?>
                    <table class="discount-table form-table">
                        <tbody>
                        <?php foreach ( $fields as $field ) { ?>
                            <?php if ( $discounts->is_valid_discount_field_type( $field[ 'field_type' ] ) && ( ! isset( $field[ 'form_visibility' ] ) || $field[ 'form_visibility' ] ) ) { ?>
                                <tr valign="top" class="<?php echo esc_attr( $field[ 'field_name' ] ); ?>">
                                    <th scope="row"><label for="<?php echo esc_attr( $field[ 'field_name' ] ); ?>"><?php echo esc_html( $field[ 'field_title' ] ); ?></label></th>
                                    <td>
                                        <?php do_action( 'tc_before_discounts_field_type_check' ); ?>
                                        <?php
                                        if ( $field[ 'field_type' ] == 'function' ) {

                                            if ( 'tickera_extended_radio_button' == $field[ 'function' ] ) {
                                                $radio_values = $field[ 'values' ];
                                                $checked = ( isset( $post_id ) && $radio_value = get_post_meta( $post_id, $field[ 'field_name' ], true ) ) ? $radio_value : '';
                                                call_user_func( $field[ 'function' ], $field[ 'field_name' ] . '_post_meta', implode( ',', $radio_values ), $checked );

                                            } else {

                                                if ( isset( $post_id ) ) {
                                                    call_user_func( $field[ 'function' ], $field[ 'field_name' ], $post_id );

                                                } else {
                                                    call_user_func( $field[ 'function' ], $field[ 'field_name' ] );
                                                }
                                            } ?>
                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                        <?php } ?>
                                        <?php if ( $field[ 'field_type' ] == 'text' ) { ?>
                                            <input type="text" <?php
                                            if ( isset( $field[ 'placeholder' ] ) ) {
                                                echo wp_kses_post( 'placeholder="' . esc_attr( $field[ 'placeholder' ] ) . '"' );
                                            }
                                            ?> class="regular-<?php echo esc_attr( $field[ 'field_type' ] ); ?>" value="<?php
                                            if ( isset( $discount ) ) {
                                                if ( $field[ 'post_field_type' ] == 'post_meta' ) {
                                                    echo esc_attr( isset( $discount->details->{$field[ 'field_name' ]} ) ? $discount->details->{$field[ 'field_name' ]} : '' );

                                                } else {
                                                    echo esc_attr( $discount->details->{$field[ 'post_field_type' ]} );
                                                }
                                            }
                                            ?>" id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" name="<?php echo esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>" <?php echo esc_attr( isset( $field[ 'required' ] ) ? 'required' : '' ); ?> <?php echo esc_attr( isset( $field[ 'number' ] ) ? 'number="true"' : '' ); ?>>
                                            <span class="description"><?php echo esc_html($field[ 'field_description' ]); ?></span>
                                        <?php } ?>
                                        <?php if ( $field[ 'field_type' ] == 'textarea' ) { ?>
                                            <textarea class="regular-<?php echo esc_html($field[ 'field_type' ]); ?>" id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" name="<?php echo esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"><?php
                                                if ( isset( $discount ) ) {
                                                    if ( $field[ 'post_field_type' ] == 'post_meta' ) {
                                                        echo esc_textarea( isset( $discount->details->{$field[ 'field_name' ]} ) ? $discount->details->{$field[ 'field_name' ]} : '' );

                                                    } else {
                                                        echo esc_textarea( $discount->details->{$field[ 'post_field_type' ]} );
                                                    }
                                                }
                                                ?>
                                            </textarea>
                                            <br/><?php echo esc_html( $field[ 'field_description' ] ); ?>
                                        <?php } ?>
                                        <?php
                                        if ( $field[ 'field_type' ] == 'image' ) { ?>
                                            <div class="file_url_holder">
                                                <label>
                                                    <input class="file_url" type="text" size="36" name="<?php echo esc_attr( $field[ 'field_name' ] . '_file_url_' . $field[ 'post_field_type' ] ); ?>" value="<?php
                                                           if ( isset( $discount ) ) {
                                                               echo esc_attr( isset( $discount->details->{$field[ 'field_name' ] . '_file_url'} ) ? $discount->details->{$field[ 'field_name' ] . '_file_url'} : '' );
                                                           }
                                                           ?>"
                                                    />
                                                    <input class="file_url_button button-secondary" type="button" value="<?php esc_html_e( 'Browse', 'tickera-event-ticketing-system' ); ?>"/><?php echo esc_html( $field[ 'field_description' ] ); ?>
                                                </label>
                                            </div>
                                        <?php } ?>
                                        <?php do_action( 'tc_after_discounts_field_type_check' ); ?>
                                    </td>
                                </tr><?php
                            }
                        } ?>
                        </tbody>
                    </table>
                    <div class="tc-discount-form-actions">
                        <?php submit_button( ( isset( $_REQUEST[ 'action' ] ) && 'edit' == $_REQUEST[ 'action' ] ? __( 'Update', 'tickera-event-ticketing-system' ) : __( 'Add New', 'tickera-event-ticketing-system' ) ), 'primary', 'add_new_discount', false ); ?>
                        <a <?php echo wp_kses_post( ( isset( $_GET[ 'action' ] ) && 'edit' == $_GET[ 'action' ] ) ) ? 'href="' . esc_url( $settings_discount_url ) . '"' : 'href="#"' . ' id="cancel_add_edit"'; ?> class="tc-tickera-secondary"><?php esc_html_e( 'Cancel', 'tickera-event-ticketing-system' ); ?></a>
                    </div>
                    <div class="clear"></div>
                </form>
            </div>
        </div>
    </div>
    <div id="poststuff" class="metabox-holder tc-discount-actions">
        <div class="postbox">
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <div class="actions">
                                <input type="button" id="add_new_discount_code" class="button button-primary" value="<?php esc_attr_e( 'Add New', 'tickera-event-ticketing-system' ); ?>">
                            </div>
                        </th>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div id="poststuff" class="metabox-holder tc-discount-codes">
        <div class="postbox">
            <h3><span><?php esc_html_e( 'Discount Codes', 'tickera-event-ticketing-system' ); ?></span>
                <div class="alignright actions new-actions">
                    <form method="get" action="edit.php" class="search-form">
                        <p class="search-box">
                            <input type="hidden" name="post_type" value="tc_events"/>
                            <input type='hidden' name='page' value='<?php echo esc_attr( $page ); ?>'/>
                            <label class="screen-reader-text"><?php esc_html_e( 'Search Discounts', 'tickera-event-ticketing-system' ); ?>:</label>
                            <input type="text" value="<?php echo esc_attr( $discountssearch ); ?>" name="s">
                            <input type="submit" class="button" value="<?php esc_html_e( 'Search Discounts', 'tickera-event-ticketing-system' ); ?>">
                        </p>
                    </form>
                </div><!--/alignright-->
            </h3>
        </div><!--/tablenav-->
        <table cellspacing="0" class="widefat shadow-table">
            <thead>
            <tr>
                <?php
                $n = 1;
                foreach ( $columns as $key => $col ) {
                    ?>
                    <th style="" class="manage-column column-<?php echo esc_attr( $key ); ?>" width="<?php echo esc_attr( isset( $col_sizes[ $n ] ) ? esc_attr( $col_sizes[ $n ]. '%' ) : '' ); ?>"
                        id="<?php echo esc_attr( $key ); ?>" scope="col"><?php echo esc_html($col); ?></th>
                    <?php
                    $n++;
                }
                ?>
            </tr>
            </thead>
            <tbody>
            <?php
            $style = '';

            foreach ( $wp_discounts_search->get_results() as $discount ) {

                $discount_obj = new \Tickera\TC_Discount( $discount->ID );
                $discount_object = apply_filters( 'tc_discount_object_details', $discount_obj->details );
                $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
                ?>
                <tr id='user-<?php echo esc_attr( $discount_object->ID ); ?>' <?php echo wp_kses_post($style); ?>>
                    <?php $n = 1; ?>
                    <?php foreach ( $columns as $key => $col ) : ?>

                        <!-- Discount code used count -->
                        <?php if ( $key == 'used_count' ) :

                            $discount_title = $discount->post_title;
                            $discount_used_times = $discounts->discount_used_times( $discount_title );
                            ?>
                            <td>
                                <?php echo esc_html( absint( $discount_used_times ) ); ?>
                            </td>

                        <?php elseif ( $key == 'edit' ) : ?>
                            <td>
                                <a class="discounts_edit_link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=tc_events&page=' . $page . '&action=' . $key . '&ID=' . $discount_object->ID ) ); ?>"><?php esc_html_e( 'Edit', 'tickera-event-ticketing-system' ); ?></a>
                            </td>

                        <?php elseif ( $key == 'delete' ) : ?>
                            <td>
                                <a class="discounts_edit_link tc_delete_link" href="<?php echo esc_url( wp_nonce_url( 'edit.php?post_type=tc_events&page=' . $page . '&action=' . $key . '&ID=' . $discount_object->ID, 'delete_' . $discount_object->ID ) ); ?>"><?php esc_html_e( 'Delete', 'tickera-event-ticketing-system' ); ?></a>
                            </td>

                        <?php else : ?>
                            <td>
                                <?php

                                $post_field_type = $discounts->check_field_property( $key, 'post_field_type' );
                                if ( isset( $post_field_type ) && $post_field_type == 'post_meta' ) {
                                    echo wp_kses_post( apply_filters( 'tc_discount_field_value', $discount_object->{$key}, $post_field_type, $key ) );

                                } else {
                                    echo wp_kses_post( apply_filters( 'tc_discount_field_value', ( isset( $discount_object->{$post_field_type} ) ? $discount_object->{$post_field_type} : $discount_object->{$key} ), $post_field_type, $key ) );
                                }
                                ?>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                <?php
            } ?>
            <?php
            if ( count( $wp_discounts_search->get_results() ) == 0 ) { ?>
                <tr>
                    <td colspan="6">
                        <div class="zero-records"><?php esc_html_e( 'No discounts found.', 'tickera-event-ticketing-system' ) ?></div>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table><!--/widefat shadow-table-->
        <div class="tablenav tc-tablenav">
            <div class="tablenav-pages"><?php esc_html( $wp_discounts_search->page_links() ); ?></div>
        </div><!--/tablenav-->
        <div class="clear"></div>
    </div>
</div>
