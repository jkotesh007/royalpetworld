<?php
/**
 * Pet history storage (custom table).
 *
 * @package RPW_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * One row per history event. Sources: manual (staff), latepoint (booking), woocommerce (order item).
 */
class RPW_History {

	const TYPES = array(
		'visit'       => 'Service visit',
		'vaccination' => 'Vaccination',
		'health'      => 'Health / vet note',
		'purchase'    => 'Purchase',
	);

	/**
	 * Table name.
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'rpw_pet_history';
	}

	/**
	 * Create / upgrade the table.
	 */
	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			pet_id bigint(20) unsigned NOT NULL,
			type varchar(20) NOT NULL,
			event_date date NOT NULL,
			title varchar(255) NOT NULL,
			details text NULL,
			next_due_date date NULL,
			weight_kg decimal(6,2) NULL,
			is_internal tinyint(1) NOT NULL DEFAULT 0,
			source varchar(20) NOT NULL DEFAULT 'manual',
			source_ref varchar(64) NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY pet_date (pet_id,event_date),
			KEY type_due (type,next_due_date),
			UNIQUE KEY source_unique (pet_id,source,source_ref)
			) $charset;"
		);
		update_option( 'rpw_core_db_version', RPW_CORE_DB_VERSION );
	}

	/**
	 * Insert an entry. Returns new id, or existing id when (pet, source, source_ref) already exists.
	 *
	 * @param array $data Entry fields.
	 */
	public static function add( array $data ): int {
		global $wpdb;
		$row = self::sanitize( $data );
		if ( ! $row['pet_id'] || ! isset( self::TYPES[ $row['type'] ] ) || '' === $row['title'] || ! $row['event_date'] ) {
			return 0;
		}
		if ( $row['source_ref'] ) {
			$existing = self::find_by_source( $row['pet_id'], $row['source'], $row['source_ref'] );
			if ( $existing ) {
				self::update( (int) $existing->id, $data );
				return (int) $existing->id;
			}
		}
		$now               = current_time( 'mysql' );
		$row['created_by'] = isset( $data['created_by'] ) ? (int) $data['created_by'] : get_current_user_id();
		$row['created_at'] = $now;
		$row['updated_at'] = $now;
		$wpdb->insert( self::table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$id = (int) $wpdb->insert_id;
		if ( $id ) {
			self::after_change( $row['pet_id'], $row );
		}
		return $id;
	}

	/**
	 * Update an entry (only fields present in $data).
	 *
	 * @param int   $id   Entry id.
	 * @param array $data Fields.
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;
		$current = self::get( $id );
		if ( ! $current ) {
			return false;
		}
		$row = array_intersect_key( self::sanitize( array_merge( (array) $current, $data ) ), $data );
		unset( $row['pet_id'], $row['source'], $row['source_ref'] );
		if ( ! $row ) {
			return true;
		}
		$row['updated_at'] = current_time( 'mysql' );
		$ok                = false !== $wpdb->update( self::table(), $row, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		self::after_change( (int) $current->pet_id, $row );
		return $ok;
	}

	/**
	 * Delete one entry.
	 *
	 * @param int $id Entry id.
	 */
	public static function delete( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( self::table(), array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Delete all entries from a source whose ref equals $ref or starts with "$ref:".
	 *
	 * @param string $source Source.
	 * @param string $ref    Ref or ref prefix.
	 */
	public static function delete_by_source( string $source, string $ref ): int {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table WHERE source = %s AND ( source_ref = %s OR source_ref LIKE %s )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$source,
				$ref,
				$wpdb->esc_like( $ref . ':' ) . '%'
			)
		);
	}

	/**
	 * Get one entry.
	 *
	 * @param int $id Entry id.
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) ) ?: null;
	}

	/**
	 * Find by source.
	 *
	 * @param int    $pet_id Pet.
	 * @param string $source Source.
	 * @param string $ref    Ref.
	 */
	public static function find_by_source( int $pet_id, string $source, string $ref ): ?object {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE pet_id = %d AND source = %s AND source_ref = %s", $pet_id, $source, $ref ) ) ?: null;
	}

	/**
	 * Entries for a pet, newest first.
	 *
	 * @param int  $pet_id           Pet.
	 * @param bool $include_internal Include staff-only entries.
	 * @param string $type           Optional type filter.
	 */
	public static function for_pet( int $pet_id, bool $include_internal = true, string $type = '' ): array {
		global $wpdb;
		$table = self::table();
		$sql   = "SELECT * FROM $table WHERE pet_id = %d";
		$args  = array( $pet_id );
		if ( ! $include_internal ) {
			$sql .= ' AND is_internal = 0';
		}
		if ( $type ) {
			$sql   .= ' AND type = %s';
			$args[] = $type;
		}
		$sql .= ' ORDER BY event_date DESC, id DESC';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}

	/**
	 * Earliest upcoming vaccination due date for a pet (latest entry per vaccine wins).
	 *
	 * @param int $pet_id Pet.
	 */
	public static function next_vaccination_due( int $pet_id ): ?string {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$due = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(h.next_due_date) FROM $table h
				 WHERE h.pet_id = %d AND h.type = 'vaccination' AND h.next_due_date IS NOT NULL
				 AND h.event_date = ( SELECT MAX(h2.event_date) FROM $table h2 WHERE h2.pet_id = h.pet_id AND h2.type = 'vaccination' AND h2.title = h.title )",
				$pet_id
			)
		);
		return $due ?: null;
	}

	/**
	 * Last service visit date (past or today).
	 *
	 * @param int $pet_id Pet.
	 */
	public static function last_visit( int $pet_id ): ?string {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_var( $wpdb->prepare( "SELECT MAX(event_date) FROM $table WHERE pet_id = %d AND type = 'visit' AND event_date <= %s", $pet_id, current_time( 'Y-m-d' ) ) ) ?: null;
	}

	/**
	 * Clean incoming data into column values.
	 *
	 * @param array $d Raw.
	 */
	private static function sanitize( array $d ): array {
		$date = static function ( $v ) {
			$v = is_string( $v ) ? trim( $v ) : '';
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : null;
		};
		$weight = isset( $d['weight_kg'] ) && '' !== $d['weight_kg'] && null !== $d['weight_kg'] ? round( (float) $d['weight_kg'], 2 ) : null;
		return array(
			'pet_id'        => isset( $d['pet_id'] ) ? (int) $d['pet_id'] : 0,
			'type'          => isset( $d['type'] ) ? sanitize_key( $d['type'] ) : '',
			'event_date'    => $date( $d['event_date'] ?? '' ),
			'title'         => isset( $d['title'] ) ? sanitize_text_field( $d['title'] ) : '',
			'details'       => isset( $d['details'] ) ? sanitize_textarea_field( $d['details'] ) : '',
			'next_due_date' => $date( $d['next_due_date'] ?? '' ),
			'weight_kg'     => ( null !== $weight && $weight > 0 ) ? $weight : null,
			'is_internal'   => empty( $d['is_internal'] ) ? 0 : 1,
			'source'        => isset( $d['source'] ) ? sanitize_key( $d['source'] ) : 'manual',
			'source_ref'    => isset( $d['source_ref'] ) && '' !== $d['source_ref'] ? sanitize_text_field( (string) $d['source_ref'] ) : null,
		);
	}

	/**
	 * Keep pet profile in step with history (weight, last-changed stamp).
	 *
	 * @param int   $pet_id Pet.
	 * @param array $row    Changed columns.
	 */
	private static function after_change( int $pet_id, array $row ): void {
		if ( ! empty( $row['weight_kg'] ) ) {
			update_post_meta( $pet_id, 'weight_kg', $row['weight_kg'] );
		}
		update_post_meta( $pet_id, 'history_updated_at', current_time( 'mysql' ) );
		do_action( 'rpw_pet_history_changed', $pet_id );
	}
}
