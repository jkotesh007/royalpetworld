<?php
/**
 * Pet profiles: private CPT `rpw_pet`, author = owning customer.
 *
 * @package RPW_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pet CPT, fields and helpers.
 */
class RPW_Pets {

	const CPT     = 'rpw_pet';
	const SPECIES = array(
		'dog' => 'Dog',
		'cat' => 'Cat',
	);
	const SEX     = array(
		'male'   => 'Male',
		'female' => 'Female',
	);

	/** Profile meta keys a customer may edit. */
	const PROFILE_FIELDS = array( 'species', 'breed', 'date_of_birth', 'sex', 'weight_kg', 'notes' );

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register CPT — never public, never queryable on the front end.
	 */
	public static function register(): void {
		register_post_type(
			self::CPT,
			array(
				'labels'              => array(
					'name'          => 'Pets',
					'singular_name' => 'Pet',
					'add_new_item'  => 'Add pet',
					'edit_item'     => 'Edit pet',
					'search_items'  => 'Search pets',
					'not_found'     => 'No pets found',
					'menu_name'     => 'Pets',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-pets',
				'menu_position'       => 56,
				'supports'            => array( 'title', 'thumbnail' ),
				'capability_type'     => 'product', // Shop managers + admins only.
				'map_meta_cap'        => true,
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}

	/**
	 * Create or update a pet. Returns pet id or WP_Error.
	 *
	 * @param array $data    name + profile fields + optional owner_id.
	 * @param int   $pet_id  Existing pet to update (0 = create).
	 */
	public static function save( array $data, int $pet_id = 0 ) {
		$name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		if ( '' === $name ) {
			return new WP_Error( 'rpw_name', 'Please enter your pet’s name.' );
		}
		$species = isset( $data['species'] ) ? sanitize_key( $data['species'] ) : '';
		if ( ! isset( self::SPECIES[ $species ] ) ) {
			return new WP_Error( 'rpw_species', 'Please choose dog or cat.' );
		}
		$post = array(
			'post_type'   => self::CPT,
			'post_title'  => $name,
			'post_status' => 'private',
		);
		if ( isset( $data['owner_id'] ) ) {
			$post['post_author'] = (int) $data['owner_id'];
		}
		if ( $pet_id ) {
			$post['ID'] = $pet_id;
			$id         = wp_update_post( $post, true );
		} else {
			$id = wp_insert_post( $post, true );
			if ( ! is_wp_error( $id ) ) {
				update_post_meta( $id, 'status', 'active' );
			}
		}
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		$dob = isset( $data['date_of_birth'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['date_of_birth'] ) ? $data['date_of_birth'] : '';
		$sex = isset( $data['sex'] ) && isset( self::SEX[ $data['sex'] ] ) ? $data['sex'] : '';
		$wkg = isset( $data['weight_kg'] ) && is_numeric( $data['weight_kg'] ) && $data['weight_kg'] > 0 ? round( (float) $data['weight_kg'], 2 ) : '';
		update_post_meta( $id, 'species', $species );
		update_post_meta( $id, 'breed', sanitize_text_field( $data['breed'] ?? '' ) );
		update_post_meta( $id, 'date_of_birth', $dob );
		update_post_meta( $id, 'sex', $sex );
		update_post_meta( $id, 'weight_kg', $wkg );
		update_post_meta( $id, 'notes', sanitize_textarea_field( $data['notes'] ?? '' ) );
		update_post_meta( $id, 'updated_at', current_time( 'mysql' ) );
		do_action( 'rpw_pet_saved', (int) $id );
		return (int) $id;
	}

	/**
	 * Soft delete.
	 *
	 * @param int $pet_id Pet.
	 */
	public static function archive( int $pet_id ): void {
		update_post_meta( $pet_id, 'status', 'archived' );
		update_post_meta( $pet_id, 'updated_at', current_time( 'mysql' ) );
		do_action( 'rpw_pet_archived', $pet_id );
	}

	/**
	 * A customer's pets.
	 *
	 * @param int    $user_id User.
	 * @param string $species Optional dog|cat filter.
	 * @param bool   $active_only Skip archived.
	 * @return WP_Post[]
	 */
	public static function for_owner( int $user_id, string $species = '', bool $active_only = true ): array {
		if ( ! $user_id ) {
			return array();
		}
		$meta = array();
		if ( $active_only ) {
			$meta[] = array(
				'key'   => 'status',
				'value' => 'active',
			);
		}
		if ( $species ) {
			$meta[] = array(
				'key'   => 'species',
				'value' => $species,
			);
		}
		return get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'private',
				'author'         => $user_id,
				'posts_per_page' => 50,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
	}

	/**
	 * Is $user_id the owner of an active pet $pet_id?
	 *
	 * @param int $pet_id  Pet.
	 * @param int $user_id User.
	 */
	public static function is_owner( int $pet_id, int $user_id ): bool {
		$pet = get_post( $pet_id );
		return $pet && self::CPT === $pet->post_type && $user_id && (int) $pet->post_author === $user_id;
	}

	/**
	 * Field value.
	 *
	 * @param int    $pet_id Pet.
	 * @param string $key    Meta key.
	 */
	public static function meta( int $pet_id, string $key ): string {
		return (string) get_post_meta( $pet_id, $key, true );
	}

	/**
	 * Human age like "3 yrs 2 mo".
	 *
	 * @param int $pet_id Pet.
	 */
	public static function age( int $pet_id ): string {
		$dob = self::meta( $pet_id, 'date_of_birth' );
		if ( ! $dob ) {
			return '';
		}
		try {
			$diff = ( new DateTime( $dob ) )->diff( new DateTime( current_time( 'Y-m-d' ) ) );
		} catch ( Exception $e ) {
			return '';
		}
		if ( $diff->y ) {
			return $diff->y . ' yr' . ( 1 === $diff->y ? '' : 's' ) . ( $diff->m ? ' ' . $diff->m . ' mo' : '' );
		}
		return max( 1, $diff->m ) . ' mo';
	}

	/**
	 * "Dog · Labrador · 3 yrs" summary.
	 *
	 * @param int $pet_id Pet.
	 */
	public static function summary( int $pet_id ): string {
		$parts = array_filter(
			array(
				self::SPECIES[ self::meta( $pet_id, 'species' ) ] ?? '',
				self::meta( $pet_id, 'breed' ),
				self::age( $pet_id ),
			)
		);
		return implode( ' · ', $parts );
	}

	/**
	 * Display date d/m/Y (matches LatePoint setting).
	 *
	 * @param string|null $ymd Y-m-d.
	 */
	public static function fmt_date( ?string $ymd ): string {
		return $ymd ? mysql2date( 'd/m/Y', $ymd ) : '';
	}
}
