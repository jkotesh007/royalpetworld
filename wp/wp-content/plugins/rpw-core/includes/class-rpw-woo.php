<?php
/**
 * WooCommerce link: paid orders become "purchase" entries in pet history.
 *
 * Mapping rule: a product in the Dog category tree goes to the customer's active dogs,
 * Cat tree → cats; a product in both trees → all of them. Guest orders are skipped.
 *
 * @package RPW_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce integration.
 */
class RPW_Woo {

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'record' ) );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'record' ) );
		add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'remove' ) );
		add_action( 'woocommerce_order_status_refunded', array( __CLASS__, 'remove' ) );
		add_action( 'woocommerce_order_status_failed', array( __CLASS__, 'remove' ) );
	}

	/**
	 * Add purchase entries for an order (idempotent per order item + pet).
	 *
	 * @param int $order_id Order.
	 */
	public static function record( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->get_customer_id() ) {
			return;
		}
		$user_id = (int) $order->get_customer_id();
		$pets    = array(
			'dog' => RPW_Pets::for_owner( $user_id, 'dog' ),
			'cat' => RPW_Pets::for_owner( $user_id, 'cat' ),
		);
		if ( ! $pets['dog'] && ! $pets['cat'] ) {
			return;
		}
		$date = $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : current_time( 'Y-m-d' );
		foreach ( $order->get_items() as $item_id => $item ) {
			$product_id = $item->get_product_id();
			if ( ! $product_id ) {
				continue;
			}
			foreach ( self::species_for_product( $product_id ) as $species ) {
				foreach ( $pets[ $species ] as $pet ) {
					RPW_History::add(
						array(
							'pet_id'     => $pet->ID,
							'type'       => 'purchase',
							'event_date' => $date,
							'title'      => $item->get_name(),
							'details'    => sprintf( 'Qty %d · Order #%s', $item->get_quantity(), $order->get_order_number() ),
							'source'     => 'woocommerce',
							'source_ref' => $order_id . ':' . $item_id,
							'created_by' => $user_id,
						)
					);
				}
			}
		}
	}

	/**
	 * Cancelled / refunded / failed → remove that order's purchase entries.
	 *
	 * @param int $order_id Order.
	 */
	public static function remove( int $order_id ): void {
		RPW_History::delete_by_source( 'woocommerce', (string) $order_id );
	}

	/**
	 * Which pet trees a product sits in.
	 *
	 * @param int $product_id Product.
	 * @return string[] Subset of dog, cat.
	 */
	private static function species_for_product( int $product_id ): array {
		$out = array();
		foreach ( array( 'dog', 'cat' ) as $slug ) {
			$root = get_term_by( 'slug', $slug, 'product_cat' );
			if ( ! $root ) {
				continue;
			}
			$ids = array_merge( array( (int) $root->term_id ), array_map( 'intval', get_term_children( $root->term_id, 'product_cat' ) ) );
			if ( has_term( $ids, 'product_cat', $product_id ) ) {
				$out[] = $slug;
			}
		}
		return $out;
	}
}
