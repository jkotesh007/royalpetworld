<?php
/**
 * Plugin Name: RPW Core
 * Description: Royal Pet World — pet profiles, pet history (visits, vaccinations, health notes, purchases), LatePoint + WooCommerce links.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Author: Switch2Growth
 * Text Domain: rpw-core
 *
 * @package RPW_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'RPW_CORE_VERSION', '0.1.0' );
define( 'RPW_CORE_DB_VERSION', '1' );
define( 'RPW_CORE_DIR', __DIR__ );
define( 'RPW_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once RPW_CORE_DIR . '/includes/class-rpw-history.php';
require_once RPW_CORE_DIR . '/includes/class-rpw-pets.php';
require_once RPW_CORE_DIR . '/includes/class-rpw-admin.php';
require_once RPW_CORE_DIR . '/includes/class-rpw-account.php';
require_once RPW_CORE_DIR . '/includes/class-rpw-latepoint.php';
require_once RPW_CORE_DIR . '/includes/class-rpw-woo.php';

register_activation_hook(
	__FILE__,
	static function () {
		RPW_History::install();
		RPW_Pets::register();
		RPW_Account::add_endpoint();
		flush_rewrite_rules();
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( get_option( 'rpw_core_db_version' ) !== RPW_CORE_DB_VERSION ) {
			RPW_History::install();
		}
		RPW_Pets::init();
		RPW_Admin::init();
		RPW_Account::init();
		RPW_LatePoint::init();
		RPW_Woo::init();
	}
);
