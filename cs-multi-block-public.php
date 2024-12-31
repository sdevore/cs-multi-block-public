<?php
/**
 * Plugin Name:       CreativeSlice Multi Block Example Public
 * Description:       Example block scaffolded with Create Block tool.
 * Requires at least: 6.6
 * Requires PHP:      7.2
 * Version:           0.0.0
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cs-multi-block-public
 *
 * @package CreateBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin updater
 * - GitHub repo path
 * - GitHub Personal Access Token (PAT) with read access to repo
 * -- expires 2025.11.23
 */
if (is_admin()) {
	// One should just check that the class doesn't exist in the current namespace for the
	// current plugin, and then require the file and instantiate the class.
	if (!class_exists('CreativeSlice\WPAdmin\Plugin_Updater')) {
		require_once plugin_dir_path(__FILE__) . 'includes/cslice-plugin-updater/cslice-plugin-updater.php';
	}
	(new \CreativeSlice\WPAdmin\Plugin_Updater(
		__FILE__,
		'sdevore/cs-multi-block-public'
	))
		->set_plugin_icon('assets/icon.png')
		->set_plugin_banner_small('assets/banner-small.png')
		->set_plugin_banner_large('assets/banner-large.png')
	->plugin_is_built();

}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function multiblock_register_blocks() {
	$blocks = [
		'block-one',
		'block-two',
		'block-three',
		'block-four',
	];
	foreach ( $blocks as $block ) {
		register_block_type( __DIR__ . '/build/blocks/' . $block );
	}
}
add_action( 'init', 'multiblock_register_blocks' );

function multiblock_enqueue_block_assets() {
	wp_enqueue_script(
		'multi-block-editor-js',
		plugin_dir_url( __FILE__ ) . 'build/multi-block-editor.js',
		array('wp-blocks', 'wp-components', 'wp-data', 'wp-dom-ready', 'wp-edit-post', 'wp-element', 'wp-i18n', 'wp-plugins'),
		null,
		false
	);

	wp_enqueue_style(
		'multi-block-editor-css',
		plugin_dir_url( __FILE__ ) . 'build/multi-block-editor.css',
		array(),
		null
	);
}
add_action( 'enqueue_block_editor_assets', 'multiblock_enqueue_block_assets' );


function multiblock_enqueue_frontend_assets() {
	wp_enqueue_style(
		'multi-block-frontend-css',
		plugin_dir_url( __FILE__ ) . 'build/style-multi-block-editor.css',
	);

	wp_enqueue_script(
		'multi-block-frontend-js',
		plugin_dir_url( __FILE__ ) . 'build/multi-block-frontend.js',
		array(),
		null,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'multiblock_enqueue_frontend_assets' );
