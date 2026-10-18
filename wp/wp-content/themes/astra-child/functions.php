<?php
defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		$parent = wp_get_theme( get_template() );

		wp_enqueue_style(
			'astra-parent-style',
			get_template_directory_uri() . '/style.css',
			array(),
			$parent->get( 'Version' )
		);

		wp_enqueue_style(
			'astra-child-style',
			get_stylesheet_uri(),
			array( 'astra-parent-style' ),
			wp_get_theme()->get( 'Version' )
		);
	}
);
