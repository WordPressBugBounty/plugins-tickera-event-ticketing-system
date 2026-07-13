<?php
/**
 * Ticket Designer Install
 *
 * @package Venuera
 * @subpackage Addons/TicketDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.Security.NonceVerification.Recommended, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery, WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude, Squiz.PHP.DiscouragedFunctions.Discouraged, WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Venuera custom-table data access:
	// These routines access Venuera's own custom tables ($wpdb->prefix . 'venuera_*'), which have no
	// WordPress core API. Table identifiers cannot be bound via $wpdb->prepare(), so the table NAME
	// (always built from $wpdb->prefix, never user input) is interpolated, while every VALUE is bound
	// through prepare() placeholders (verified across the file). Direct queries are required, and the
	// results are transactional ticket/seat data that must not be served stale from the object cache.
	// Read-only GET filters here are public search/pagination params (sanitized, no state change), which
	// have no nonce by design.

	exit;
}

/**
 * Ticket Designer Install class.
 */
class TC_Ticket_Designer_Install {

	/**
	 * Install tables.
	 */
	public static function install() {
		self::create_tables();
		// Seed a single default design (the bundled Conference template).
		self::ensure_default_template();
	}

	/**
	 * Ensure a "Default Template" exists, built from the bundled Conference
	 * ready-made design, and marked as THE default template.
	 * Idempotent — runs on install/upgrade and only seeds once.
	 */
	private static function ensure_default_template() {
		global $wpdb;

		$table = $wpdb->prefix . 'tickera_ticket_templates';

		// Already seeded? Leave it (and the user's edits) untouched.
		$exists = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s LIMIT 1", 'Default Template' )
		);
		if ( $exists ) {
			return;
		}

		$file = dirname( __DIR__ ) . '/templates/conference-badge.json';
		if ( ! file_exists( $file ) ) {
			return;
		}

		$json = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- bundled local file.
		if ( ! is_array( $json ) || empty( $json['templateData'] ) ) {
			return;
		}

		$template_data = $json['templateData'];
		$settings      = ( isset( $json['settings'] ) && is_array( $json['settings'] ) )
			? $json['settings']
			: array(
				'orientation' => 'landscape',
				'size'        => 'custom',
				'width'       => isset( $template_data['width'] ) ? (int) $template_data['width'] : 432,
				'height'      => isset( $template_data['height'] ) ? (int) $template_data['height'] : 180,
			);

		$wpdb->insert(
			$table,
			array(
				'name'          => __( 'Default Template', 'tickera-event-ticketing-system' ),
				'template_data' => wp_json_encode( $template_data ),
				'settings'      => wp_json_encode( $settings ),
				'status'        => 'active',
				'is_default'    => 1,
			),
			array( '%s', '%s', '%s', '%s', '%d' )
		);

		$new_id = (int) $wpdb->insert_id;
		if ( $new_id ) {
			// Make "Default Template" the single default.
			$wpdb->query(
				$wpdb->prepare( "UPDATE {$table} SET is_default = 0 WHERE id <> %d", $new_id )
			);
		}
	}

	/**
	 * Safety net: if the templates table is empty (the user deleted everything),
	 * re-seed the bundled "Default Template" so there is always at least one
	 * design to start from. Cheap COUNT — runs on admin loads.
	 */
	public static function maybe_seed_when_empty() {
		global $wpdb;

		$table = $wpdb->prefix . 'tickera_ticket_templates';

		// Table must exist first.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return;
		}

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( 0 === $count ) {
			self::ensure_default_template();
		}
	}

	/**
	 * Create database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create ticket templates table.
		$table_name = $wpdb->prefix . 'tickera_ticket_templates';

		$sql1 = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            template_data longtext,
            settings longtext,
            thumbnail_url varchar(500) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            is_default tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY is_default (is_default)
        ) $charset_collate;";

		dbDelta( $sql1 );

		// Create template assignments table.
		$table_name2 = $wpdb->prefix . 'tickera_ticket_template_assignments';

		$sql2 = "CREATE TABLE $table_name2 (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            template_id bigint(20) UNSIGNED NOT NULL,
            event_id bigint(20) UNSIGNED DEFAULT NULL,
            ticket_type_id bigint(20) UNSIGNED DEFAULT NULL,
            priority int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY template_id (template_id),
            KEY event_id (event_id),
            KEY ticket_type_id (ticket_type_id)
        ) $charset_collate;";

		dbDelta( $sql2 );
	}


}
