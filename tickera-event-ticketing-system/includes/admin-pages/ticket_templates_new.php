<?php
if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $tc_template_elements, $tc_gateway_plugins, $wpdb, $tc_plugin_url;

$templates = new \Tickera\TC_Ticket_Templates();
$template_elements = new \Tickera\TC_Ticket_Template_Elements();
$page = sanitize_key( $_GET[ 'page' ] );

if ( isset( $_POST[ 'add_new_template' ] ) ) {

    if ( check_admin_referer( 'save_template' ) ) {

        if ( current_user_can( 'manage_options' ) || current_user_can( 'save_template_cap' ) ) {
            $templates->add_new_template_new();
            $message = __( 'Template data has been successfully saved.', 'tickera-event-ticketing-system' );
        } else {
            $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        }
    }
}

if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'edit' ) {
    $post_id = (int)$_GET[ 'ID' ];
    $post = get_post( $post_id );
    $template = new \Tickera\TC_Template( $post_id );
    $template_elements = new \Tickera\TC_Ticket_Template_Elements( $post_id );
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
            'menu_order'                => (int) $post->menu_order,
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

        // Duplicate all post meta just in two SQL queries
        $query = $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $post_id );
        $post_meta_infos = $wpdb->get_results( $query );
        if ( count( $post_meta_infos ) != 0 ) {
            $sql_query_sel = [];
            $table_columns = [ 'post_id', 'meta_key', 'meta_value' ];
            $prepare_table_columns_placeholder = implode( ',', array_fill( 0, count( $table_columns ), '%1s' ) );
            $sql_query = $wpdb->prepare( "INSERT INTO {$wpdb->postmeta} ($prepare_table_columns_placeholder) ", $table_columns );

            foreach ( $post_meta_infos as $meta_info ) {
                $meta_key = $meta_info->meta_key;
                $meta_value = addslashes( $meta_info->meta_value );
                $sql_query_sel[] = $wpdb->prepare( "SELECT %d, %s, %s", (int)$new_post_id, $meta_key, $meta_value );
            }
            $sql_query .= implode( " UNION ALL ", $sql_query_sel );
            $wpdb->query( $sql_query );
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
), admin_url( 'edit.php' ) );
?>
    <div class="wrap tc_wrap_zxy tc_new_ticket_template_single">
    <h2><?php esc_html_e( 'Ticket Templates * NEW *', 'tickera-event-ticketing-system' ); ?>
        <?php if ( isset( $_GET[ 'action' ] ) && ( $_GET[ 'action' ] == 'edit' || $_GET[ 'action' ] == 'add_new' ) ) : ?>
            <a href="<?php echo esc_url( $templates_url ); ?>"
               class="add-new-h2"><?php esc_html_e( 'Back', 'tickera-event-ticketing-system' ); ?></a>
        <?php elseif ( tickera_iw_is_pr() && !tets_fs()->is_free_plan() ) : ?>
            <a href="<?php echo esc_url( $templates_add_new_url ); ?>"
               class="add-new-h2"><?php esc_html_e( 'Add New', 'tickera-event-ticketing-system' ); ?></a>
        <?php endif; ?>
    </h2>
<?php if ( isset( $message ) ) : ?>
    <div id="message" class="updated fade"><p><?php echo esc_html( $message ); ?></p></div>
