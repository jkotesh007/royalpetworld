<?php
defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		$parent = wp_get_theme( get_template() );

		wp_enqueue_style(
			'kadence-parent-style',
			get_template_directory_uri() . '/style.css',
			array(),
			$parent->get( 'Version' )
		);

		wp_enqueue_style(
			'kadence-child-style',
			get_stylesheet_uri(),
			array( 'kadence-parent-style' ),
			wp_get_theme()->get( 'Version' )
		);

		// Product cards start hidden (style.css) and are revealed by this script — load it
		// wherever product grids appear, including the [products] blocks on the front page.
		if ( is_front_page() || ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) ) {
			wp_enqueue_script(
				'rpw-shop-animations',
				get_stylesheet_directory_uri() . '/shop-animations.js',
				array(),
				wp_get_theme()->get( 'Version' ),
				true
			);
		}
	}
);
