<?php
if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $tc_template_elements, $tc_gateway_plugins, $wpdb;

$templates = new \Tickera\TC_Ticket_Templates();
$template_elements = new \Tickera\TC_Ticket_Template_Elements();
$template_elements_set = array();
$page = sanitize_key( $_GET[ 'page' ] );

if ( isset( $_POST[ 'add_new_template' ] ) ) {

    if ( check_admin_referer( 'tickera_save_template' ) ) {

        if ( current_user_can( 'manage_options' ) || current_user_can( 'save_template_cap' ) ) {
            $templates->add_new_template();
            $message = __( 'Template data has been successfully saved.', 'tickera-event-ticketing-system' );

        } else {
            $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        }
    }
}

if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'edit' ) {
    $post_id = (int)$_GET[ 'ID' ];
    $template = new \Tickera\TC_Template( $post_id );
    $template_elements = new \Tickera\TC_Ticket_Template_Elements( $post_id );
    $template_elements_set = $template_elements->get_all_set_elements();
}

if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'delete' ) {

    if ( !isset( $_POST[ '_wpnonce' ] ) ) {
        check_admin_referer( 'delete_' . (int)$_GET[ 'ID' ] );

        if ( current_user_can( 'manage_options' ) || current_user_can( 'delete_template_cap' ) ) {
            $template = new \Tickera\TC_Template( (int)$_GET[ 'ID' ] );
            $template->delete_template();
            $message = __( 'Template has been successfully deleted.', 'tickera-event-ticketing-system' );

        } else {
            $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        }
    }
}

/**
 * Click to duplicate ticket template start
 */
if ( ( isset( $_GET[ 'action' ] ) ) && $_GET[ 'action' ] == 'tc_duplicate' ) {

    // Get the original post ID
    $post_id = (int)$_GET[ 'ID' ];

    // Fetch all post data by ID
    $post = get_post( $post_id );

    /*
     * If you don't want current user to be the new post author,
     * then change next couple of lines to this: $new_post_author = $post->post_author;
     */
    $current_user = wp_get_current_user();
    $new_post_author = $current_user->ID;

    /*
     * If post data exists, create the post duplicate
     */
    if ( isset( $post ) && $post != null ) {

        // New post data array
        $new_post_author = wp_get_current_user();
        $new_post_date = current_time( 'mysql' );
        $new_post_date_gmt = get_gmt_from_date( $new_post_date );
        $duplicate_title_extension = ' [duplicate]';

        $args = apply_filters( 'tc_duplicate_template_args', array(
            'post_author'               => (int) $new_post_author->ID,
            'post_date'                 => $new_post_date,
            'post_date_gmt'             => $new_post_date_gmt,
            'post_content'              => $post->post_content,
            'post_content_filtered'     => $post->post_content_filtered,
            'post_title'                => $post->post_title . $duplicate_title_extension,
            'post_excerpt'              => $post->post_excerpt,
            'post_status'               => 'draft',
            'post_type'                 => $post->post_type,
            'comment_status'            => $post->comment_status,
            'ping_status'               => $post->ping_status,
            'post_password'             => $post->post_password,
            'to_ping'                   => $post->to_ping,
            'pinged'                    => $post->pinged,
            'post_modified'             => $new_post_date,
            'post_modified_gmt'         => $new_post_date_gmt,
            'menu_order'                => (int)$post->menu_order,
            'post_mime_type'            => $post->post_mime_type,
        ), $post_id );

        // Insert the post by wp_insert_post() function
        $new_post_id = wp_insert_post( tickera_sanitize_array( $args, true ) );

        /*
         * Get all current post terms ad set them to the new post draft
         * Returns array of taxonomy names for post type, ex array("category", "post_tag");
         */
        $taxonomies = get_object_taxonomies( $post->post_type );

        foreach ( $taxonomies as $taxonomy ) {
            $post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
            wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
        }

        // Clone post meta onto new post
        $post_meta = get_post_meta( $post_id );
        foreach ( $post_meta as $key => $value ) {
            add_post_meta( $new_post_id, sanitize_key( $key ), sanitize_text_field( $value[ 0 ] ) );
        }

        // Finally, redirect to the edit post screen for the new draft
        $new_post_url = add_query_arg( array(
            'post_type' => sanitize_text_field( $_GET[ 'post_type' ] ),
            'page' => $page,
            'action' => 'edit',
            'ID' => $new_post_id
        ), admin_url( 'edit.php' ) );

        // Redirect ticket template to new url
        tickera_redirect( $new_post_url, true, false );

    } else {
        wp_die( 'Post creation failed, could not find original post: ' . $post_id );
    }
}

