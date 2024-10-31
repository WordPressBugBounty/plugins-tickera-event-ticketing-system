<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Ticket_Template_Elements' ) ) {

    class TC_Ticket_Template_Elements {

        var $id = '';
        var $template_metas = '';
        var $element_title = '';

        function __construct( $id = '' ) {

            $this->id = $id;

            if ( $id !== '' ) {
                $this->template_metas = tickera_get_post_meta_all( $id );
            }
            $this->on_creation();
        }

        function on_creation() {
        }

        function admin_content() {
            ob_start();
            $this->get_font_sizes();
            $this->get_font_style();
            $this->get_font_colors();
            $this->get_cell_alignment();
            $this->get_element_margins();
            return apply_filters( 'tc_ticket_admin_content', ob_get_clean() );
        }

        function advanced_admin_element_settings() {}

        function advanced_admin_element_content() {}

        function ticket_content() {}

        function save() {}

        function get_all_set_elements() {

            $set_elements = array();

            for ( $i = 1; $i <= apply_filters( 'tc_ticket_template_row_number', 10 ); $i++ ) {
                $rows_elements = get_post_meta( $this->id, 'rows_' . $i, true );
                if ( isset( $rows_elements ) && $rows_elements !== '' ) {
                    $element_class_names = explode( ',', $rows_elements );

                    foreach ( $element_class_names as $element_class_name ) {
                        $set_elements[] = $element_class_name;
                    }
                }
            }

            return $set_elements;
        }

        function get_dpi( $class = '' ) { ?>
            <label>
                <?php esc_html_e( 'Resolution (DPI)', 'tickera-event-ticketing-system' ); ?>
            </label>
            <div class="<?php echo esc_attr( $class ); ?>">
                <select name="dpi_post_meta">
                    <option value="72" <?php selected( isset( $this->template_metas[ 'dpi' ] ) ? $this->template_metas[ 'dpi' ] : '72', '72', true ); ?>><?php esc_html_e( '72 (default)', 'tickera-event-ticketing-system' ); ?></option>
                    <option value="150" <?php selected( isset( $this->template_metas[ 'dpi' ] ) ? $this->template_metas[ 'dpi' ] : '72', '150', true ); ?>><?php esc_html_e( '150', 'tickera-event-ticketing-system' ); ?></option>
                    <option value="300" <?php selected( isset( $this->template_metas[ 'dpi' ] ) ? $this->template_metas[ 'dpi' ] : '72', '300', true ); ?>><?php esc_html_e( '300', 'tickera-event-ticketing-system' ); ?></option>
                    <?php do_action( 'tc_additional_ticket_dpi', $this->template_metas[ 'dpi' ] ); ?>
                </select>
            </div>
            <?php
        }

        function get_document_sizes( $class = '' ) { ?>
            <label><?php esc_html_e( 'Ticket Size', 'tickera-event-ticketing-system' ); ?></label>
            <div class="<?php echo esc_attr( $class ); ?>">
                <select name="document_ticket_size_post_meta">
                    <?php $document_ticket_size = isset( $this->template_metas[ 'document_ticket_size' ] ) ? $this->template_metas[ 'document_ticket_size' ] : 'A4'; ?>
                    <option value="A4" <?php selected( $document_ticket_size, 'A4', true ); ?>><?php esc_html_e( 'A4 (210 × 297 mm)', 'tickera-event-ticketing-system' ); ?></option>
                    <option value="A5" <?php selected( $document_ticket_size, 'A5', true ); ?>><?php esc_html_e( 'A5 (148 × 210 mm)', 'tickera-event-ticketing-system' ); ?></option>
                    <option value="A6" <?php selected( $document_ticket_size, 'A6', true ); ?>><?php esc_html_e( 'A6 (105 × 148 mm)', 'tickera-event-ticketing-system' ); ?></option>
                    <option value="A7" <?php selected( $document_ticket_size, 'A7', true ); ?>><?php esc_html_e( 'A7 (74 × 105 mm)', 'tickera-event-ticketing-system' ); ?></option>
                    <option value="A8" <?php selected( $document_ticket_size, 'A8', true ); ?>><?php esc_html_e( 'A8 (52 × 74 mm)', 'tickera-event-ticketing-system' ); ?></option>
                    <option value="ANSI_A" <?php selected( $document_ticket_size, 'ANSI_A', true ); ?>><?php esc_html_e( 'Letter (216x279 mm)', 'tickera-event-ticketing-system' ); ?></option>
                    <?php do_action( 'tc_additional_ticket_document_size', $document_ticket_size ); ?>
                </select>
            </div>
            <?php
        }

        function get_document_orientation( $class = '' ) {
            ?>
            <label><?php esc_html_e( 'Orientation', 'tickera-event-ticketing-system' ); ?></label>
            <div class="<?php echo esc_attr( $class ); ?>">
                <select name="document_ticket_orientation_post_meta" <?php echo esc_attr( $class ); ?>>
                    <?php $document_ticket_orientation = isset( $this->template_metas[ 'document_ticket_orientation' ] ) ? $this->template_metas[ 'document_ticket_orientation' ] : 'P'; ?>
                    <option value="P" <?php selected( $document_ticket_orientation, 'P', true ); ?>><?php esc_html_e( 'Portrait', 'tickera-event-ticketing-system' ); ?></option>
                    <option value="L" <?php selected( $document_ticket_orientation, 'L', true ); ?>><?php esc_html_e( 'Landscape', 'tickera-event-ticketing-system' ); ?></option>
                </select>
            </div>
            <?php
        }

        function get_document_margins() { ?>
            <label><?php esc_html_e( 'Document Margins', 'tickera-event-ticketing-system' ); ?></label>
            <?php esc_html_e( 'Top', 'tickera-event-ticketing-system' ); ?> <input class="ticket_margin" type="text" name="document_ticket_top_margin_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ 'document_ticket_top_margin' ] ) ? $this->template_metas[ 'document_ticket_top_margin' ] : '' ); ?>"/>
            <?php esc_html_e( 'Right', 'tickera-event-ticketing-system' ); ?> <input class="ticket_margin" type="text" name="document_ticket_right_margin_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ 'document_ticket_right_margin' ] ) ? $this->template_metas[ 'document_ticket_right_margin' ] : '' ); ?>"/>
            <?php esc_html_e( 'Left', 'tickera-event-ticketing-system' ); ?> <input class="ticket_margin" type="text" name="document_ticket_left_margin_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ 'document_ticket_left_margin' ] ) ? $this->template_metas[ 'document_ticket_left_margin' ] : '' ); ?>"/>
            </p>
            <?php
        }

        function get_full_background_image() { ?>
            <label><?php esc_html_e( 'Ticket Background Image', 'tickera-event-ticketing-system' ); ?>
                <input class="file_url" type="text" size="36" name="document_ticket_background_image_post_meta" value="<?php echo esc_attr( ( isset( $this->template_metas[ 'document_ticket_background_image' ] ) && $this->template_metas[ 'document_ticket_background_image' ] !== '' ) ? $this->template_metas[ 'document_ticket_background_image' ] : '' ); ?>"/>
                <input class="file_url_button button-secondary" type="button" value="<?php esc_html_e( 'Browse', 'tickera-event-ticketing-system' ); ?>"/>
            </label>
            <?php
        }

        function get_text_alignment() { ?>
            <label><?php esc_html_e( 'Text Alignment', 'tickera-event-ticketing-system' ); ?></label>
            <select name="<?php echo esc_attr( $this->element_name ); ?>_text_alignment_post_meta" class="tc_att_text_alignment">
                <option value="left" <?php selected( isset( $this->template_metas[ $this->element_name . '_text_alignment' ] ) ? $this->template_metas[ $this->element_name . '_text_alignment' ] : 'left', 'left', true ); ?>><?php esc_html_e( 'Left', 'tickera-event-ticketing-system' ); ?></option>
                <option value="right" <?php selected( isset( $this->template_metas[ $this->element_name . '_text_alignment' ] ) ? $this->template_metas[ $this->element_name . '_text_alignment' ] : 'left', 'right', true ); ?>><?php esc_html_e( 'Right', 'tickera-event-ticketing-system' ); ?></option>
                <option value="center" <?php selected( isset( $this->template_metas[ $this->element_name . '_text_alignment' ] ) ? $this->template_metas[ $this->element_name . '_text_alignment' ] : 'left', 'center', true ); ?>><?php esc_html_e( 'Center', 'tickera-event-ticketing-system' ); ?></option>
            </select>
            <?php
        }

        function get_cell_alignment() { ?>
            <label><?php esc_html_e( 'Cell Alignment', 'tickera-event-ticketing-system' ); ?></label>
            <select name="<?php echo esc_attr( $this->element_name ); ?>_cell_alignment_post_meta">
                <option value="left" <?php selected( isset( $this->template_metas[ $this->element_name . '_cell_alignment' ] ) ? $this->template_metas[ $this->element_name . '_cell_alignment' ] : 'left', 'left', true ); ?>><?php esc_html_e( 'Left', 'tickera-event-ticketing-system' ); ?></option>
                <option value="right" <?php selected( isset( $this->template_metas[ $this->element_name . '_cell_alignment' ] ) ? $this->template_metas[ $this->element_name . '_cell_alignment' ] : 'left', 'right', true ); ?>><?php esc_html_e( 'Right', 'tickera-event-ticketing-system' ); ?></option>
                <option value="center" <?php selected( isset( $this->template_metas[ $this->element_name . '_cell_alignment' ] ) ? $this->template_metas[ $this->element_name . '_cell_alignment' ] : 'left', 'center', true ); ?>><?php esc_html_e( 'Center', 'tickera-event-ticketing-system' ); ?></option>
            </select>
            <?php
        }

        function get_element_margins() { ?>
            <label><?php esc_html_e( 'Element Break Lines', 'tickera-event-ticketing-system' ); ?></label>
            <?php esc_html_e( 'Top', 'tickera-event-ticketing-system' ); ?> <input class="ticket_element_padding" type="text" name="<?php echo esc_attr( $this->element_name ); ?>_top_padding_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ $this->element_name . '_top_padding' ] ) ? $this->template_metas[ $this->element_name . '_top_padding' ] : 1 ); ?>"/>
            <?php esc_html_e( 'Bottom', 'tickera-event-ticketing-system' ); ?> <input class="ticket_element_padding" type="text" name="<?php echo esc_attr( $this->element_name ); ?>_bottom_padding_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ $this->element_name . '_bottom_padding' ] ) ? $this->template_metas[ $this->element_name . '_bottom_padding' ] : 1 ); ?>"/>
            </p>
            <?php
        }

        function get_font_style() { ?>
            <label><?php esc_html_e( 'Font Style', 'tickera-event-ticketing-system' ); ?></label>
            <select name="<?php echo esc_attr( $this->element_name ); ?>_font_style_post_meta" class="tc_att_font_style">
                <?php $element_font_style = isset( $this->template_metas[ $this->element_name . '_font_style' ] ) ? $this->template_metas[ $this->element_name . '_font_style' ] : ''; ?>
                <option value="" <?php selected( $element_font_style, '', true ); ?>><?php esc_html_e( 'Regular', 'tickera-event-ticketing-system' ); ?></option>
                <option value="B" <?php selected( $element_font_style, 'B', true ); ?>><?php esc_html_e( 'Bold', 'tickera-event-ticketing-system' ); ?></option>
                <option value="BI" <?php selected( $element_font_style, 'BI', true ); ?>><?php esc_html_e( 'Bold + Italic', 'tickera-event-ticketing-system' ); ?></option>
                <option value="BU" <?php selected( $element_font_style, 'BU', true ); ?>><?php esc_html_e( 'Bold + Underline', 'tickera-event-ticketing-system' ); ?></option>
                <option value="BIU" <?php selected( $element_font_style, 'BIU', true ); ?>><?php esc_html_e( 'Bold + Underline + Italic', 'tickera-event-ticketing-system' ); ?></option>
                <option value="I" <?php selected( $element_font_style, 'I', true ); ?>><?php esc_html_e( 'Italic', 'tickera-event-ticketing-system' ); ?></option>
                <option value="IU" <?php selected( $element_font_style, 'IU', true ); ?>><?php esc_html_e( 'Italic + Underline', 'tickera-event-ticketing-system' ); ?></option>
                <option value="U" <?php selected( $element_font_style, 'U', true ); ?>><?php esc_html_e( 'Underline', 'tickera-event-ticketing-system' ); ?></option>
            </select>
            <?php
        }

        function get_colors( $label = 'Color', $field_name = 'color', $default_color = '#000000' ) { ?>
            <label><?php echo esc_html( $label ); ?></label>
            <input type="text" class="tc-color-picker" name="<?php echo esc_attr( $this->element_name ); ?>_<?php echo esc_attr( $field_name ); ?>_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ $this->element_name . '_' . $field_name ] ) ? $this->template_metas[ $this->element_name . '_' . $field_name ] : $default_color ); ?>"/>
            <?php
        }

        function get_font_colors( $label = 'Font Color', $field_name = 'font_color', $default_color = '#000000' ) { ?>
            <label><?php echo esc_html( $label ); ?></label>
            <input type="text" class="tc-color-picker" name="<?php echo esc_attr( $this->element_name ); ?>_<?php echo esc_attr( $field_name ); ?>_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ $this->element_name . '_' . $field_name ] ) ? $this->template_metas[ $this->element_name . '_' . $field_name ] : $default_color ); ?>"/>
            <?php
        }

        function get_att_fonts() { ?>
            <select name="<?php echo esc_attr( $this->element_name ); ?>_att_font_family_post_meta" class="tc_att_font_family">
                <?php $element_att_font_family = isset( $this->template_metas[ $this->element_name . '_att_font_family' ] ) ? $this->template_metas[ $this->element_name . '_att_font_family' ] : ''; ?>
                <option value="Montserrat" <?php selected( $element_att_font_family, '', true ); ?>><?php esc_html_e( 'Montserrat', 'tickera-event-ticketing-system' ); ?></option>
                <option value="Oswald" <?php selected( $element_att_font_family, 'Oswald', true ); ?>><?php esc_html_e( 'Oswald', 'tickera-event-ticketing-system' ); ?></option>
                <option value="Indie Flower" <?php selected( $element_att_font_family, 'Indie Flower', true ); ?>><?php esc_html_e( 'Indie Flower', 'tickera-event-ticketing-system' ); ?></option>
                <option value="Faster One" <?php selected( $element_att_font_family, 'Faster One', true ); ?>><?php esc_html_e( 'Faster One', 'tickera-event-ticketing-system' ); ?></option>
            </select>
            <?php
        }

        function tcpdf_get_fonts( $prefix = 'document', $default_font = 'helvetica' ) { ?>
            <label><?php esc_html_e( 'Font', 'tickera-event-ticketing-system' ); ?></label>
            <select name="document_font_post_meta">
                <?php $template_prefix_font = isset( $this->template_metas[ $prefix . '_font' ] ) ? $this->template_metas[ $prefix . '_font' ] : $default_font; ?>
                <option value='aealarabiya' <?php selected( $template_prefix_font, 'aealarabiya', true ); ?>><?php esc_html_e( 'Al Arabiya', 'tickera-event-ticketing-system' ); ?></option>
                <option value='aefurat' <?php selected( $template_prefix_font, 'aefurat', true ); ?>><?php esc_html_e( 'Furat', 'tickera-event-ticketing-system' ); ?></option>
                <option value='cid0cs' <?php selected( $template_prefix_font, 'cid0cs', true ); ?>><?php esc_html_e( 'Arial Unicode MS (Simplified Chinese)', 'tickera-event-ticketing-system' ); ?></option>
                <option value='cid0jp' <?php selected( $template_prefix_font, 'cid0jp', true ); ?>><?php esc_html_e( 'Arial Unicode MS (Japanese)', 'tickera-event-ticketing-system' ); ?></option>
                <option value='cid0kr' <?php selected( $template_prefix_font, 'cid0kr', true ); ?>><?php esc_html_e( 'Arial Unicode MS (Korean)', 'tickera-event-ticketing-system' ); ?></option>
                <option value='courier' <?php selected( $template_prefix_font, 'courier', true ); ?>'><?php esc_html_e( 'Courier', 'tickera-event-ticketing-system' ); ?></option>
                <option value='dejavusans' <?php selected( $template_prefix_font, 'dejavusans', true ); ?>><?php esc_html_e( 'DejaVu Sans', 'tickera-event-ticketing-system' ); ?></option>
                <option value='dejavusanscondensed' <?php selected( $template_prefix_font, 'dejavusanscondensed', true ); ?>><?php esc_html_e( 'DejaVu Sans Condensed', 'tickera-event-ticketing-system' ); ?></option>
                <option value='dejavusansextralight' <?php selected( $template_prefix_font, 'dejavusansextralight', true ); ?>><?php esc_html_e( 'DejaVu Sans ExtraLight', 'tickera-event-ticketing-system' ); ?></option>
                <option value='dejavusansmono' <?php selected( $template_prefix_font, 'dejavusansmono', true ); ?>><?php esc_html_e( 'DejaVu Sans Mono', 'tickera-event-ticketing-system' ); ?></option>
                <option value='dejavuserif' <?php selected( $template_prefix_font, 'dejavuserif', true ); ?>><?php esc_html_e( 'DejaVu Serif', 'tickera-event-ticketing-system' ); ?></option>
                <option value='dejavuserifcondensed' <?php selected( $template_prefix_font, 'dejavuserifcondensed', true ); ?>><?php esc_html_e( 'DejaVu Serif Condensed', 'tickera-event-ticketing-system' ); ?></option>
                <option value='freemono' <?php selected( $template_prefix_font, 'freemono', true ); ?>><?php esc_html_e( 'FreeMono', 'tickera-event-ticketing-system' ); ?></option>
                <option value='freesans' <?php selected( $template_prefix_font, 'freesans', true ); ?>><?php esc_html_e( 'FreeSans', 'tickera-event-ticketing-system' ); ?></option>
                <option value='freeserif' <?php selected( $template_prefix_font, 'freeserif', true ); ?>><?php esc_html_e( 'FreeSerif', 'tickera-event-ticketing-system' ); ?></option>
                <option value='helvetica' <?php selected( $template_prefix_font, 'helvetica', true ); ?>><?php esc_html_e( 'Helvetica', 'tickera-event-ticketing-system' ); ?></option>
                <option value='hysmyeongjostdmedium' <?php selected( $template_prefix_font, 'hysmyeongjostdmedium', true ); ?>><?php esc_html_e( 'MyungJo Medium (Korean)', 'tickera-event-ticketing-system' ); ?></option>
                <option value='kozgopromedium' <?php selected( $template_prefix_font, 'kozgopromedium', true ); ?>><?php esc_html_e( 'Kozuka Gothic Pro (Japanese Sans-Serif)', 'tickera-event-ticketing-system' ); ?></option>
                <option value='kozminproregular' <?php selected( $template_prefix_font, 'kozminproregular', true ); ?>><?php esc_html_e( 'Kozuka Mincho Pro (Japanese Serif)', 'tickera-event-ticketing-system' ); ?></option>
                <option value='msungstdlight' <?php selected( $template_prefix_font, 'msungstdlight', true ); ?>><?php esc_html_e( 'MSung Light (Traditional Chinese)', 'tickera-event-ticketing-system' ); ?></option>
                <option value='pdfacourier' <?php selected( $template_prefix_font, 'pdfacourier', true ); ?>><?php esc_html_e( 'PDFA Courier', 'tickera-event-ticketing-system' ); ?></option>
                <option value='pdfahelvetica' <?php selected( $template_prefix_font, 'pdfahelvetica', true ); ?>><?php esc_html_e( 'PDFA Helvetica', 'tickera-event-ticketing-system' ); ?></option>
                <option value='pdfatimes' <?php selected( $template_prefix_font, 'pdfatimes', true ); ?>><?php esc_html_e( 'PDFA Times', 'tickera-event-ticketing-system' ); ?></option>
                <option value='stsongstdlight' <?php selected( $template_prefix_font, 'stsongstdlight', true ); ?>><?php esc_html_e( 'STSong Light (Simplified Chinese)', 'tickera-event-ticketing-system' ); ?></option>
                <option value='symbol' <?php selected( $template_prefix_font, 'symbol', true ); ?>><?php esc_html_e( 'Symbol', 'tickera-event-ticketing-system' ); ?></option>
                <option value='times' <?php selected( $template_prefix_font, 'times', true ); ?>><?php esc_html_e( 'Times-Roman', 'tickera-event-ticketing-system' ); ?></option>
                <option value='thsarabun' <?php selected( $template_prefix_font, 'thsarabun', true ); ?>><?php esc_html_e( 'Sarabun (Thai)', 'tickera-event-ticketing-system' ); ?></option>
                <?php do_action( 'tc_ticket_font', isset( $this->template_metas[ $prefix . '_font' ] ) ? $this->template_metas[ $prefix . '_font' ] : '', $default_font ); ?>
            </select>
            <?php
        }

        function get_font_sizes( $box_title = false, $default_font_size = false ) { ?>
            <label><?php
                if ( $box_title ) {
                    echo esc_html( $box_title );

                } else {
                    esc_html_e( 'Font Size', 'tickera-event-ticketing-system' );
                }
                ?>
            </label>
            <select name="<?php echo esc_attr( $this->element_name ); ?>_font_size_post_meta" class="tc_att_font_size">
                <?php
                for ( $i = 6; $i <= 100; $i++ ) { ?>
                    <option value='<?php echo esc_attr( $i ); ?>' <?php selected( isset( $this->template_metas[ $this->element_name . '_font_size' ] ) ? $this->template_metas[ $this->element_name . '_font_size' ] : ( $default_font_size ? $default_font_size : 14 ), $i, true ); ?>><?php echo esc_html( $i ); ?> pt</option>
                    <?php
                }
                ?>
            </select>
            <?php
        }

        function get_default_text_value( $text ) { ?>
            <div class="tc_att_default_text_value default_text_value"><?php echo esc_html( $text ); ?></div>
            <?php
        }
    }
}

/**
 * Deprecated function "tc_register_template_element".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_register_template_element' ) ) {

    function tickera_register_template_element( $class_name, $element_title ) {

        global $tc_template_elements;

        if ( ! is_array( $tc_template_elements ) ) {
            $tc_template_elements = array();
        }

        if ( class_exists( $class_name ) ) {
            $tc_template_elements[] = array( $class_name, $element_title );

        } else {
            return false;
        }
    }
}
