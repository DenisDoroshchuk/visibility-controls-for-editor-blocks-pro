<?php
/**
 * Plugin Name: Visibility Controls for Editor Blocks Pro
 * Description: Premium version of Visibility Controls for Editor Blocks.
 * Tags: block, visibility, gutenberg, responsive, breakpoints
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Denis Doroshchuk
 * Author URI: https://doroshchuk.me/
 * Text Domain: visibility-controls-for-editor-blocks
 * Domain Path: /languages
 * License: GPLv3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'gbvc_fs' ) ) {
	gbvc_fs()->set_basename( true, __FILE__ );
	return;
}

if ( ! function_exists( 'gbvc_fs' ) ) {
	// Create a helper function for easy SDK access.
	function gbvc_fs() {
		global $gbvc_fs;

		if ( ! isset( $gbvc_fs ) ) {
			// Include Freemius SDK.
			require_once __DIR__ . '/vendor/freemius/start.php';

			$gbvc_fs = fs_dynamic_init(
				array(
					'id'                  => '29179',
					'slug'                => 'visibility-controls-for-editor-blocks',
					'premium_slug'        => 'visibility-controls-for-editor-blocks-pro',
					'type'                => 'plugin',
					'public_key'          => 'pk_e6a53b83c36649824485a84562e90',
					'is_premium'          => true,
					'premium_suffix'      => 'Pro',
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'is_org_compliant'    => true,
					'menu'                => array(
						'slug'    => 'gbvc-settings',
						'support' => false,
						'parent'  => array(
							'slug' => 'options-general.php',
						),
					),
				)
			);
		}

		return $gbvc_fs;
	}

	// Init Freemius.
	gbvc_fs();
	// Signal that SDK was initiated.
	do_action( 'gbvc_fs_loaded' );
}
