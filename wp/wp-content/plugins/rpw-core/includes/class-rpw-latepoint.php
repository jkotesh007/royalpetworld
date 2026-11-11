<?php
/**
 * LatePoint link: "Which pet?" on the booking form; bookings become pet-history visits.
 *
 * @package RPW_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * LatePoint integration (free plugin hooks only).
 */
class RPW_LatePoint {

	const META = 'rpw_pet_id';

	/**
	 * Hooks (inert when LatePoint is inactive — its actions never fire).
	 */
	public static function init(): void {
		add_action( 'latepoint_booking_steps_contact_after', array( __CLASS__, 'field' ), 10, 2 );
		add_action( 'latepoint_model_set_data', array( __CLASS__, 'capture' ), 10, 1 );
		add_action( 'latepoint_booking_created', array( __CLASS__, 'created' ), 20 );
		add_action( 'latepoint_booking_updated', array( __CLASS__, 'updated' ), 20, 2 );
	}

	/**
	 * "Which pet?" select on the contact step.
	 *
	 * @param mixed $customer LatePoint customer.
	 * @param mixed $booking  LatePoint booking.
	 */
	public static function field( $customer, $booking ): void {
		$species = self::species_for_service( (int) ( $booking->service_id ?? 0 ) );
		echo '<div class="os-row"><div class="os-col-12 rpw-lp-pet">';
		if ( ! is_user_logged_in() ) {
			printf(
				'<p class="rpw-lp-note">Have an account? <a href="%s" target="_blank" rel="noopener">Log in</a> to pick your pet and keep this visit in its history. Otherwise, write your pet’s name, breed and age in the comments.</p>',
				esc_url( wc_get_page_permalink( 'myaccount' ) )
			);
			echo '</div></div>';
			return;
		}
		$pets = RPW_Pets::for_owner( get_current_user_id(), $species );
		if ( ! $pets ) {
			printf(
				'<p class="rpw-lp-note">No %s on your account yet — <a href="%s" target="_blank" rel="noopener">add your pet</a> to keep its history, or write the details in the comments.</p>',
				esc_html( $species ? strtolower( RPW_Pets::SPECIES[ $species ] ) . 's' : 'pets' ),
				esc_url( RPW_Account::url( array( 'add' => 1 ) ) )
			);
			echo '</div></div>';
			return;
		}
		$selected = count( $pets ) === 1 ? $pets[0]->ID : 0;
		echo '<div class="os-form-group os-form-select-group os-form-group-transparent"><label for="rpw_pet_id">Which pet?</label>';
		echo '<select name="customer[rpw_pet_id]" id="rpw_pet_id" class="os-form-control required">';
		if ( ! $selected ) {
			echo '<option value="">Choose your pet…</option>';
		}
		foreach ( $pets as $pet ) {
			printf( '<option value="%d"%s>%s</option>', (int) $pet->ID, selected( $pet->ID, $selected, false ), esc_html( $pet->post_title . ' (' . RPW_Pets::summary( $pet->ID ) . ')' ) );
		}
		echo '</select></div></div></div>';
	}

	/**
	 * Remember the chosen pet for this WP user while the booking form is in progress.
	 * Reads the raw form params so it works whatever LatePoint keeps on its customer model.
	 */
	public static function capture(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! class_exists( 'OsParamsHelper' ) ) {
			return;
		}
		$params = OsParamsHelper::get_params();
		$pet_id = isset( $params['customer']['rpw_pet_id'] ) ? absint( $params['customer']['rpw_pet_id'] ) : 0;
		if ( $pet_id && RPW_Pets::is_owner( $pet_id, $user_id ) ) {
			set_transient( 'rpw_lp_pet_' . $user_id, $pet_id, 30 * MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Booking created → link pet + add a visit to its history.
	 *
	 * @param mixed $booking OsBookingModel.
	 */
	public static function created( $booking ): void {
		self::capture();
		$user_id = get_current_user_id();
		if ( ! $user_id || empty( $booking->id ) ) {
			return;
		}
		$pet_id = (int) get_transient( 'rpw_lp_pet_' . $user_id );
		if ( ! $pet_id ) {
			// Fall back to the only matching pet, if exactly one.
			$pets   = RPW_Pets::for_owner( $user_id, self::species_for_service( (int) $booking->service_id ) );
			$pet_id = 1 === count( $pets ) ? $pets[0]->ID : 0;
		}
		if ( ! $pet_id || ! RPW_Pets::is_owner( $pet_id, $user_id ) ) {
			return;
		}
		delete_transient( 'rpw_lp_pet_' . $user_id );
		$booking->save_meta_by_key( self::META, $pet_id );
		self::sync_visit( $booking, $pet_id );
	}

	/**
	 * Booking updated (reschedule, status change, staff edit).
	 *
	 * @param mixed $booking     New.
	 * @param mixed $old_booking Old.
	 */
	public static function updated( $booking, $old_booking = null ): void {
		if ( empty( $booking->id ) ) {
			return;
		}
		$pet_id = (int) $booking->get_meta_by_key( self::META, 0 );
		if ( ! $pet_id ) {
			return;
		}
		if ( 'cancelled' === $booking->status ) {
			RPW_History::delete_by_source( 'latepoint', 'booking:' . (int) $booking->id );
			return;
		}
		self::sync_visit( $booking, $pet_id );
	}

	/**
	 * Upsert the visit row for a booking.
	 *
	 * @param mixed $booking OsBookingModel.
	 * @param int   $pet_id  Pet.
	 */
	private static function sync_visit( $booking, int $pet_id ): void {
		$service = new OsServiceModel( (int) $booking->service_id );
		$agent   = class_exists( 'OsAgentModel' ) ? new OsAgentModel( (int) $booking->agent_id ) : null;
		$time    = method_exists( $booking, 'get_nice_start_time' ) ? $booking->get_nice_start_time() : '';
		$details = trim( $time . ( $agent && ! empty( $agent->first_name ) ? ' · ' . trim( $agent->first_name . ' ' . $agent->last_name ) : '' ) . ' · Booking ' . $booking->booking_code );
		RPW_History::add(
			array(
				'pet_id'     => $pet_id,
				'type'       => 'visit',
				'event_date' => $booking->start_date,
				'title'      => $service->name ?: 'Service visit',
				'details'    => $details,
				'source'     => 'latepoint',
				'source_ref' => 'booking:' . (int) $booking->id,
			)
		);
	}

	/**
	 * Service names starting "Dog"/"Cat" (or containing "Puppy") are species-specific.
	 *
	 * @param int $service_id LatePoint service.
	 */
	private static function species_for_service( int $service_id ): string {
		if ( ! $service_id || ! class_exists( 'OsServiceModel' ) ) {
			return '';
		}
		$name = strtolower( (string) ( new OsServiceModel( $service_id ) )->name );
		if ( str_starts_with( $name, 'dog' ) || str_contains( $name, 'puppy' ) ) {
			return 'dog';
		}
		if ( str_starts_with( $name, 'cat' ) ) {
			return 'cat';
		}
		return '';
	}
}
