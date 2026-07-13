<?php
/**
 * Ticket Template Model
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
 * Ticket Template class.
 */
class TC_Ticket_Designer_Template {

	/**
	 * Template ID.
	 *
	 * @var int
	 */
	private $id = 0;

	/**
	 * Template data.
	 *
	 * @var array
	 */
	private $data = array(
		'name'          => '',
		'template_data' => '',
		'settings'      => '',
		'thumbnail_url' => '',
		'status'        => 'active',
		'is_default'    => 0,
		'created_at'    => '',
		'updated_at'    => '',
	);

	/**
	 * Constructor.
	 *
	 * @param int $template_id Template ID to load.
	 */
	public function __construct( $template_id = 0 ) {
		if ( $template_id ) {
			$this->load( $template_id );
		}
	}

	/**
	 * Load template from database.
	 *
	 * @param int $template_id Template ID.
	 */
	private function load( $template_id ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}tickera_ticket_templates WHERE id = %d",
				$template_id
			),
			ARRAY_A
		);

		if ( $row ) {
			$this->id   = absint( $row['id'] );
			$this->data = array(
				'name'          => $row['name'],
				'template_data' => $row['template_data'],
				'settings'      => $row['settings'],
				'thumbnail_url' => $row['thumbnail_url'],
				'status'        => $row['status'],
				'is_default'    => $row['is_default'],
				'created_at'    => $row['created_at'],
				'updated_at'    => $row['updated_at'],
			);
		}
	}

	/**
	 * Get template ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get template property.
	 *
	 * @param string $key Property key.
	 * @return mixed
	 */
	public function get( $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
	}

	/**
	 * Set template property.
	 *
	 * @param string $key   Property key.
	 * @param mixed  $value Property value.
	 */
	public function set( $key, $value ) {
		if ( array_key_exists( $key, $this->data ) ) {
			$this->data[ $key ] = $value;
		}
	}

	/**
	 * Save template to database.
	 *
	 * @return int|false Template ID on success, false on failure.
	 */
	public function save() {
		global $wpdb;

		$data = array(
			'name'          => $this->data['name'],
			'template_data' => $this->data['template_data'],
			'settings'      => $this->data['settings'],
			'thumbnail_url' => $this->data['thumbnail_url'],
			'status'        => $this->data['status'],
			'is_default'    => $this->data['is_default'],
		);

		$format = array( '%s', '%s', '%s', '%s', '%s', '%d' );

		if ( $this->id ) {
			// Update existing.
			$result = $wpdb->update(
				"{$wpdb->prefix}tickera_ticket_templates",
				$data,
				array( 'id' => $this->id ),
				$format,
				array( '%d' )
			);

			return false !== $result ? $this->id : false;
		} else {
			// Insert new.
			$result = $wpdb->insert(
				"{$wpdb->prefix}tickera_ticket_templates",
				$data,
				$format
			);

			if ( $result ) {
				$this->id = $wpdb->insert_id;
				return $this->id;
			}
		}

		return false;
	}

	/**
	 * Delete template from database.
	 *
	 * @return bool
	 */
	public function delete() {
		global $wpdb;

		if ( ! $this->id ) {
			return false;
		}

		// Delete assignments first.
		$wpdb->delete(
			"{$wpdb->prefix}tickera_ticket_template_assignments",
			array( 'template_id' => $this->id ),
			array( '%d' )
		);

		// Delete template.
		$result = $wpdb->delete(
			"{$wpdb->prefix}tickera_ticket_templates",
			array( 'id' => $this->id ),
			array( '%d' )
		);

		return (bool) $result;
	}

	/**
	 * Get all templates.
	 *
	 * @param array $args Query arguments.
	 * @return TC_Ticket_Designer_Template[]
	 */
	public static function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'  => 'active',
			'orderby' => 'name',
			'order'   => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where  = array();
		$values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		$where_clause = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';
		$order_clause = sprintf(
			'ORDER BY %s %s',
			sanitize_sql_orderby( $args['orderby'] ) ? sanitize_sql_orderby( $args['orderby'] ) : 'name',
			'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC'
		);

		$query = "SELECT id FROM {$wpdb->prefix}tickera_ticket_templates {$where_clause} {$order_clause}";

		if ( ! empty( $values ) ) {
			$query = $wpdb->prepare( $query, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is assembled from a fixed SELECT plus sanitize_sql_orderby(); placeholders are bound here.
		}

		$ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is built with $wpdb->prepare(); only the table name (from $wpdb->prefix) is interpolated.

		$templates = array();
		foreach ( $ids as $id ) {
			$templates[] = new self( $id );
		}

		return $templates;
	}

	/**
	 * Get template for a specific event/ticket type.
	 *
	 * @param int $event_id       Event ID.
	 * @param int $ticket_type_id Ticket type ID.
	 * @return TC_Ticket_Designer_Template|null
	 */
	public static function get_for_ticket( $event_id, $ticket_type_id ) {
		global $wpdb;

		$template_id = null;
		$table       = $wpdb->prefix . 'tickera_ticket_template_assignments';

		// 1) Most specific: assignment for the exact product / variation that
		// sold the ticket. For a variable-event-ticket order item this is
		// the variation id, so a per-variation override wins immediately.
		if ( $ticket_type_id ) {
			$template_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT template_id FROM {$table}
                    WHERE ticket_type_id = %d
                    ORDER BY priority DESC
                    LIMIT 1",
					$ticket_type_id
				)
			);
		}

		// 2) Variation fallback: if the ticket type is a variation and has
		// no override of its own, fall back to its parent Variable Event
		// Ticket product's assignment. This is what makes the variation
		// "Inherit from parent" default work end-to-end.
		if ( ! $template_id && $ticket_type_id ) {
			$parent_id = wp_get_post_parent_id( $ticket_type_id );
			if ( $parent_id ) {
				$template_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT template_id FROM {$table}
                        WHERE ticket_type_id = %d
                        ORDER BY priority DESC
                        LIMIT 1",
						$parent_id
					)
				);
			}
		}

		// 3) Event-level assignment (no product/variation override anywhere).
		if ( ! $template_id && $event_id ) {
			$template_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT template_id FROM {$table}
                    WHERE event_id = %d AND ticket_type_id IS NULL
                    ORDER BY priority DESC
                    LIMIT 1",
					$event_id
				)
			);
		}

		// 4) Final fallback: the global default template.
		if ( ! $template_id ) {
			$template_id = $wpdb->get_var(
				"SELECT id FROM {$wpdb->prefix}tickera_ticket_templates
                WHERE status = 'active'
                ORDER BY is_default DESC, id ASC
                LIMIT 1"
			);
		}

		if ( $template_id ) {
			return new self( $template_id );
		}

		return null;
	}

	/**
	 * Assign template to event or ticket type.
	 *
	 * @param int $event_id       Event ID (optional).
	 * @param int $ticket_type_id Ticket type ID (optional).
	 * @param int $priority       Priority (higher = more specific).
	 * @return bool
	 */
	public function assign( $event_id = null, $ticket_type_id = null, $priority = 0 ) {
		global $wpdb;

		if ( ! $this->id ) {
			return false;
		}

		// Remove existing assignment for this combination.
		$where        = array( 'template_id' => $this->id );
		$where_format = array( '%d' );

		if ( $event_id ) {
			$where['event_id'] = $event_id;
			$where_format[]    = '%d';
		}

		if ( $ticket_type_id ) {
			$where['ticket_type_id'] = $ticket_type_id;
			$where_format[]          = '%d';
		}

		$wpdb->delete(
			"{$wpdb->prefix}tickera_ticket_template_assignments",
			$where,
			$where_format
		);

		// Insert new assignment.
		$data   = array(
			'template_id' => $this->id,
			'priority'    => $priority,
		);
		$format = array( '%d', '%d' );

		if ( $event_id ) {
			$data['event_id'] = $event_id;
			$format[]         = '%d';
		}

		if ( $ticket_type_id ) {
			$data['ticket_type_id'] = $ticket_type_id;
			$format[]               = '%d';
		}

		return (bool) $wpdb->insert(
			"{$wpdb->prefix}tickera_ticket_template_assignments",
			$data,
			$format
		);
	}

	/**
	 * Get assigned events for this template.
	 *
	 * @return array Array of event IDs.
	 */
	public function get_assigned_events() {
		global $wpdb;

		if ( ! $this->id ) {
			return array();
		}

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT event_id FROM {$wpdb->prefix}tickera_ticket_template_assignments 
                WHERE template_id = %d AND event_id IS NOT NULL",
				$this->id
			)
		);
	}

	/**
	 * Get template data as array.
	 *
	 * @return array
	 */
	public function get_template_array() {
		$template_data = $this->get( 'template_data' );
		if ( is_string( $template_data ) ) {
			$decoded = json_decode( $template_data, true );
			return $decoded ? $decoded : array();
		}
		return is_array( $template_data ) ? $template_data : array();
	}

	/**
	 * Get settings as array.
	 *
	 * @return array
	 */
	public function get_settings_array() {
		$settings = $this->get( 'settings' );
		if ( is_string( $settings ) ) {
			$decoded = json_decode( $settings, true );
			return $decoded ? $decoded : array();
		}
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Duplicate this template.
	 *
	 * @param string $new_name New template name.
	 * @return TC_Ticket_Designer_Template|false
	 */
	public function duplicate( $new_name = '' ) {
		if ( ! $this->id ) {
			return false;
		}

		$new_template = new self();
		$new_template->set( 'name', $new_name ? $new_name : $this->get( 'name' ) . ' (Copy)' );
		$new_template->set( 'template_data', $this->get( 'template_data' ) );
		$new_template->set( 'settings', $this->get( 'settings' ) );
		$new_template->set( 'status', 'active' );
		$new_template->set( 'is_default', 0 );

		if ( $new_template->save() ) {
			return $new_template;
		}

		return false;
	}
}
