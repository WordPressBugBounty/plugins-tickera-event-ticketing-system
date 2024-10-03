<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $tc;
?>
<div class="tc-wiz-wrapper">
    <div class="tc-wiz-screen-wrap tc-installation-settings-page <?php echo esc_attr( tickera_wizard_wrapper_class() ); ?>">
        <h1><?php echo esc_html( $tc->title ); ?></h1>
        <?php tickera_wizard_progress(); ?>
        <div class="tc-clear"></div>
        <div class="tc-wiz-screen">
            <div class="tc-wiz-screen-header">
                <h2>Settings</h2>
            </div><!-- .tc-wiz-screen-header -->
            <div class="tc-wiz-screen-content">
                <p><?php echo esc_html( sprintf( /* translators: %s: Tickera */ __( 'Set some crucial settings for your event ticketing store bellow. All the setting could be changed later from within your %s Settings panel.', 'tickera-event-ticketing-system' ), esc_html( $tc->title ) ) ); ?></p>
                <?php
                $tc_general_settings = get_option( 'tickera_general_setting', false );
                $settings = get_option( 'tickera_settings' );
                $currencies = $settings[ 'gateways' ][ 'currencies' ];
                ksort( $currencies );
                $checked = ( isset( $tc_general_settings[ 'currencies' ] ) ) ? $tc_general_settings[ 'currencies' ] : 'USD';
                ?>
                <div class="tc-setting-wrap">
                    <div class="tc-setting-label"><label for="tc_select_currency"><?php esc_html_e( 'Currency', 'tickera-event-ticketing-system' ); ?></label></div>
                    <div class="tc-setting-field">
                        <select id="tc_select_currency" name="tc_select_currency" class="tc_select_currency">
                            <?php foreach ( $currencies as $currency_symbol => $title ) : ?>
                                <option value="<?php echo esc_attr( $currency_symbol ); ?>" <?php selected( $checked, $currency_symbol, true ); ?>><?php echo esc_html( $title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div><!-- .tc-setting-field -->
                </div><!-- .tc-setting-wrap -->
                <div class="tc-setting-wrap">
                    <div class="tc-setting-label"><label for="tc_select_currency_symbol"><?php esc_html_e( 'Currency Symbol', 'tickera-event-ticketing-system' ); ?></label></div>
                    <div class="tc-setting-field">
                        <input type="text" id="tc_select_currency_symbol" name="currency_symbol" class="tc_currency_symbol" value="<?php echo esc_attr( isset( $tc_general_settings[ 'currency_symbol' ] ) ? $tc_general_settings[ 'currency_symbol' ] : '$' ) ?>"/>
                    </div><!-- .tc-setting-field -->
                </div><!-- .tc-setting-wrap -->
                <div class="tc-setting-wrap">
                    <div class="tc-setting-label"><label for="tc_select_currency_position"><?php esc_html_e( 'Currency Position', 'tickera-event-ticketing-system' ); ?></label></div>
                    <div class="tc-setting-field">
                        <?php
                        $checked = ( isset( $tc_general_settings[ 'currency_position' ] ) ) ? $tc_general_settings[ 'currency_position' ] : 'pre_nospace';
                        $symbol = ( isset( $tc_general_settings[ 'currency_symbol' ] ) && $tc_general_settings[ 'currency_symbol' ] != '' ? $tc_general_settings[ 'currency_symbol' ] : ( isset( $tc_general_settings[ 'currencies' ] ) ? $tc_general_settings[ 'currencies' ] : '$' ) );
                        ?>
                        <select name="currency_position" class="tc_currency_position">
                            <option value="pre_space" <?php selected( $checked, 'pre_space', true ); ?>><?php echo esc_html( $symbol . ' 10' ); ?></option>
                            <option value="pre_nospace" <?php selected( $checked, 'pre_nospace', true ); ?>><?php echo esc_html( $symbol . '10' ); ?></option>
                            <option value="post_nospace" <?php selected( $checked, 'post_nospace', true ); ?>><?php echo esc_html( '10' . $symbol ); ?></option>
                            <option value="post_space" <?php selected( $checked, 'post_space', true ); ?>><?php echo esc_html( '10 ' . $symbol ); ?></option>
                            <?php do_action( 'tc_currencies_position' ); ?>
                        </select>
                    </div><!-- .tc-setting-field -->
                </div><!-- .tc-setting-wrap -->
                <div class="tc-setting-wrap">
                    <div class="tc-setting-label"><label for="tc_select_currency_position"><?php esc_html_e( 'Price Format', 'tickera-event-ticketing-system' ); ?></label></div>
                    <div class="tc-setting-field">
                        <?php $checked = ( isset( $tc_general_settings[ 'price_format' ] ) ) ? $tc_general_settings[ 'price_format' ] : 'us'; ?>
                        <select name="price_format" class="tc_price_format">
                            <option value="us" <?php selected( $checked, 'us', true ); ?>><?php esc_html_e( '1,234.56', 'tickera-event-ticketing-system' ); ?></option>
                            <option value="eu" <?php selected( $checked, 'eu', true ); ?>><?php esc_html_e( '1.234,56', 'tickera-event-ticketing-system' ); ?></option>
                            <option value="french_comma" <?php selected( $checked, 'french_comma', true ); ?>><?php esc_html_e( '1 234,56', 'tickera-event-ticketing-system' ); ?></option>
                            <option value="french_dot" <?php selected( $checked, 'french_dot', true ); ?>><?php esc_html_e( '1 234.56', 'tickera-event-ticketing-system' ); ?></option>
                            <?php do_action( 'tc_price_formats' ); ?>
                        </select>
                    </div><!--.tc-setting-field -->
                </div><!--.tc-setting-wrap -->
                <?php $checked_show_tax_rate = ( isset( $tc_general_settings[ 'show_tax_rate' ] ) ) ? $tc_general_settings[ 'show_tax_rate' ] : 'no'; ?>
                <div class="tc-setting-wrap">
                    <div class="tc-setting-label"><label for="use_taxes"><?php esc_html_e( 'Use Taxes', 'tickera-event-ticketing-system' ); ?></label></div>
                    <div class="tc-setting-field">
                        <label>
                            <input type="radio" class="tc_show_tax_rate" name="show_tax_rate" value="yes" checked="checked" <?php checked( $checked_show_tax_rate, 'yes', true ); ?>><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ); ?>
                        </label>
                        <label>
                            <input type="radio" class="tc_show_tax_rate" name="show_tax_rate" value="no" <?php checked( $checked_show_tax_rate, 'no', true ); ?>><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ); ?>
                        </label>
                    </div><!--.tc-setting-field -->
                </div><!--.tc-setting-wrap -->
                <?php $show_taxes_fields = ( 'no' == $checked_show_tax_rate ) ? ' style="display:none"' : ''; ?>
                <div class="tc-setting-wrap tc-taxes-fields-wrap"<?php echo esc_html($show_taxes_fields); ?>>
                    <div class="tc-setting-label"><?php esc_html_e( 'Tax Rate (%)', 'tickera-event-ticketing-system' ); ?></div>
                    <div class="tc-setting-field">
                        <input type="text" class="tc_tax_rate" id="tax_rate" name="show_tax_rate" value="<?php echo esc_attr( isset( $tc_general_settings[ 'tax_rate' ] ) ? $tc_general_settings[ 'tax_rate' ] : 0 ); ?>">
                    </div><!--.tc-setting-field -->
                </div><!--.tc-setting-wrap -->
                <?php $checked = ( isset( $tc_general_settings[ 'tax_inclusive' ] ) ) ? $tc_general_settings[ 'tax_inclusive' ] : 'no'; ?>
                <div class="tc-setting-wrap tc-taxes-fields-wrap"<?php echo esc_html($show_taxes_fields); ?>>
                    <div class="tc-setting-label"><?php esc_html_e( 'Prices inclusive of tax?', 'tickera-event-ticketing-system' ); ?></div>
                    <div class="tc-setting-field">
                        <label>
                            <input type="radio" class="tc_tax_inclusive" name="tax_inclusive" value="yes" <?php checked( $checked, 'yes', true ); ?>><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ); ?>
                        </label>
                        <label>
                            <input type="radio" class="tc_tax_inclusive" name="tax_inclusive" value="no" <?php checked( $checked, 'no', true ); ?>><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ); ?>
                        </label>
                    </div><!--.tc-setting-field -->
                </div><!--.tc-setting-wrap -->
                <div class="tc-setting-wrap tc-taxes-fields-wrap"<?php echo esc_html($show_taxes_fields); ?>>
                    <div class="tc-setting-label"><?php esc_html_e( 'Tax Label', 'tickera-event-ticketing-system' ); ?></div>
                    <div class="tc-setting-field">
                        <input type="text" class="tc_tax_label" id="tax_label" name="tax_label" value="<?php echo esc_attr( isset( $tc_general_settings[ 'tax_label' ] ) ? $tc_general_settings[ 'tax_label' ] : 'Tax' ); ?>"/>
                    </div><!--.tc-setting-field -->
                </div><!--.tc-setting-wrap -->
                <?php tickera_wizard_navigation(); ?>
                <div class="tc-clear"></div>
            </div><!-- .tc-wiz-screen-content -->
        </div><!-- tc-wiz-screen -->
    </div><!-- .tc-wiz-screen-wrap -->
</div><!-- .tc-wiz-wrapper -->
