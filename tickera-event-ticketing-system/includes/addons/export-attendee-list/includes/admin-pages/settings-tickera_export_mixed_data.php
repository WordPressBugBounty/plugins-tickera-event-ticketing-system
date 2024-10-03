<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  ?>
<div class="wrap tc_wrap">
    <div id="poststuff" class="metabox-holder tc-settings">
        <form action="" method="post" enctype="multipart/form-data">
            <div id="store_settings" class="postbox">
                <h3><span><?php esc_html_e( 'PDF Export', 'tickera-event-ticketing-system' ); ?></span>
                    <span class="description"><?php esc_html_e( 'Export per-event based attendee lists in PDF file format', 'tickera-event-ticketing-system' ); ?></span>
                </h3>
                <div class="inside">
                    <table class="form-table">
                        <tbody>
                        <tr valign="top">
                            <th scope="row"><label for="tc_export_event_data"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></label></th>
                            <td>
                                <?php $wp_events_search = new \Tickera\TC_Events_Search( '', '', -1 ); ?>
                                <select name="tc_export_event_data">
                                    <?php
                                    foreach ( $wp_events_search->get_results() as $event ) {
                                        $event_obj = new \Tickera\TC_Event( $event->ID );
                                        $event_object = $event_obj->details;
                                        ?>
                                        <option value="<?php echo esc_attr( (int) $event_object->ID ); ?>"><?php echo esc_html( $event_object->post_title ); ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="attendee_export_field"><?php esc_html_e( 'Export columns', 'tickera-event-ticketing-system' ); ?></label></th>
                            <td>
                                <fieldset>
                                    <label for="col_checkbox" class="tc_checkboxes_label">
                                        <input type="checkbox" id="col_checkbox" name="col_checkbox">
                                        <?php esc_html_e( 'Check field (useful for manually check-ins)', 'tickera-event-ticketing-system' ); ?>
                                    </label>

                                    <label for="col_owner_name" class="tc_checkboxes_label">
                                        <input type="checkbox" id="col_owner_name" name="col_owner_name" checked="checked">
                                        <?php esc_html_e( 'Attendee full name', 'tickera-event-ticketing-system' ); ?>
                                    </label>

                                    <label for="col_payment_date" class="tc_checkboxes_label">
                                        <input type="checkbox" id="col_payment_date" name="col_payment_date" checked="checked">
                                        <?php esc_html_e( 'Payment date', 'tickera-event-ticketing-system' ); ?>
                                    </label>

                                    <label for="col_ticket_id" class="tc_checkboxes_label">
                                        <input type="checkbox" id="col_ticket_id" name="col_ticket_id" checked="checked">
                                        <?php esc_html_e( 'Ticket ID', 'tickera-event-ticketing-system' ); ?>
                                    </label>


                                    <label for="col_ticket_type" class="tc_checkboxes_label">
                                        <input type="checkbox" id="col_ticket_type" name="col_ticket_type" checked="checked">
                                        <?php esc_html_e( 'Ticket type', 'tickera-event-ticketing-system' ); ?>
                                    </label>

                                    <label for="col_buyer_name" class="tc_checkboxes_label">
                                        <input type="checkbox" name="col_buyer_name" id="col_buyer_name" checked="checked">
                                        <?php esc_html_e( 'Buyer full name', 'tickera-event-ticketing-system' ); ?>
                                    </label>

                                    <label for="col_buyer_email" class="tc_checkboxes_label">
                                        <input type="checkbox" name="col_buyer_email" id="col_buyer_email" checked="checked">
                                        <?php esc_html_e( 'Buyer email address', 'tickera-event-ticketing-system' ); ?>
                                    </label>

                                    <label for="col_checked_in" class="tc_checkboxes_label">
                                        <input type="checkbox" name="col_checked_in" id="col_checked_in" checked="checked">
                                        <?php esc_html_e( 'Checked-in', 'tickera-event-ticketing-system' ); ?>
                                    </label>

                                    <label for="col_checkins" class="tc_checkboxes_label">
                                        <input type="checkbox" name="col_checkins" id="col_checkins" checked="checked">
                                        <?php esc_html_e( 'Check-ins (list of all the check-ins)', 'tickera-event-ticketing-system' ); ?>
                                    </label>
                                    <?php do_action( 'tc_pdf_admin_columns' ); ?>
                                </fieldset>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="document_font"><?php esc_html_e( 'Document font', 'tickera-event-ticketing-system' ); ?></label>
                            </th>
                            <td>
                                <label>
                                    <select name="document_font">
                                        <option value='aealarabiya'><?php esc_html_e( 'Al Arabiya', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='aefurat'><?php esc_html_e( 'Furat', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='cid0cs'><?php esc_html_e( 'Arial Unicode MS (Simplified Chinese)', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='cid0jp'><?php esc_html_e( 'Arial Unicode MS (Japanese)', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='cid0kr'><?php esc_html_e( 'Arial Unicode MS (Korean)', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='courier'><?php esc_html_e( 'Courier', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='dejavusans'><?php esc_html_e( 'DejaVu Sans', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='dejavusanscondensed'><?php esc_html_e( 'DejaVu Sans Condensed', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='dejavusansextralight'><?php esc_html_e( 'DejaVu Sans ExtraLight', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='dejavusansmono'><?php esc_html_e( 'DejaVu Sans Mono', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='dejavuserif'><?php esc_html_e( 'DejaVu Serif', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='dejavuserifcondensed'><?php esc_html_e( 'DejaVu Serif Condensed', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='freemono'><?php esc_html_e( 'FreeMono', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='freesans'><?php esc_html_e( 'FreeSans', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='freeserif'><?php esc_html_e( 'FreeSerif', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='helvetica' selected=""><?php esc_html_e( 'Helvetica', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='hysmyeongjostdmedium'><?php esc_html_e( 'MyungJo Medium (Korean)', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='kozgopromedium'><?php esc_html_e( 'Kozuka Gothic Pro (Japanese Sans-Serif)', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='kozminproregular'><?php esc_html_e( 'Kozuka Mincho Pro (Japanese Serif)', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='msungstdlight'><?php esc_html_e( 'MSung Light (Traditional Chinese)', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='pdfacourier'><?php esc_html_e( 'PDFA Courier', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='pdfahelvetica'><?php esc_html_e( 'PDFA Helvetica', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='pdfatimes'><?php esc_html_e( 'PDFA Times', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='stsongstdlight'><?php esc_html_e( 'STSong Light (Simplified Chinese)', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='symbol'><?php esc_html_e( 'Symbol', 'tickera-event-ticketing-system' ); ?></option>
                                        <option value='times'><?php esc_html_e( 'Times-Roman', 'tickera-event-ticketing-system' ); ?></option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="document_font_size"><?php esc_html_e( 'Document font size', 'tickera-event-ticketing-system' ); ?></label></th>
                            <td>
                                <select name="document_font_size">
                                    <?php
                                    $i = 0;
                                    for ( $i = 8; $i <= 40; $i++ ) { ?>
                                        <option value="<?php echo esc_attr( $i ); ?>" <?php
                                        if ( $i == 14 ) {
                                            echo esc_attr( 'selected' );
                                        }
                                        ?>><?php echo esc_html( $i ); ?></option>
                                    <?php }  ?>
                                </select>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="document_orientation"><?php esc_html_e( 'Document orientation', 'tickera-event-ticketing-system' ); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="radio" name="document_orientation" value="L" checked="checked"><?php esc_html_e( 'Landscape', 'tickera-event-ticketing-system' ); ?>
                                </label>
                                <label>
                                    <input type="radio" name="document_orientation" value="P"><?php esc_html_e( 'Portrait', 'tickera-event-ticketing-system' ); ?>
                                </label>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><label for="document_size"><?php esc_html_e( 'Document size', 'tickera-event-ticketing-system' ); ?></label>
                            </th>
                            <td>
                                <select name="document_size">
                                    <option value="A3"><?php esc_html_e( 'A3 (297 × 420 mm)', 'tickera-event-ticketing-system' ); ?></option>
                                    <option value="A4" selected="selected"><?php esc_html_e( 'A4 (210 × 297)', 'tickera-event-ticketing-system' ); ?></option>
                                    <option value="A5"><?php esc_html_e( 'A5 (148 × 210)', 'tickera-event-ticketing-system' ); ?></option>
                                    <option value="A6"><?php esc_html_e( 'A6 (105 × 148)', 'tickera-event-ticketing-system' ); ?></option>
                                    <option value="ANSI_A"><?php esc_html_e( 'ANSI A (216x279 mm)', 'tickera-event-ticketing-system' ); ?></option>
                                </select>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><label for="document_title"><?php esc_html_e( 'Document title', 'tickera-event-ticketing-system' ); ?></label>
                            </th>
                            <td>
                                <input type="text" name='document_title' value='<?php esc_attr_e( 'Attendee List', 'tickera-event-ticketing-system' ); ?>'/>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tc-progress-bar tc-hidden"></div>
            <input type="submit" name="export_event_data" id="export_event_data" class="button button-primary" value="Export Data">
        </form>
    </div>
</div><?php