// Click to duplicate ticket template end
if ( isset( $_GET[ 'page_num' ] ) ) {
    $page_num = (int)$_GET[ 'page_num' ];

} else {
    $page_num = 1;
}

if ( isset( $_GET[ 's' ] ) ) {
    $templatessearch = sanitize_text_field( $_GET[ 's' ] );

} else {
    $templatessearch = '';
}

$wp_templates_search = new \Tickera\TC_Templates_Search( $templatessearch, $page_num );
$fields = $templates->get_template_col_fields();
$columns = $templates->get_columns();

$templates_url = add_query_arg( array(
    'post_type' => 'tc_events',
    'page' => $page,
), admin_url( 'edit.php' ) );

$templates_add_new_url = add_query_arg( array(
    'post_type' => 'tc_events',
    'page' => $page,
    'action' => 'add_new'
), admin_url( 'edit.php' ) ); ?>
    <div class="wrap tc_wrap">
    <h2><?php esc_html_e( 'Ticket Templates', 'tickera-event-ticketing-system' ); ?>
        <?php if ( isset( $_GET[ 'action' ] ) && ( $_GET[ 'action' ] == 'edit' || $_GET[ 'action' ] == 'add_new' ) ) : ?>
            <a href="<?php echo esc_url( $templates_url ); ?>"
               class="add-new-h2"><?php esc_html_e( 'Back', 'tickera-event-ticketing-system' ); ?></a>
        <?php elseif ( tickera_iw_is_pr() && !\Tickera\tets_fs()->is_free_plan() ) : ?>
            <a href="<?php echo esc_url( $templates_add_new_url ); ?>"
               class="add-new-h2"><?php esc_html_e( 'Add New', 'tickera-event-ticketing-system' ); ?></a>
        <?php endif; ?>
    </h2>
<?php if ( isset( $message ) ) : ?>
    <div id="message" class="updated fade"><p><?php echo esc_html( $message ); ?></p></div>
