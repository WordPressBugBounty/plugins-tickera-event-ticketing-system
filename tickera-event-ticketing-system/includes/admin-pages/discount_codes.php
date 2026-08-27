<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file is used only on Tickera-specific admin-side custom settings or sections.
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $wpdb;

$discounts = new \Tickera\TC_Discounts();
$page = isset( $_GET[ 'page' ] ) ? sanitize_key( wp_unslash( $_GET[ 'page' ] ) ) : '';
$can_delete_discounts = current_user_can( 'manage_options' ) || current_user_can( 'delete_discount_cap' );

$settings_discount_url = add_query_arg(
    array(
        'post_type' => 'tc_events',
        'page' => $page,
    ),
    admin_url( 'edit.php' )
);

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
if ( isset( $_GET[ 'action' ] ) && 'edit' == sanitize_text_field( wp_unslash( $_GET[ 'action' ] ) ) ) {

    $id = isset( $_GET[ 'ID' ] ) ? (int) $_GET[ 'ID' ] : 0;
    if ( $id ) {
        $tickera_discount = new \Tickera\TC_Discount( $id );
        $post_id = $id;
    }
}

/**
 * Delete discount code
 */
if ( isset( $_GET[ 'action' ] ) && 'delete' == sanitize_text_field( wp_unslash( $_GET[ 'action' ] ) ) ) {

    if ( ! isset( $_POST[ '_wpnonce' ] ) ) {

        $id = isset( $_GET[ 'ID' ] ) ? (int) $_GET[ 'ID' ] : 0;

        if ( $id && check_admin_referer( 'delete_' . $id ) ) {

            if ( $can_delete_discounts ) {
                $tickera_discount = new \Tickera\TC_Discount( $id );
                $tickera_discount->delete_discount();
                $message = __( 'Discount Code has been successfully deleted.', 'tickera-event-ticketing-system' );

            } else {
                $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
            }
        }
    }
}

/**
 * Bulk delete Discount Codes from the current table page.
 */
if ( isset( $_POST[ 'tc_bulk_discounts_request' ] ) ) {

    check_admin_referer( 'tickera_bulk_delete_discounts', 'tc_bulk_discounts_nonce' );

    $bulk_page_num = isset( $_POST[ 'page_num' ] ) ? max( 1, absint( wp_unslash( $_POST[ 'page_num' ] ) ) ) : 1;
    $bulk_search = isset( $_POST[ 's' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 's' ] ) ) : '';
    $bulk_action = isset( $_POST[ 'bulk_action' ] ) ? sanitize_key( wp_unslash( $_POST[ 'bulk_action' ] ) ) : '';
    $discount_ids = isset( $_POST[ 'discount_ids' ] ) ? array_map( 'absint', (array) wp_unslash( $_POST[ 'discount_ids' ] ) ) : array();
    $discount_ids = array_values( array_unique( array_filter( $discount_ids ) ) );

    $redirect_args = array(
        'post_type' => 'tc_events',
        'page' => $page,
        'page_num' => $bulk_page_num,
    );

    if ( '' !== $bulk_search ) {
        $redirect_args[ 's' ] = $bulk_search;
    }

    if ( ! $can_delete_discounts ) {
        $redirect_args[ 'tc_discounts_bulk_error' ] = 'permission';

    } elseif ( 'delete' !== $bulk_action || empty( $discount_ids ) ) {
        $redirect_args[ 'tc_discounts_bulk_error' ] = 'no_selection';

    } else {
        $deleted_count = 0;
        $failed_count = 0;

        foreach ( $discount_ids as $discount_id ) {
            $discount_post = get_post( $discount_id );

            if ( ! $discount_post || 'tc_discounts' !== $discount_post->post_type ) {
                $failed_count++;
                continue;
            }

            $tickera_discount = new \Tickera\TC_Discount( $discount_id );

            if ( $tickera_discount->delete_discount( true ) ) {
                $deleted_count++;

            } else {
                $failed_count++;
            }
        }

        // If the final page became empty, return to the nearest remaining page.
        $remaining_discounts = new \Tickera\TC_Discounts_Search( $bulk_search, 1 );
        $last_page = max( 1, (int) ceil( $remaining_discounts->get_count_of_all() / $remaining_discounts->per_page ) );
        $redirect_args[ 'page_num' ] = min( $bulk_page_num, $last_page );
        $redirect_args[ 'tc_discounts_bulk_deleted' ] = $deleted_count;

        if ( $failed_count ) {
            $redirect_args[ 'tc_discounts_bulk_failed' ] = $failed_count;
        }
    }

    wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'edit.php' ) ) );
    exit;
}