<?php endif; ?>
<?php if ( !isset( $_GET[ 'action' ] ) || ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'delete' ) || ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'add_new' && isset( $_POST[ 'add_new_template' ] ) ) ) { ?>
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
            foreach ( $columns as $key => $col ) {
                ?>
                <th style="" class="manage-column column-<?php echo esc_attr( $key ); ?>"
                    width="<?php echo esc_attr( isset( $col_sizes[ $n ] ) ? esc_attr( $col_sizes[ $n ] . '%' ) : '' ); ?>"
                    id="<?php echo esc_attr( $key ); ?>" scope="col"><?php echo esc_html( $col ); ?></th>
                <?php $n++;
            }
            ?>
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
                        if ( $key == 'edit' ) {
                            ?>
                            <td>
                            <a class="templates_edit_link"
                               href="<?php echo esc_url( admin_url( 'edit.php?post_type=tc_events&page=' . $page . '&action=' . $key . '&ID=' . $template_object->ID, 'save_template' ) ); ?>"><?php esc_html_e( 'Edit', 'tickera-event-ticketing-system' ); ?></a>
                            </td><?php } elseif ( $key == 'delete' ) {
                            ?>
                            <td>
                            <a class="templates_edit_link tc_delete_link"
                               href="<?php echo esc_url( wp_nonce_url( 'edit.php?post_type=tc_events&page=' . $page . '&action=' . $key . '&ID=' . $template_object->ID, 'delete_' . $template_object->ID ) ); ?>"><?php esc_html_e( 'Delete', 'tickera-event-ticketing-system' ); ?></a>
                            </td><?php } elseif ( $key == 'tc_duplicate' ) { // Add Duplicate field
                            ?>
                            <td>
                            <a class="templates_edit_link" id="tc_template_duplicate"
                               title="<?php esc_attr_e( 'Duplicate this ticket template', 'tickera-event-ticketing-system' ); ?>"
                               href="<?php echo esc_url( wp_nonce_url( 'edit.php?post_type=tc_events&page=' . $page . '&action=' . $key . '&ID=' . $template_object->ID, 'tc_duplicate' . $template_object->ID ) ); ?>"
                               rel="permalink"><?php esc_html_e( 'Duplicate', 'tickera-event-ticketing-system' ); ?></a>
                            </td><?php } else {
                            ?>
                            <td>
                                <?php echo esc_html( apply_filters( 'tc_template_field_value', $template_object->{$key} ) ); ?>
                            </td>
                            <?php
                        }
                    }
                    ?>
                </tr>
                <?php
            }
        }
        if ( count( $wp_templates_search->get_results() ) == 0 ) {
            ?>
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
    <form action="" method="post" enctype="multipart/form-data" id="ticket_template_form">
        <div class="fullwidth-holder">
            <?php
            wp_nonce_field( 'save_template' );
            if ( isset( $post_id ) ) {
                ?>
                <input type="hidden" name="post_id" value="<?php echo esc_attr( (int) $post_id ); ?>"/>
            <?php } ?>
            <h4><?php esc_html_e( 'Template Title', 'tickera-event-ticketing-system' ); ?></h4>
            <input type="text" name="template_title"
                   value="<?php echo esc_attr( isset( $template->details->post_title ) ? $template->details->post_title : '' ); ?>">
            <br clear="all"/>

            <div class="rows ticket-elements-drop-area">
                <?php
                $current_row = 0;
                $current_col = 0;
                $current_element = 0;

                $post_content = get_post_field( 'post_content', $post_id );
                $template_structure = json_decode( $post_content, true );
                $rows = isset( $template_structure[ 'rows' ] ) ? $template_structure[ 'rows' ] : array();

                if ( count( $rows ) > 0 ) {

                    foreach ( $rows as $row ) {
                        $current_row++;
                        $row_info = $row[ 'info' ];
                        ?>
                        <ul class="sortables droptrue tc-inside-template column-<?php echo esc_attr( $row_info[ 'type' ] ); ?>"
                            data-type="<?php echo esc_attr( $row_info[ 'type' ] ); ?>">
                            <?php
                            $current_col = 0;
                            foreach ( $row[ 'cols' ] as $col ) {
                                $current_col++;
                                ?>
                                <div>
                                    <ul class="sortables droptrue fullwidth ui-sortable">
                                        <?php
                                        $current_element = 0;
                                        foreach ( $col[ 'elements' ] as $elements ) {
                                            $current_element++;
                                            $element_class_name = $elements[ 'name' ];
                                            $element = new $element_class_name();
                                            ?>
                                            <li class="ui-state-default cols"
                                                data-class="<?php echo esc_attr( $element_class_name ); ?>">
                                                <div class="elements-wrap">
                                                    <div class="element_title">
                                                        <?php echo esc_html( $element->element_title ); ?>
                                                    </div>
                                                    <div class="element-icon">
                                                        <?php if ( empty( $element->font_awesome_icon ) ) { ?>
                                                            <i class="fa fa-plus-circle"></i>
                                                        <?php } else { ?>
                                                            <?php echo wp_kses_post( $element->font_awesome_icon ); ?>
                                                        <?php } ?>
                                                    </div><!-- .element-icon -->
                                                    <div class="tc-element-controls">
                                                        <a href="#" class="tc-edit-block">
                                                            <span class="tti-edit_pen_writing_icon-1"></span>
                                                        </a>
                                                        <a class="close-this" href="#">
                                                            <span class="tti-close_delete_exit_remove_icon"></span>
                                                        </a>
                                                    </div>
                                                </div><!-- .elements-wrap -->
                                                <?php $element_default_values = $template_structure[ 'rows' ][ 'row_' . $current_row ][ 'cols' ][ 'col_' . $current_col ][ 'elements' ][ 'element_' . $current_element ]; ?>
                                                <div class="element_content"><?php
                                                    //esc_html( $element->admin_content_v2($element_default_values) );
                                                    if ( method_exists( $element, 'admin_content_v2' ) ) {
                                                        wp_kses( $element->admin_content_v2( $element_default_values ), wp_kses_allowed_html( 'tickera' ) );
                                                    } else {
                                                        wp_kses( $element->admin_content(), wp_kses_allowed_html( 'tickera' ) );
                                                    }
                                                    ?></div>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            <?php } ?>
                            <div class="tc-row-control-wrap">
                                <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>
                                <span class="tc-delete-row tti-close_delete_exit_remove_icon"></span>
                            </div>
                        </ul>
                        <?php
                    }
                }
                ?>
                <br style="clear:both">
            </div>
            <br clear="all"/>
        </div>
        <div class="tc-right-resizable">
            <div class="right-holder tc-ticket-templates-options">
                <div class="tc-options-header">
                    <div class="tc-slide-button">
                        <i class="tc-arrow tc-arrow-left"></i>
                    </div><!-- tc-slide-button -->
                    <div class="tc-main-options">
                        <span class="tti-gear_maintenance_settings_icon-1"></span>
                        <span class="tc-slideout-text"><?php esc_html_e( 'Page Settings', 'tickera-event-ticketing-system' ); ?></span>
                    </div>
                    <div class="tc-all-elements">
                        <span class="tc-slideout-text tc-slideout-text-elements"><?php esc_html_e( 'Elements', 'tickera-event-ticketing-system' ); ?></span>
                        <span class="tti-boxes"></span>
                    </div>
                </div><!-- .tc-options-header -->
                <div class="tc-elements-wrap">
                    <div id="template_document_settings">
                        <?php
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
                        do_action( 'tc_template_document_settings', $template_elements );
                        ?>
                        <br/>
                        <div class="clear"></div>
                    </div>
                    <div class="tc-sortables-wrap">
                        <input type="hidden" name="template_id"
                               value="<?php echo esc_attr( isset( $_GET[ 'ID' ] ) ? (int)$_GET[ 'ID' ] : '' ); ?>"/>
                        <ul class="sortables dropfalse" id="ticket_elements">
                            <?php
                            foreach ( $tc_template_elements as $element ) {
                                $element_class = new $element[ 0 ];
                                ?>
                                <li class="ui-state-default <?php echo esc_attr( $element[ 0 ] ); ?>"
                                    data-class="<?php echo esc_attr( $element[ 0 ] ); ?>">
                                    <div class="elements-wrap">
                                        <div class="element-icon">
                                            <?php if ( empty( $element_class->font_awesome_icon ) ) { ?>
                                                <i class="fa fa-plus-circle"></i>
                                            <?php } else { ?>
                                                <?php echo wp_kses_post( $element_class->font_awesome_icon ); ?>
                                            <?php } ?>
                                        </div><!-- .element-icon -->
                                        <div class="element_title"><?php echo esc_html( $element[ 1 ] ); ?>
                                        </div><!-- .elements-wrap -->
                                        <div class="element_content">
                                            <?php
                                            if ( method_exists( $element_class, 'admin_content_v2' ) ) {
                                                wp_kses( $element_class->admin_content_v2( false ), wp_kses_allowed_html( 'tickera' ) );
                                            } else {
                                                wp_kses( $element_class->admin_content(), wp_kses_allowed_html( 'tickera' ) );
                                            }
                                            ?>
                                        </div>
                                        <div class="tc-element-controls">
                                            <a href="#" class="tc-edit-block">
                                                <span class="tti-edit_pen_writing_icon-1"></span>
                                            </a>
                                            <a class="close-this" href="#">
                                                <span class="tti-close_delete_exit_remove_icon"></span>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            <?php } ?>
                        </ul>
                    </div><!-- .tc-sortables-wrap -->
                </div>
                <textarea id="template_content" name="post_content" style="display:none;"></textarea>
                <!--<div class="right-holder-second">
					<?php if ( !isset( $_GET[ 'ID' ] ) ) { ?>
								<p><?php esc_html_e( 'NOTE: After saving, you will have an option to see a preview of the ticket.', 'tickera-event-ticketing-system' ); ?></p>
	<?php } else { ?>
								<p><?php esc_html_e( 'NOTE: Save changes first, then check the preview.</br></br><strong>Important:</strong> Once done with creating a ticket template, make a test purchase of a ticket that is using this template and test ticket scanning functionality prior to going live with the ticket sales.', 'tickera-event-ticketing-system' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tc_events&page=' . sanitize_text_field( $_GET[ 'page' ] ) . '&action=preview&ID=' . (int)$_GET[ 'ID' ] ) ); ?>" class="button button-secondary" target="_blank"><?php esc_html_e( 'Preview', 'tickera-event-ticketing-system' ); ?></a>
				<?php } ?>
					</div>-->
            </div>
        </div><!-- .tc-right-resizable -->
        <div class="tc-notification-start tc-hidden-notification">
            <?php esc_html_e( 'Start by adding new row', 'tickera-event-ticketing-system' ); ?>
            <img src="<?php echo esc_html( $tc_plugin_url ); ?>images/arrow-down.svg"/>
        </div><!-- .tc-notification-start -->
        <div class="tc-ticket-template-bar">
            <div class="tc-width-options">
                <ul class="tc-template-selection">
                    <li id="one" data-attr="1">
                        <div class="tc-fullwidth" id="tc-fullwidth"></div>
                    </li>

                    <li id="two" data-attr="2">
                        <div class="tc-halfwidth-preview"></div>
                        <div class="tc-halfwidth-preview"></div>
                    </li>

                    <li id="three" data-attr="3">
                        <div class="tc-thirds-preview"></div>
                        <div class="tc-thirds-preview"></div>
                        <div class="tc-thirds-preview"></div>
                    </li>

                    <li id="four" data-attr="4">
                        <div class="tc-fours-preview"></div>
                        <div class="tc-fours-preview"></div>
                        <div class="tc-fours-preview"></div>
                        <div class="tc-fours-preview"></div>
                    </li>

                    <li id="five" data-attr="5">
                        <div class="tc-thirds-preview"></div>
                        <div class="tc-two-thirds-preview"></div>
                    </li>

                    <li id="six" data-attr="6">
                        <div class="tc-two-thirds-preview"></div>
                        <div class="tc-thirds-preview"></div>
                    </li>

                    <li id="eight" data-attr="7">
                        <div class="tc-fours-preview"></div>
                        <div class="tc-fours-preview"></div>
                        <div class="tc-halfwidth-preview"></div>
                    </li>

                    <li id="nine" data-attr="8">
                        <div class="tc-fours-preview"></div>
                        <div class="tc-halfwidth-preview"></div>
                        <div class="tc-fours-preview"></div>
                    </li>

                    <li id="ten" data-attr="9">
                        <div class="tc-fifths-preview"></div>
                        <div class="tc-fifths-preview"></div>
                        <div class="tc-fifths-preview"></div>
                        <div class="tc-fifths-preview"></div>
                        <div class="tc-fifths-preview"></div>
                    </li>
                    <li id="eleven" data-attr="10">
                        <div class="tc-fifths-preview"></div>
                        <div class="tc-two-thirds-preview"></div>
                        <div class="tc-fifths-preview"></div>
                    </li>
                    <li id="twelve" data-attr="11">
                        <div class="tc-sixts-preview"></div>
                        <div class="tc-sixts-preview"></div>
                        <div class="tc-sixts-preview"></div>
                        <div class="tc-sixts-preview"></div>
                        <div class="tc-sixts-preview"></div>
                        <div class="tc-sixts-preview"></div>
                    </li>
                </ul>
            </div>
            <a href="#">
                <?php esc_html_e( 'Preview', 'tickera-event-ticketing-system' ); ?>
            </a>
            <?php submit_button( __( 'Save', 'tickera-event-ticketing-system' ), 'primary', 'add_new_template', false ); ?>
        </div><!-- .tc-bottom-controls -->
    </form>
    </div>
    <?php
}