<?php endif;
if ( !isset( $_GET[ 'action' ] ) || ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'delete' ) || ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'add_new' && isset( $_POST[ 'add_new_template' ] ) ) ) { ?>
    <div class="tablenav">
        <div class="alignright actions new-actions">
            <form method="get" action="edit.php?post_type=tc_events&page=<?php echo esc_attr( $page ); ?>"
                  class="search-form">
                <p class="search-box">
                    <input type='hidden' name='page' value='<?php echo esc_attr( $page ); ?>'/>
                    <input type="hidden" name="post_type" value="tc_events"/>
                    <label class="screen-reader-text"><?php esc_html_e( 'Search Templates', 'tickera-event-ticketing-system' ); ?>
                        :</label>
                    <input type="text" value="<?php echo esc_attr( $templatessearch ); ?>" name="s">
                    <input type="submit" class="button"
                           value="<?php esc_html_e( 'Search Templates', 'tickera-event-ticketing-system' ); ?>">
                </p>
            </form>
        </div><!--/alignright-->
    </div><!--/tablenav-->
    <table cellspacing="0" class="widefat shadow-table">
        <thead>
        <tr>
            <?php
            $n = 1;
            foreach ( $columns as $key => $col ) { ?>
                <th style="" class="manage-column column-<?php echo esc_attr( $key ); ?>"
                    width="<?php echo esc_attr( isset( $col_sizes[ $n ] ) ? esc_attr( $col_sizes[ $n ] . '%' ) : '' ); ?>"
                    id="<?php echo esc_attr( $key ); ?>" scope="col"><?php echo esc_html( $col ); ?></th>
                <?php $n++;
            } ?>
        </tr>
        </thead>
        <tbody>
        <?php
        $style = '';
        foreach ( $wp_templates_search->get_results() as $template ) {
            if ( $template->post_status !== 'trash' ) {
                $template_obj = new \Tickera\TC_Template( $template->ID );
                $template_object = apply_filters( 'tc_template_object_details', $template_obj->details );
                $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
                ?>
                <tr id='user-<?php echo esc_attr( $template_object->ID ); ?>' <?php echo wp_kses_post( ( ' class="alternate"' == $style ) ? '' : ' class="alternate"' ); ?>>
                    <?php
                    $n = 1;
                    foreach ( $columns as $key => $col ) {
                        if ( $key == 'edit' ) { ?>
                            <td>
                            <a class="templates_edit_link"
                               href="<?php echo esc_url( admin_url( 'edit.php?post_type=tc_events&page=' . $page . '&action=' . $key . '&ID=' . $template_object->ID, 'save_template' ) ); ?>"><?php esc_html_e( 'Edit', 'tickera-event-ticketing-system' ); ?></a>
                            </td><?php
                        } elseif ( $key == 'delete' ) { ?>
                            <td>
                            <a class="templates_edit_link tc_delete_link"
                               href="<?php echo esc_url( wp_nonce_url( 'edit.php?post_type=tc_events&page=' . $page . '&action=' . $key . '&ID=' . $template_object->ID, 'delete_' . $template_object->ID ) ); ?>"><?php esc_html_e( 'Delete', 'tickera-event-ticketing-system' ); ?></a>
                            </td><?php
                        } elseif ( $key == 'tc_duplicate' ) { // Add Duplicate field ?>
                            <td>
                            <a class="templates_edit_link" id="tc_template_duplicate"
                               title="<?php esc_attr_e( 'Duplicate this ticket template', 'tickera-event-ticketing-system' ); ?>"
                               href="<?php echo esc_url( wp_nonce_url( 'edit.php?post_type=tc_events&page=' . $page . '&action=' . $key . '&ID=' . $template_object->ID, 'tc_duplicate' . $template_object->ID ) ); ?>"
                               rel="permalink"><?php esc_html_e( 'Duplicate', 'tickera-event-ticketing-system' ); ?></a>
                            </td><?php
                        } else { ?>
                            <td>
                                <?php echo esc_html( apply_filters( 'tc_template_field_value', $template_object->{$key} ) ); ?>
                            </td>
                            <?php
                        }
                    } ?>
                </tr>
                <?php
            }
        }
        if ( count( $wp_templates_search->get_results() ) == 0 ) { ?>
            <tr>
                <td colspan="6">
                    <div class="zero-records"><?php esc_html_e( 'No templates found.', 'tickera-event-ticketing-system' ) ?></div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table><!--/widefat shadow-table-->
    <p>
        <?php if ( !tickera_iw_is_pr() || \Tickera\tets_fs()->is_free_plan() ) { ?>
            <a class="tc_link" target="_blank"
               href="https://tickera.com/?utm_source=plugin&utm_medium=upsell&utm_campaign=templates"><?php esc_html_e( 'Create unlimited number of ticket templates, get premium support and unlock additional features.', 'tickera-event-ticketing-system' ); ?></a>
        <?php } ?>
    </p>
    <div class="tablenav">
        <div class="tablenav-pages"><?php esc_html( $wp_templates_search->page_links() ); ?></div>
    </div><!--/tablenav-->
<?php } else { ?>
    <form action="" method="post" enctype="multipart/form-data">
        <div class="left-holder">
            <?php wp_nonce_field( 'tickera_save_template' );
            if ( isset( $post_id ) ) { ?>
                <input type="hidden" name="post_id" value="<?php echo esc_attr( (int)$post_id ); ?>"/>
            <?php } ?>
            <h4><?php esc_html_e( 'Template Title', 'tickera-event-ticketing-system' ); ?></h4>
            <input type="text" name="template_title"
                   value="<?php echo esc_attr( isset( $template->details->post_title ) ? $template->details->post_title : '' ); ?>">
            <h4><?php esc_html_e( 'Ticket Elements', 'tickera-event-ticketing-system' ); ?></h4>
            <input type="hidden" name="template_id"
                   value="<?php echo esc_attr( isset( $_GET[ 'ID' ] ) ? (int)$_GET[ 'ID' ] : '' ); ?>"/>
            <ul class="sortables droptrue" id="ticket_elements">
                <?php foreach ( $tc_template_elements as $element ) {
                    $element_class = new $element[ 0 ];
                    if ( !in_array( $element[ 0 ], $template_elements_set ) ) { ?>
                        <li class="ui-state-default" data-class="<?php echo esc_attr( $element[ 0 ] ); ?>">
                            <div class="elements-wrap">
                                <div class="element_title"><?php echo esc_html( $element[ 1 ] ); ?>
                                    <a class="close-this" href="#"><i class="fa fa-times"></i></a></div>
                                <div class="element-icon">
                                    <?php if ( empty( $element_class->font_awesome_icon ) ) { ?>
                                        <i class="fa fa-plus-circle"></i>
                                    <?php } else { ?>
                                        <?php echo wp_kses_post( $element_class->font_awesome_icon ); ?>
                                    <?php } ?>
                                </div><!-- .element-icon -->
                            </div><!-- .elements-wrap -->
                            <div class="element_content">
                                <?php echo wp_kses( $element_class->admin_content(), wp_kses_allowed_html( 'tickera' ) ); ?>
                            </div>
                        </li>
                        <?php
                    }
                } ?>
            </ul>
            <br clear="all"/>
            <h4><?php esc_html_e( 'Ticket', 'tickera-event-ticketing-system' ); ?></h4>
            <div class="rows ticket-elements-drop-area">
                <?php for ( $i = 1; $i <= apply_filters( 'tc_ticket_template_row_number', 10 ); $i++ ) { ?>
                    <ul id="row_<?php echo esc_attr( $i ); ?>" class="sortables droptrue">
                        <span class="row_num_info"><?php esc_html_e( 'Row', 'tickera-event-ticketing-system' ); ?><?php echo esc_html( $i ); ?></span>
                        <input type="hidden" class="rows_classes" name="rows_<?php echo esc_attr( $i ); ?>_post_meta"
                               value=""/>
                        <?php
                        if ( isset( $post_id ) ) {
                            $rows_elements = get_post_meta( $post_id, 'rows_' . $i, true );
                            if ( isset( $rows_elements ) && $rows_elements !== '' ) {
                                $element_class_names = explode( ',', $rows_elements );
                                foreach ( $element_class_names as $element_class_name ) {

                                    $element_class_name = str_replace( 'Tickera\\Ticket\\Element\\', '', $element_class_name );
                                    $element_class_namespace = '\\Tickera\\Ticket\\Element\\' . $element_class_name;

                                    if ( class_exists( $element_class_namespace ) ) {

                                        if ( isset( $post_id ) ) {
                                            $element = new $element_class_namespace( $post_id );
                                        } else {
                                            $element = new $element_class_namespace;
                                        }
                                        ?>
                                    <li class="ui-state-default cols"
                                        data-class="<?php echo esc_attr( $element_class_name ); ?>">
                                        <div class="elements-wrap">
                                            <div class="element_title"><?php echo esc_html( $element->element_title ); ?>
                                                <a class="close-this" href="#"><i class="fa fa-times"></i></a>
                                            </div>
                                            <div class="element-icon">
                                                <?php if ( empty( $element->font_awesome_icon ) ) { ?>
                                                    <i class="fa fa-plus-circle"></i>
                                                <?php } else { ?>
                                                    <?php echo wp_kses_post( $element->font_awesome_icon ); ?>
                                                <?php } ?>
                                            </div><!-- .element-icon -->
                                        </div><!-- .elements-wrap -->
                                        <div class="element_content"><?php
                                            echo wp_kses( $element->admin_content(), wp_kses_allowed_html( 'tickera' ) );
                                        ?></div>
                                        </li><?php
                                    }
                                }
                            }
                        } ?>
                    </ul>
                <?php } ?>
                <br style="clear:both">
            </div>
            <input type="hidden" name="rows_number_post_meta"
                   value="<?php echo esc_attr( (int) apply_filters( 'tc_ticket_template_row_number', 10 ) ); ?>"/>
            <br clear="all"/>
            <?php submit_button( __( 'Save', 'tickera-event-ticketing-system' ), 'primary', 'add_new_template', true ); ?>
        </div>
        <div class="right-holder">
            <h4><?php esc_html_e( 'Ticket PDF Settings', 'tickera-event-ticketing-system' ); ?></h4>
            <div id="template_document_settings"><?php
                do_action( 'tc_template_elements_side_bar_before_fonts', $template_elements );
                $template_elements->tcpdf_get_fonts();
                do_action( 'tc_template_elements_side_bar_before_document_sizes', $template_elements );
                $template_elements->get_document_sizes();
                do_action( 'tc_template_elements_side_bar_before_orientation', $template_elements );
                $template_elements->get_document_orientation();
                do_action( 'tc_template_elements_side_bar_before_margins', $template_elements );
                $template_elements->get_document_margins();
                do_action( 'tc_template_elements_side_bar_before_background_image', $template_elements );
                $template_elements->get_full_background_image();
                do_action( 'tc_template_document_settings', $template_elements ); ?>
                <br/>
                <?php submit_button( __( 'Save', 'tickera-event-ticketing-system' ), 'primary', 'add_new_template', false ); ?>
                <div class="clear"></div>
            </div>
        </div>
        <div class="right-holder right-holder-second">
            <?php if ( !isset( $_GET[ 'ID' ] ) ) { ?>
                <p><?php esc_html_e( 'NOTE: After saving, you will have an option to see a preview of the ticket.', 'tickera-event-ticketing-system' ); ?></p>
            <?php } else { ?>
                <p><?php echo wp_kses_post( __( 'NOTE: Save changes first, then check the preview.</br></br><strong>Important:</strong> Once done with creating a ticket template, make a test purchase of a ticket that is using this template and test ticket scanning functionality prior to going live with the ticket sales.', 'tickera-event-ticketing-system' ) ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tc_events&page=' . sanitize_text_field( $_GET[ 'page' ] ) . '&action=preview&ID=' . (int)$_GET[ 'ID' ] ) ); ?>"
                   class="button button-secondary"
                   target="_blank"><?php esc_html_e( 'Preview', 'tickera-event-ticketing-system' ); ?></a>
            <?php } ?>
        </div>
    </form>
    </div>
<?php }