if ( isset( $_GET[ 'tc_discounts_bulk_error' ] ) ) {
    $bulk_error = sanitize_key( wp_unslash( $_GET[ 'tc_discounts_bulk_error' ] ) );

    if ( 'permission' === $bulk_error ) {
        $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        $message_class = 'notice notice-error';

    } elseif ( 'no_selection' === $bulk_error ) {
        $message = __( 'Select at least one discount code to delete.', 'tickera-event-ticketing-system' );
        $message_class = 'notice notice-warning';
    }

} elseif ( isset( $_GET[ 'tc_discounts_bulk_deleted' ] ) ) {
    $deleted_count = absint( $_GET[ 'tc_discounts_bulk_deleted' ] );
    $failed_count = isset( $_GET[ 'tc_discounts_bulk_failed' ] ) ? absint( $_GET[ 'tc_discounts_bulk_failed' ] ) : 0;

    if ( $deleted_count ) {
        $message = sprintf(
            /* translators: %d: Number of discount codes deleted. */
            _n( '%d discount code has been successfully deleted.', '%d discount codes have been successfully deleted.', $deleted_count, 'tickera-event-ticketing-system' ),
            $deleted_count
        );
        $message_class = 'notice notice-success';

    } elseif ( $failed_count ) {
        $message = sprintf(
            /* translators: %d: Number of discount codes that could not be deleted. */
            _n( '%d discount code could not be deleted.', '%d discount codes could not be deleted.', $failed_count, 'tickera-event-ticketing-system' ),
            $failed_count
        );
        $message_class = 'notice notice-error';

    } else {
        $message = __( 'No discount codes were deleted.', 'tickera-event-ticketing-system' );
        $message_class = 'notice notice-warning';
    }

    if ( $deleted_count && $failed_count ) {
        $message .= ' ' . sprintf(
            /* translators: %d: Number of discount codes that could not be deleted. */
            _n( '%d discount code could not be deleted.', '%d discount codes could not be deleted.', $failed_count, 'tickera-event-ticketing-system' ),
            $failed_count
        );
    }
}

$page_num = isset( $_GET[ 'page_num' ] ) ? max( 1, absint( wp_unslash( $_GET[ 'page_num' ] ) ) ) : 1;
$discountssearch = ( isset( $_GET[ 's' ] ) ) ? sanitize_text_field( wp_unslash( $_GET[ 's' ] ) ) : '';

