<?php
/**
 * @package gimnazija-nfc-hunt
 * @version 19042024a
 */

/*
 * Plugin Name:       Gimnazija NFC Hunt
 *
 * Description:       Plugin trenutno namenjen za "Kulturni maraton". Omogoča registracijo uporabnikov, beleženje njihove udeležbe na dogodku in točkovanje kvizov.
 * Version:           19042024a
 * Author:            Jan-Fcloud
 * Author URI:        https://jans.dev
 * Text Domain:       jans-gmnfc
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Define plugin path
define('GMNFC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GMNFC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GMNFC_SITE_URL', get_site_url());
define('GMNFC_PLUGIN_VERSION', '19042024a');

// Include the main plugin class file
require_once GMNFC_PLUGIN_PATH . 'includes/class-gmnfc.php';

// function runs on plugin activation
register_activation_hook(__FILE__, 'db_setup_run');
function db_setup_run(){
    require_once GMNFC_PLUGIN_PATH . 'includes/db-gmnfc.php';
    db_setup();
}

add_action('init', 'gmnfc_type_init');
function gmnfc_type_init(){
    require_once GMNFC_PLUGIN_PATH . 'includes/class-gmnfc-init.php';
	$gmnfc_type = new GMNFC_Type();

	// Add custom post type
	$gmnfc_type->create_post_type();
	// Hide admin bar for non admins
	$gmnfc_type->hide_admin_bar();

	// check if user is on the wp-admin page, if he is, and isn't admin, redirect him to the home page
	if (!current_user_can('administrator') && is_admin()) {
		wp_safe_redirect( home_url() );
		exit;
	}

}

add_action('admin_menu', 'alterAdminPostTypePage');
function alterAdminPostTypePage(){
	require_once GMNFC_PLUGIN_PATH . 'includes/class-gmnfc-init.php';
	$gmnfc_type = new GMNFC_Type();

	// Add custom post type
	$gmnfc_type->alter_post_type();
}

// function runs on user registration
add_action('user_register', 'proccessUser', 10, 1);
function proccessUser($user_id){
    require_once GMNFC_PLUGIN_PATH . 'includes/class-gmnfc.php';
    $gmnfc = new GMNFC();
    $gmnfc->proccessUser($user_id);
}

// function runs to add an admin menu
add_action('admin_menu', 'addAdminMenu');
function addAdminMenu(){
    require_once GMNFC_PLUGIN_PATH . 'includes/class-gmnfc.php';
    $gmnfc = new GMNFC();
    $gmnfc->addAdminMenu();

	// remove_menu_page('edit.php?post_type=gmnfc_quiz');
}

add_action('admin_init', 'adminInit');
function adminInit(){
	// Redirect non admins
	if (!current_user_can('administrator') && !is_admin()) {
		echo "WASSSS?";
		wp_safe_redirect( home_url() );
		exit;
	}
}

// function runs to add a shortcode
add_shortcode('gmnfc-main', 'gmnfcShortcode');
function gmnfcShortcode(){
	$proceed = false;
	if(isset($_GET['lov'])){
		if($_GET['lov'] == 'true'){
			$proceed = true;
		}
	}
	if(isset($_GET['logout'])){
		if($_GET['logout'] == 'true'){
			wp_logout();
		}
	}
    require_once GMNFC_PLUGIN_PATH . 'includes/class-gmnfc-hello.php';
    $gmnfc = new GMNFC_Hello(is_user_logged_in(), $proceed);
    return $gmnfc->display_menu_page();
}

// function runs to add a shortcode
add_shortcode('gmnfc-quiz', 'gmnfcQuizShortcode');
function gmnfcQuizShortcode($atts){
    require_once GMNFC_PLUGIN_PATH . 'includes/class-gmnfc.php';
    $gmnfc = new GMNFC();

    if(isset($atts['id'])){
        return $gmnfc->gmnfcQuizShortcodeLoad($atts['id']);
    } else {
        return $gmnfc->errorPage();
    }
}

// Function to disable gutenberg for my post type
function my_disable_gutenberg( $current_status, $post_type ) {

	// Disabled post types
	$disabled_post_types = array( 'gmnfc_quiz' );

	// Change $can_edit to false for any post types in the disabled post types array
	if ( in_array( $post_type, $disabled_post_types, true ) ) {
		$current_status = false;
	}

	return $current_status;
}
add_filter( 'use_block_editor_for_post_type', 'my_disable_gutenberg', 10, 2 );



// Define a function to hide all meta info in a way that I replace the post title with CSS
function remove_post_title($title) {
	if (is_singular('gmnfc_quiz')) {
		return '<style>main > .wp-block-group > .wp-block-group:has(.wp-block-post-title):has(.wp-block-template-part){display: none;} main > div:last-child > div{display: none;)</style>';
	}
	return $title;
}
add_filter('the_title', 'remove_post_title');

add_filter( 'login_redirect', function( $redirect_to, $requested_redirect_to, $user ) {
	if ( ! $requested_redirect_to ) {
		$referrer = wp_get_referer();

		// Make sure the referring page is not a variation of the wp-login page or was the admin (aka user is logging out).
		if ( $referrer && ! str_contains( $referrer, 'wp-login' ) && ! str_contains( $referrer, 'wp-admin' ) ) {
			$redirect_to = $referrer;
		}
	}
	return $redirect_to;
}, 10, 3 );
