<?php
/**
 * wp-admin: pet profile + pet history screens for staff.
 *
 * @package RPW_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI.
 */
class RPW_Admin {

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes_' . RPW_Pets::CPT, array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post_' . RPW_Pets::CPT, array( __CLASS__, 'save' ), 10, 2 );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'force_private' ), 10, 2 );
		add_action( 'admin_post_rpw_delete_history', array( __CLASS__, 'delete_history' ) );
		add_filter( 'manage_' . RPW_Pets::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . RPW_Pets::CPT . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'admin_head', array( __CLASS__, 'css' ) );
	}

	/**
	 * Pets are always private.
	 *
	 * @param array $data    Post data.
	 * @param array $postarr Raw.
	 */
	public static function force_private( array $data, array $postarr ): array {
		if ( RPW_Pets::CPT === $data['post_type'] && ! in_array( $data['post_status'], array( 'trash', 'auto-draft', 'inherit' ), true ) ) {
			$data['post_status'] = 'private';
		}
		return $data;
	}

	/**
	 * Register boxes.
	 */
	public static function meta_boxes(): void {
		add_meta_box( 'rpw_pet_profile', 'Pet profile', array( __CLASS__, 'box_profile' ), RPW_Pets::CPT, 'normal', 'high' );
		add_meta_box( 'rpw_pet_history', 'Pet history', array( __CLASS__, 'box_history' ), RPW_Pets::CPT, 'normal', 'default' );
	}

	/**
	 * Profile fields.
	 *
	 * @param WP_Post $post Pet.
	 */
	public static function box_profile( WP_Post $post ): void {
		wp_nonce_field( 'rpw_pet_save', 'rpw_pet_nonce' );
		$m     = static fn( $k ) => RPW_Pets::meta( $post->ID, $k );
		$owner = $post->post_author ? get_userdata( (int) $post->post_author ) : null;
		$email = ( $owner && 'auto-draft' !== $post->post_status ) ? $owner->user_email : '';
		?>
		<table class="form-table rpw-form">
			<tr><th><label for="rpw_owner_email">Owner (customer email)</label></th>
				<td><input type="email" id="rpw_owner_email" name="rpw[owner_email]" value="<?php echo esc_attr( $email ); ?>" class="regular-text" required>
				<?php if ( $owner && $email ) : ?>
					<p class="description"><?php echo esc_html( $owner->display_name ); ?> — <a href="<?php echo esc_url( get_edit_user_link( $owner->ID ) ); ?>">view customer</a></p>
				<?php endif; ?></td></tr>
			<tr><th>Species</th><td><?php self::select( 'rpw[species]', RPW_Pets::SPECIES, $m( 'species' ) ); ?></td></tr>
			<tr><th><label for="rpw_breed">Breed</label></th><td><input id="rpw_breed" name="rpw[breed]" value="<?php echo esc_attr( $m( 'breed' ) ); ?>" class="regular-text"></td></tr>
			<tr><th><label for="rpw_dob">Date of birth</label></th><td><input type="date" id="rpw_dob" name="rpw[date_of_birth]" value="<?php echo esc_attr( $m( 'date_of_birth' ) ); ?>"> <?php echo esc_html( RPW_Pets::age( $post->ID ) ); ?></td></tr>
			<tr><th>Sex</th><td><?php self::select( 'rpw[sex]', array( '' => '—' ) + RPW_Pets::SEX, $m( 'sex' ) ); ?></td></tr>
			<tr><th><label for="rpw_wkg">Weight (kg)</label></th><td><input type="number" step="0.01" min="0" id="rpw_wkg" name="rpw[weight_kg]" value="<?php echo esc_attr( $m( 'weight_kg' ) ); ?>"></td></tr>
			<tr><th><label for="rpw_notes">Owner notes</label></th><td><textarea id="rpw_notes" name="rpw[notes]" rows="3" class="large-text"><?php echo esc_textarea( $m( 'notes' ) ); ?></textarea></td></tr>
			<tr><th>Status</th><td><?php self::select( 'rpw[status]', array( 'active' => 'Active', 'archived' => 'Archived' ), $m( 'status' ) ?: 'active' ); ?></td></tr>
		</table>
		<p class="description">Pet photo: use “Featured image” on the right. Pet name is the title above.</p>
		<?php
	}

	/**
	 * History list + add form.
	 *
	 * @param WP_Post $post Pet.
	 */
	public static function box_history( WP_Post $post ): void {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p>Save the pet first, then add history.</p>';
			return;
		}
		$due     = RPW_History::next_vaccination_due( $post->ID );
		$entries = RPW_History::for_pet( $post->ID );
		if ( $due ) {
			$overdue = $due < current_time( 'Y-m-d' );
			printf( '<p class="rpw-due%s"><strong>Next vaccination due:</strong> %s%s</p>', $overdue ? ' overdue' : '', esc_html( RPW_Pets::fmt_date( $due ) ), $overdue ? ' (overdue)' : '' );
		}
		?>
		<h4>Add entry</h4>
		<div class="rpw-add">
			<p><?php self::select( 'rpw_h[type]', array( 'health' => 'Health / vet note', 'vaccination' => 'Vaccination', 'visit' => 'Service visit', 'purchase' => 'Purchase' ), 'health' ); ?>
			<input type="date" name="rpw_h[event_date]" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" aria-label="Date">
			<input name="rpw_h[title]" placeholder="Title — e.g. Rabies vaccine, Skin allergy check" class="regular-text" aria-label="Title"></p>
			<p><textarea name="rpw_h[details]" rows="2" class="large-text" placeholder="Details — symptoms, diagnosis, medicines, dose, batch no., staff name…" aria-label="Details"></textarea></p>
			<p><label>Weight (kg) <input type="number" step="0.01" min="0" name="rpw_h[weight_kg]" style="width:90px"></label>
			<label>Next due <input type="date" name="rpw_h[next_due_date]"></label>
			<label><input type="checkbox" name="rpw_h[is_internal]" value="1"> Internal (hidden from customer)</label></p>
			<p class="description">Fill a title and click <strong>Update</strong> to save the entry. “Next due” drives vaccination reminders.</p>
		</div>
		<?php
		if ( ! $entries ) {
			echo '<p>No history yet.</p>';
			return;
		}
		echo '<table class="widefat striped rpw-hist"><thead><tr><th>Date</th><th>Type</th><th>Entry</th><th>Next due</th><th>Source</th><th></th></tr></thead><tbody>';
		foreach ( $entries as $e ) {
			$del = wp_nonce_url( admin_url( 'admin-post.php?action=rpw_delete_history&id=' . (int) $e->id . '&pet=' . (int) $post->ID ), 'rpw_delete_history_' . (int) $e->id );
			$by  = $e->created_by ? get_userdata( (int) $e->created_by ) : null;
			printf(
				'<tr><td>%s</td><td><span class="rpw-type rpw-%s">%s</span>%s</td><td><strong>%s</strong>%s%s</td><td>%s</td><td>%s</td><td><a href="%s" class="submitdelete" onclick="return confirm(\'Delete this history entry?\')">Delete</a></td></tr>',
				esc_html( RPW_Pets::fmt_date( $e->event_date ) ),
				esc_attr( $e->type ),
				esc_html( RPW_History::TYPES[ $e->type ] ?? $e->type ),
				$e->is_internal ? ' <span class="rpw-internal">internal</span>' : '',
				esc_html( $e->title ),
				$e->details ? '<br>' . nl2br( esc_html( $e->details ) ) : '',
				$e->weight_kg ? '<br>Weight: ' . esc_html( (string) (float) $e->weight_kg ) . ' kg' : '',
				esc_html( RPW_Pets::fmt_date( $e->next_due_date ) ),
				esc_html( 'manual' === $e->source ? ( $by ? $by->display_name : 'staff' ) : ucfirst( $e->source ) ),
				esc_url( $del )
			);
		}
		echo '</tbody></table>';
	}

	/**
	 * Save profile + optional new history entry.
	 *
	 * @param int     $post_id Pet.
	 * @param WP_Post $post    Post.
	 */
	public static function save( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['rpw_pet_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['rpw_pet_nonce'] ) ), 'rpw_pet_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['rpw'] ) ? wp_unslash( (array) $_POST['rpw'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in RPW_Pets::save.
		remove_action( 'save_post_' . RPW_Pets::CPT, array( __CLASS__, 'save' ), 10 );

		$owner = get_user_by( 'email', sanitize_email( $in['owner_email'] ?? '' ) );
		if ( ! $owner ) {
			set_transient( 'rpw_notice_' . get_current_user_id(), 'No customer account has that owner email — owner not changed.', 60 );
		}
		$data          = $in;
		$data['name']  = $post->post_title;
		if ( $owner ) {
			$data['owner_id'] = $owner->ID;
		}
		$res = RPW_Pets::save( $data, $post_id );
		if ( is_wp_error( $res ) ) {
			set_transient( 'rpw_notice_' . get_current_user_id(), $res->get_error_message(), 60 );
		}
		if ( isset( $in['status'] ) && in_array( $in['status'], array( 'active', 'archived' ), true ) ) {
			update_post_meta( $post_id, 'status', $in['status'] );
		}

		$h = isset( $_POST['rpw_h'] ) ? wp_unslash( (array) $_POST['rpw_h'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in RPW_History.
		if ( ! empty( $h['title'] ) ) {
			$h['pet_id'] = $post_id;
			$h['source'] = 'manual';
			if ( ! RPW_History::add( $h ) ) {
				set_transient( 'rpw_notice_' . get_current_user_id(), 'History entry not saved — check the date and title.', 60 );
			}
		}
	}

	/**
	 * Delete a history entry.
	 */
	public static function delete_history(): void {
		$id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$pet = isset( $_GET['pet'] ) ? absint( $_GET['pet'] ) : 0;
		check_admin_referer( 'rpw_delete_history_' . $id );
		if ( ! current_user_can( 'edit_post', $pet ) ) {
			wp_die( 'Not allowed.' );
		}
		$e = RPW_History::get( $id );
		if ( $e && (int) $e->pet_id === $pet ) {
			RPW_History::delete( $id );
		}
		wp_safe_redirect( get_edit_post_link( $pet, 'url' ) );
		exit;
	}

	/**
	 * List columns.
	 *
	 * @param array $cols Columns.
	 */
	public static function columns( array $cols ): array {
		return array(
			'cb'          => $cols['cb'],
			'title'       => 'Pet',
			'rpw_owner'   => 'Owner',
			'rpw_summary' => 'Species · breed · age',
			'rpw_vacc'    => 'Next vaccination',
			'rpw_visit'   => 'Last visit',
			'rpw_status'  => 'Status',
		);
	}

	/**
	 * Column values.
	 *
	 * @param string $col     Column.
	 * @param int    $post_id Pet.
	 */
	public static function column( string $col, int $post_id ): void {
		switch ( $col ) {
			case 'rpw_owner':
				$u = get_userdata( (int) get_post_field( 'post_author', $post_id ) );
				echo $u ? esc_html( $u->display_name . ' — ' . $u->user_email ) : '—';
				break;
			case 'rpw_summary':
				echo esc_html( RPW_Pets::summary( $post_id ) );
				break;
			case 'rpw_vacc':
				$d = RPW_History::next_vaccination_due( $post_id );
				echo $d ? esc_html( RPW_Pets::fmt_date( $d ) ) . ( $d < current_time( 'Y-m-d' ) ? ' <strong style="color:#b32d2e">overdue</strong>' : '' ) : '—';
				break;
			case 'rpw_visit':
				echo esc_html( RPW_Pets::fmt_date( RPW_History::last_visit( $post_id ) ) ?: '—' );
				break;
			case 'rpw_status':
				echo esc_html( ucfirst( RPW_Pets::meta( $post_id, 'status' ) ?: 'active' ) );
				break;
		}
	}

	/**
	 * One-shot error notices.
	 */
	public static function notices(): void {
		$key = 'rpw_notice_' . get_current_user_id();
		$msg = get_transient( $key );
		if ( $msg ) {
			delete_transient( $key );
			printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', esc_html( $msg ) );
		}
	}

	/**
	 * Small admin CSS on pet screens.
	 */
	public static function css(): void {
		$screen = get_current_screen();
		if ( ! $screen || RPW_Pets::CPT !== $screen->post_type ) {
			return;
		}
		echo '<style>.rpw-add{background:#f6f7f7;padding:8px 12px;border:1px solid #dcdcde;margin-bottom:12px}.rpw-type{display:inline-block;padding:1px 6px;border-radius:3px;background:#e7e1d8;font-size:11px}.rpw-vaccination{background:#d7eedb}.rpw-health{background:#fbe5d0}.rpw-purchase{background:#dde7f6}.rpw-internal{display:inline-block;padding:1px 5px;border-radius:3px;background:#b32d2e;color:#fff;font-size:10px}.rpw-due.overdue{color:#b32d2e}</style>';
	}

	/**
	 * Echo a select.
	 *
	 * @param string $name    Name.
	 * @param array  $options value => label.
	 * @param string $current Selected.
	 */
	private static function select( string $name, array $options, string $current ): void {
		echo '<select name="' . esc_attr( $name ) . '">';
		foreach ( $options as $v => $l ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $v ), selected( (string) $v, $current, false ), esc_html( $l ) );
		}
		echo '</select>';
	}
}