$wp_discounts_search = new \Tickera\TC_Discounts_Search( $discountssearch, $page_num );
$discount_results = $wp_discounts_search->get_results();
$fields = $discounts->get_discount_fields();
$columns = $discounts->get_columns();
?>
<div class="wrap tc_wrap tc-discount-codes-content">
    <?php if ( isset( $message ) ) : ?>
        <div id="message" class="<?php echo esc_attr( isset( $message_class ) ? $message_class : 'updated fade' ); ?>"><p><?php echo esc_html( $message ); ?></p></div>
    <?php endif; ?>
    <div id="poststuff" class="metabox-holder tc-discount-form<?php echo esc_attr( isset( $post_id ) ? ' tc-edit' : '' ); ?>">
        <div class="postbox">
            <h3><span><?php echo esc_html( $discounts->form_title ); ?></span></h3>
            <div class="inside">
                <form action="" method="post" enctype="multipart/form-data" id="tc_discount_code_form">
                    <?php wp_nonce_field( 'tickera_save_discount' ); ?>
                    <?php if ( isset( $post_id ) ) { ?>
                        <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>"/>
                    <?php } ?>
                    <table class="discount-table form-table">
                        <tbody>
                        <?php foreach ( $fields as $field ) { ?>
                            <?php if ( $discounts->is_valid_discount_field_type( $field[ 'field_type' ] ) && ( ! isset( $field[ 'form_visibility' ] ) || $field[ 'form_visibility' ] ) ) { ?>
                                <tr valign="top" <?php echo wp_kses_post( \Tickera\TC_Fields::conditionals( $field, false ) ); ?>>
                                    <th scope="row"><label for="<?php echo esc_attr( $field[ 'field_name' ] ); ?>"><?php echo esc_html( $field[ 'field_title' ] ); ?></label></th>
                                    <td>
                                        <?php tickera_do_action( 'tickera_before_discounts_field_type_check' ); ?>
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
                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span><?php

                                        } elseif ( $field[ 'field_type' ] == 'text' ) { ?>
                                            <input type="text" <?php
                                            if ( isset( $field[ 'placeholder' ] ) ) {
                                                echo wp_kses_post( 'placeholder="' . esc_attr( $field[ 'placeholder' ] ) . '"' );
                                            }
                                            ?> class="regular-<?php echo esc_attr( $field[ 'field_type' ] ); ?> <?php echo esc_attr( $field[ 'field_name' ] ); ?>" value="<?php
                                            if ( isset( $tickera_discount ) ) {
                                                if ( $field[ 'post_field_type' ] == 'post_meta' ) {
                                                    echo esc_attr( isset( $tickera_discount->details->{$field[ 'field_name' ]} ) ? $tickera_discount->details->{$field[ 'field_name' ]} : '' );

                                                } else {
                                                    echo esc_attr( $tickera_discount->details->{$field[ 'post_field_type' ]} );
                                                }
                                            }
                                            ?>" id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" name="<?php echo esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>" <?php echo esc_attr( isset( $field[ 'required' ] ) ? 'required' : '' ); ?> <?php echo esc_attr( isset( $field[ 'number' ] ) ? 'number="true"' : '' ); ?>>
                                            <span class="description"><?php echo esc_html($field[ 'field_description' ]); ?></span><?php

                                        } elseif ( $field[ 'field_type' ] == 'textarea' ) { ?>
                                            <textarea class="regular-<?php echo esc_html($field[ 'field_type' ]); ?> <?php echo esc_attr( $field[ 'field_name' ] ); ?>" id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" name="<?php echo esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"><?php
                                                if ( isset( $tickera_discount ) ) {
                                                    if ( $field[ 'post_field_type' ] == 'post_meta' ) {
                                                        echo esc_textarea( isset( $tickera_discount->details->{$field[ 'field_name' ]} ) ? $tickera_discount->details->{$field[ 'field_name' ]} : '' );

                                                    } else {
                                                        echo esc_textarea( $tickera_discount->details->{$field[ 'post_field_type' ]} );
                                                    }
                                                }
                                                ?>
                                            </textarea>
                                            <br/><?php echo esc_html( $field[ 'field_description' ] );

                                        } elseif ( $field[ 'field_type' ] == 'image' ) { ?>
                                            <div class="file_url_holder">
                                                <label>
                                                    <input class="file_url <?php echo esc_attr( $field[ 'field_name' ] ); ?>" type="text" size="36" name="<?php echo esc_attr( $field[ 'field_name' ] . '_file_url_' . $field[ 'post_field_type' ] ); ?>" value="<?php
                                                           if ( isset( $tickera_discount ) ) {
                                                               echo esc_attr( isset( $tickera_discount->details->{$field[ 'field_name' ] . '_file_url'} ) ? $tickera_discount->details->{$field[ 'field_name' ] . '_file_url'} : '' );
                                                           }
                                                           ?>"
                                                    />
                                                    <input class="file_url_button button-secondary" type="button" value="<?php esc_html_e( 'Browse', 'tickera-event-ticketing-system' ); ?>"/><?php echo esc_html( $field[ 'field_description' ] ); ?>
                                                </label>
                                            </div><?php

                                        } elseif ( $field[ 'field_type' ] == 'select' ) {
                                            $selected = isset( $tickera_discount->details->{$field[ 'field_name' ]} ) ? $tickera_discount->details->{$field[ 'field_name' ]} : ''; ?>
                                            <select id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" class="regular-<?php echo esc_attr( $field[ 'field_type' ] ); ?> <?php echo esc_attr( $field[ 'field_name' ] ); ?>" name="<?php echo esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>" <?php echo esc_attr( isset( $field[ 'required' ] ) ? 'required' : '' ); ?>>
                                                <?php foreach( $field[ 'options' ] as $key => $value ) : ?>
                                                    <option value="<?php echo esc_attr( $key ) ?>" <?php selected( $selected, $key, true ) ?>><?php echo esc_html( $value ) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="description"><?php echo esc_html($field[ 'field_description' ]); ?></span>
                                            <?php
                                        }
                                        tickera_do_action( 'tickera_after_discounts_field_type_check' ); ?>
                                    </td>
                                </tr><?php
                            }
                        } ?>
                        </tbody>
                    </table>
                    <div class="tc-discount-form-actions">
                        <?php submit_button( ( isset( $_REQUEST[ 'action' ] ) && 'edit' == sanitize_text_field( wp_unslash( $_REQUEST[ 'action' ] ) ) ? __( 'Update', 'tickera-event-ticketing-system' ) : __( 'Add New', 'tickera-event-ticketing-system' ) ), 'primary', 'add_new_discount', false ); ?>
                        <a <?php echo wp_kses_post( ( isset( $_GET[ 'action' ] ) && 'edit' == sanitize_text_field( wp_unslash( $_GET[ 'action' ] ) ) ) ) ? 'href="' . esc_url( $settings_discount_url ) . '"' : 'href="#"' . ' id="cancel_add_edit"'; ?> class="tc-tickera-secondary"><?php esc_html_e( 'Cancel', 'tickera-event-ticketing-system' ); ?></a>
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
                    <?php if ( $can_delete_discounts ) : ?>
                        <form id="tc-discounts-bulk-form" method="post" action="<?php echo esc_url( $settings_discount_url ); ?>">
                            <?php wp_nonce_field( 'tickera_bulk_delete_discounts', 'tc_bulk_discounts_nonce' ); ?>
                            <input type="hidden" name="tc_bulk_discounts_request" value="1"/>
                            <input type="hidden" name="page_num" value="<?php echo esc_attr( $page_num ); ?>"/>
                            <input type="hidden" name="s" value="<?php echo esc_attr( $discountssearch ); ?>"/>
                            <label for="tc-discounts-bulk-action" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'tickera-event-ticketing-system' ); ?></label>
                            <select name="bulk_action" id="tc-discounts-bulk-action" class="tc-regular-select tc-bulk-action-select">
                                <option value="-1"><?php esc_html_e( 'Bulk actions', 'tickera-event-ticketing-system' ); ?></option>
                                <option value="delete"><?php esc_html_e( 'Delete permanently', 'tickera-event-ticketing-system' ); ?></option>
                            </select>
                            <input type="submit" id="tc-discounts-bulk-apply" class="button action" value="<?php esc_attr_e( 'Apply', 'tickera-event-ticketing-system' ); ?>"/>
                        </form>
                    <?php endif; ?>
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
        <table cellspacing="0" class="wp-list-table widefat shadow-table">
            <thead>
            <tr>
                <?php if ( $can_delete_discounts ) : ?>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="tc-discounts-select-all"><?php esc_html_e( 'Select all discount codes on this page', 'tickera-event-ticketing-system' ); ?></label>
                        <input id="tc-discounts-select-all" class="tc-discounts-select-all" type="checkbox" form="tc-discounts-bulk-form"/>
                    </td>
                <?php endif; ?>
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

            foreach ( $discount_results as $tickera_discount ) {

                $discount_obj = new \Tickera\TC_Discount( $tickera_discount->ID );
                $discount_object = tickera_apply_filters( 'tickera_discount_object_details', $discount_obj->details );
                $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
                ?>
                <tr id='user-<?php echo esc_attr( $discount_object->ID ); ?>' <?php echo wp_kses_post($style); ?>>
                    <?php if ( $can_delete_discounts ) : ?>
                        <th scope="row" class="check-column">
                            <label class="screen-reader-text" for="discount-<?php echo esc_attr( (int) $discount_object->ID ); ?>"><?php echo esc_html( sprintf( /* translators: %d: Discount code ID. */ __( 'Select discount code %d', 'tickera-event-ticketing-system' ), (int) $discount_object->ID ) ); ?></label>
                            <input id="discount-<?php echo esc_attr( (int) $discount_object->ID ); ?>" class="tc-discount-select" type="checkbox" name="discount_ids[]" value="<?php echo esc_attr( (int) $discount_object->ID ); ?>" form="tc-discounts-bulk-form"/>
                        </th>
                    <?php endif; ?>
                    <?php $n = 1; ?>
                    <?php foreach ( $columns as $key => $col ) : ?>

                        <!-- Discount code used count -->
                        <?php if ( $key == 'used_count' ) :
                            $discount_title = $tickera_discount->post_title;
                            $discount_used_times = $discounts->discount_used_times( $discount_title ); ?>
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
                                    echo wp_kses_post( tickera_apply_filters( 'tickera_discount_field_value', $discount_object->{$key}, $post_field_type, $key ) );

                                } else {
                                    echo wp_kses_post( tickera_apply_filters( 'tickera_discount_field_value', ( isset( $discount_object->{$post_field_type} ) ? $discount_object->{$post_field_type} : $discount_object->{$key} ), $post_field_type, $key ) );
                                }
                                ?>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                <?php
            } ?>
            <?php
            if ( count( $discount_results ) == 0 ) { ?>
                <tr>
                    <td colspan="<?php echo esc_attr( count( $columns ) + ( $can_delete_discounts ? 1 : 0 ) ); ?>">
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
