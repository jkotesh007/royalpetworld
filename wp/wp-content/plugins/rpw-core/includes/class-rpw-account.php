<?php
/**
 * My Account → My Pets: customers manage pet profiles and read pet history.
 *
 * @package RPW_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end account tab.
 */
class RPW_Account {

	const ENDPOINT = 'my-pets';

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'add_endpoint' ) );
		add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'query_vars' ) );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'menu' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( __CLASS__, 'render' ) );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', static fn() => 'My Pets' );
		add_action( 'template_redirect', array( __CLASS__, 'handle_post' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'css' ) );
	}

	/**
	 * Rewrite endpoint.
	 */
	public static function add_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Register with WooCommerce.
	 *
	 * @param array $vars Query vars.
	 */
	public static function query_vars( array $vars ): array {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * Insert "My Pets" after Dashboard.
	 *
	 * @param array $items Menu.
	 */
	public static function menu( array $items ): array {
		$out = array();
		foreach ( $items as $k => $v ) {
			$out[ $k ] = $v;
			if ( 'dashboard' === $k ) {
				$out[ self::ENDPOINT ] = 'My Pets';
			}
		}
		return $out;
	}

	/**
	 * Base URL of the tab.
	 *
	 * @param array $args Query args.
	 */
	public static function url( array $args = array() ): string {
		return add_query_arg( $args, wc_get_account_endpoint_url( self::ENDPOINT ) );
	}

	/**
	 * Save / archive submissions.
	 */
	public static function handle_post(): void {
		if ( empty( $_POST['rpw_account_action'] ) || ! is_user_logged_in() ) {
			return;
		}
		$action = sanitize_key( wp_unslash( $_POST['rpw_account_action'] ) );
		$pet_id = isset( $_POST['pet_id'] ) ? absint( $_POST['pet_id'] ) : 0;
		$nonce  = isset( $_POST['_rpw_nonce'] ) ? sanitize_key( wp_unslash( $_POST['_rpw_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'rpw_account_' . $action . '_' . $pet_id ) ) {
			wc_add_notice( 'Your session expired — please try again.', 'error' );
			return;
		}
		$user_id = get_current_user_id();
		if ( $pet_id && ! RPW_Pets::is_owner( $pet_id, $user_id ) ) {
			wc_add_notice( 'That pet isn’t on your account.', 'error' );
			return;
		}

		if ( 'archive' === $action && $pet_id ) {
			RPW_Pets::archive( $pet_id );
			wc_add_notice( 'Pet removed from your list. Its history is kept by the store.' );
			wp_safe_redirect( self::url() );
			exit;
		}

		if ( 'save' === $action ) {
			$in   = isset( $_POST['pet'] ) ? wp_unslash( (array) $_POST['pet'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in RPW_Pets::save.
			$data = array_intersect_key( $in, array_flip( array_merge( array( 'name' ), RPW_Pets::PROFILE_FIELDS ) ) );
			$res  = RPW_Pets::save( $data, $pet_id );
			if ( is_wp_error( $res ) ) {
				wc_add_notice( $res->get_error_message(), 'error' );
				return;
			}
			$photo_error = self::maybe_upload_photo( $res );
			if ( $photo_error ) {
				wc_add_notice( $photo_error, 'notice' );
			}
			wc_add_notice( $pet_id ? 'Pet profile updated.' : 'Pet added.' );
			wp_safe_redirect( self::url( array( 'pet' => $res ) ) );
			exit;
		}
	}

	/**
	 * Optional photo upload (jpg/png/webp, ≤ 5 MB). Returns error text or ''.
	 *
	 * @param int $pet_id Pet.
	 */
	private static function maybe_upload_photo( int $pet_id ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked in handle_post.
		if ( empty( $_FILES['pet_photo']['name'] ) || ! empty( $_FILES['pet_photo']['error'] ) && UPLOAD_ERR_NO_FILE === $_FILES['pet_photo']['error'] ) {
			return '';
		}
		$size = isset( $_FILES['pet_photo']['size'] ) ? (int) $_FILES['pet_photo']['size'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $size > 5 * MB_IN_BYTES ) {
			return 'Photo not saved — please use an image under 5 MB.';
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$att = media_handle_upload(
			'pet_photo',
			$pet_id,
			array(),
			array(
				'test_form' => false,
				'mimes'     => array(
					'jpg|jpeg' => 'image/jpeg',
					'png'      => 'image/png',
					'webp'     => 'image/webp',
				),
			)
		);
		if ( is_wp_error( $att ) ) {
			return 'Photo not saved — ' . $att->get_error_message();
		}
		$old = (int) get_post_thumbnail_id( $pet_id );
		set_post_thumbnail( $pet_id, $att );
		if ( $old && $old !== $att ) {
			wp_delete_attachment( $old, true );
		}
		return '';
	}

	/**
	 * Tab content.
	 */
	public static function render(): void {
		$user_id = get_current_user_id();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only view switches.
		$pet_id = isset( $_GET['pet'] ) ? absint( $_GET['pet'] ) : 0;
		$adding = isset( $_GET['add'] );
		$edit   = isset( $_GET['edit'] );
		// phpcs:enable

		if ( $adding ) {
			self::form( 0 );
			return;
		}
		if ( $pet_id ) {
			if ( ! RPW_Pets::is_owner( $pet_id, $user_id ) || 'archived' === RPW_Pets::meta( $pet_id, 'status' ) ) {
				echo '<p>Pet not found.</p>';
				return;
			}
			$edit ? self::form( $pet_id ) : self::detail( $pet_id );
			return;
		}

		$pets = RPW_Pets::for_owner( $user_id );
		echo '<p><a class="button" href="' . esc_url( self::url( array( 'add' => 1 ) ) ) . '">+ Add a pet</a></p>';
		if ( ! $pets ) {
			echo '<p>No pets yet. Add your dog or cat to book services for them and keep their health history in one place.</p>';
			return;
		}
		echo '<div class="rpw-pets">';
		foreach ( $pets as $pet ) {
			$due = RPW_History::next_vaccination_due( $pet->ID );
			$lv  = RPW_History::last_visit( $pet->ID );
			printf(
				'<a class="rpw-pet-card" href="%s">%s<span class="rpw-pet-name">%s</span><span class="rpw-pet-sum">%s</span>%s%s</a>',
				esc_url( self::url( array( 'pet' => $pet->ID ) ) ),
				self::photo( $pet->ID ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in photo().
				esc_html( $pet->post_title ),
				esc_html( RPW_Pets::summary( $pet->ID ) ),
				$due ? '<span class="rpw-pet-meta' . ( $due < current_time( 'Y-m-d' ) ? ' overdue' : '' ) . '">Vaccination due ' . esc_html( RPW_Pets::fmt_date( $due ) ) . '</span>' : '',
				$lv ? '<span class="rpw-pet-meta">Last visit ' . esc_html( RPW_Pets::fmt_date( $lv ) ) . '</span>' : ''
			);
		}
		echo '</div>';
	}

	/**
	 * Pet profile + history.
	 *
	 * @param int $pet_id Pet.
	 */
	private static function detail( int $pet_id ): void {
		$pet = get_post( $pet_id );
		$m   = static fn( $k ) => RPW_Pets::meta( $pet_id, $k );
		echo '<p><a href="' . esc_url( self::url() ) . '">← All pets</a></p>';
		echo '<div class="rpw-pet-head">' . self::photo( $pet_id ) . '<div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<h3>' . esc_html( $pet->post_title ) . '</h3><p>' . esc_html( RPW_Pets::summary( $pet_id ) ) . '</p>';
		$facts = array_filter(
			array(
				'Born'   => RPW_Pets::fmt_date( $m( 'date_of_birth' ) ),
				'Sex'    => RPW_Pets::SEX[ $m( 'sex' ) ] ?? '',
				'Weight' => $m( 'weight_kg' ) ? $m( 'weight_kg' ) . ' kg' : '',
			)
		);
		foreach ( $facts as $k => $v ) {
			echo '<span class="rpw-fact"><strong>' . esc_html( $k ) . ':</strong> ' . esc_html( $v ) . '</span> ';
		}
		$due = RPW_History::next_vaccination_due( $pet_id );
		if ( $due ) {
			echo '<p class="rpw-due' . ( $due < current_time( 'Y-m-d' ) ? ' overdue' : '' ) . '"><strong>Next vaccination due:</strong> ' . esc_html( RPW_Pets::fmt_date( $due ) ) . '</p>';
		}
		echo '<p><a class="button" href="' . esc_url( self::url( array( 'pet' => $pet_id, 'edit' => 1 ) ) ) . '">Edit profile</a> ';
		echo '<a class="button" href="' . esc_url( home_url( '/services/' ) ) . '">Book a service</a></p></div></div>';

		echo '<h3>Pet history</h3>';
		$entries = RPW_History::for_pet( $pet_id, false );
		if ( ! $entries ) {
			echo '<p>No history yet. Visits, vaccinations, vet notes and purchases for ' . esc_html( $pet->post_title ) . ' will appear here.</p>';
			return;
		}
		$today = current_time( 'Y-m-d' );
		echo '<ul class="rpw-timeline">';
		foreach ( $entries as $e ) {
			$upcoming = 'visit' === $e->type && $e->event_date > $today;
			printf(
				'<li class="rpw-t-%s"><span class="rpw-t-date">%s</span><span class="rpw-t-type">%s%s</span><strong>%s</strong>%s%s%s</li>',
				esc_attr( $e->type ),
				esc_html( RPW_Pets::fmt_date( $e->event_date ) ),
				esc_html( RPW_History::TYPES[ $e->type ] ?? $e->type ),
				$upcoming ? ' · upcoming' : '',
				esc_html( $e->title ),
				$e->details ? '<span class="rpw-t-details">' . nl2br( esc_html( $e->details ) ) . '</span>' : '',
				$e->weight_kg ? '<span class="rpw-t-details">Weight: ' . esc_html( (string) (float) $e->weight_kg ) . ' kg</span>' : '',
				$e->next_due_date ? '<span class="rpw-t-details">Next due: ' . esc_html( RPW_Pets::fmt_date( $e->next_due_date ) ) . '</span>' : ''
			);
		}
		echo '</ul>';
	}

	/**
	 * Add / edit form.
	 *
	 * @param int $pet_id Pet (0 = new).
	 */
	private static function form( int $pet_id ): void {
		$pet = $pet_id ? get_post( $pet_id ) : null;
		$m   = static fn( $k ) => $pet_id ? RPW_Pets::meta( $pet_id, $k ) : '';
		$opt = static function ( array $opts, string $cur ): void {
			foreach ( $opts as $v => $l ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( $v, $cur, false ), esc_html( $l ) );
			}
		};
		echo '<p><a href="' . esc_url( $pet_id ? self::url( array( 'pet' => $pet_id ) ) : self::url() ) . '">← Back</a></p>';
		?>
		<form method="post" enctype="multipart/form-data" class="rpw-pet-form woocommerce-EditAccountForm">
			<?php wp_nonce_field( 'rpw_account_save_' . $pet_id, '_rpw_nonce' ); ?>
			<input type="hidden" name="rpw_account_action" value="save">
			<input type="hidden" name="pet_id" value="<?php echo esc_attr( (string) $pet_id ); ?>">
			<p class="form-row form-row-first"><label for="rpw-name">Pet name&nbsp;<span class="required">*</span></label>
				<input class="input-text" id="rpw-name" name="pet[name]" required value="<?php echo esc_attr( $pet ? $pet->post_title : '' ); ?>"></p>
			<p class="form-row form-row-last"><label for="rpw-species">Dog or cat&nbsp;<span class="required">*</span></label>
				<select id="rpw-species" name="pet[species]" required><option value="">Choose…</option><?php $opt( RPW_Pets::SPECIES, $m( 'species' ) ); ?></select></p>
			<p class="form-row form-row-first"><label for="rpw-breed">Breed</label>
				<input class="input-text" id="rpw-breed" name="pet[breed]" value="<?php echo esc_attr( $m( 'breed' ) ); ?>" placeholder="e.g. Labrador, Indie, Persian"></p>
			<p class="form-row form-row-last"><label for="rpw-dob">Date of birth (approx. is fine)</label>
				<input class="input-text" type="date" id="rpw-dob" name="pet[date_of_birth]" max="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( $m( 'date_of_birth' ) ); ?>"></p>
			<p class="form-row form-row-first"><label for="rpw-sex">Sex</label>
				<select id="rpw-sex" name="pet[sex]"><option value="">—</option><?php $opt( RPW_Pets::SEX, $m( 'sex' ) ); ?></select></p>
			<p class="form-row form-row-last"><label for="rpw-wkg">Weight (kg)</label>
				<input class="input-text" type="number" step="0.1" min="0" id="rpw-wkg" name="pet[weight_kg]" value="<?php echo esc_attr( $m( 'weight_kg' ) ); ?>"></p>
			<p class="form-row form-row-wide"><label for="rpw-notes">Anything we should know? (allergies, temperament, diet)</label>
				<textarea class="input-text" id="rpw-notes" name="pet[notes]" rows="3"><?php echo esc_textarea( $m( 'notes' ) ); ?></textarea></p>
			<p class="form-row form-row-wide"><label for="rpw-photo">Photo (jpg, png or webp, max 5 MB)</label>
				<input type="file" id="rpw-photo" name="pet_photo" accept="image/jpeg,image/png,image/webp"></p>
			<p><button type="submit" class="button"><?php echo $pet_id ? 'Save changes' : 'Add pet'; ?></button></p>
		</form>
		<?php
		if ( $pet_id ) {
			?>
			<form method="post" class="rpw-archive" onsubmit="return confirm('Remove this pet from your list?');">
				<?php wp_nonce_field( 'rpw_account_archive_' . $pet_id, '_rpw_nonce' ); ?>
				<input type="hidden" name="rpw_account_action" value="archive">
				<input type="hidden" name="pet_id" value="<?php echo esc_attr( (string) $pet_id ); ?>">
				<button type="submit" class="button-link">Remove this pet</button>
			</form>
			<?php
		}
	}

	/**
	 * Photo or initial.
	 *
	 * @param int $pet_id Pet.
	 */
	private static function photo( int $pet_id ): string {
		if ( has_post_thumbnail( $pet_id ) ) {
			return get_the_post_thumbnail( $pet_id, 'thumbnail', array( 'class' => 'rpw-pet-photo', 'alt' => '' ) );
		}
		$initial = mb_strtoupper( mb_substr( get_the_title( $pet_id ), 0, 1 ) );
		return '<span class="rpw-pet-photo rpw-pet-initial">' . esc_html( $initial ) . '</span>';
	}

	/**
	 * Styles on the account page only.
	 */
	public static function css(): void {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}
		wp_register_style( 'rpw-account', false, array(), RPW_CORE_VERSION );
		wp_enqueue_style( 'rpw-account' );
		wp_add_inline_style(
			'rpw-account',
			'.rpw-pets{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px}
			.rpw-pet-card{display:flex;flex-direction:column;gap:4px;padding:16px;border:1px solid #e5ddd3;border-radius:10px;text-decoration:none;color:inherit;background:#fff}
			.rpw-pet-card:hover{border-color:#8b5e3c}
			.rpw-pet-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;flex:none}
			.rpw-pet-initial{display:flex;align-items:center;justify-content:center;background:#8b5e3c;color:#fff;font-size:28px;font-weight:600}
			.rpw-pet-name{font-weight:600;font-size:1.1em;margin-top:6px}.rpw-pet-sum,.rpw-pet-meta{font-size:.9em;opacity:.8}
			.overdue{color:#b32d2e!important;opacity:1}
			.rpw-pet-head{display:flex;gap:20px;align-items:flex-start;margin-bottom:24px}.rpw-pet-head h3{margin:0}
			.rpw-fact{margin-right:12px}
			.rpw-timeline{list-style:none;margin:0;padding:0;border-left:3px solid #e5ddd3}
			.rpw-timeline li{position:relative;padding:4px 0 16px 18px;display:flex;flex-direction:column}
			.rpw-timeline li:before{content:"";position:absolute;left:-8px;top:8px;width:13px;height:13px;border-radius:50%;background:#8b5e3c}
			.rpw-t-vaccination:before{background:#3c8b4f!important}.rpw-t-health:before{background:#c0712e!important}.rpw-t-purchase:before{background:#3c62a8!important}
			.rpw-t-date{font-size:.85em;opacity:.7}.rpw-t-type{font-size:.8em;text-transform:uppercase;letter-spacing:.04em;opacity:.7}.rpw-t-details{font-size:.92em}
			.rpw-archive{margin-top:12px}.rpw-archive .button-link{background:none;border:0;color:#b32d2e;text-decoration:underline;cursor:pointer;padding:0}
			@media(max-width:600px){.rpw-pet-head{flex-direction:column}}'
		);
	}
}
