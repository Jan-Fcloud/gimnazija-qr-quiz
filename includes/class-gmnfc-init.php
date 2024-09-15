<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class GMNFC_Type{
	public function __construct(){
		// add_action('init', array($this, 'create_post_type'));
		// add_action('admin_menu', array($this, 'alter_post_type'));
		// add_action('init', array($this, 'hide_admin_bar'));
	}

	public function create_post_type(){
		register_post_type('gmnfc_quiz',
			array(
				'labels' => array(
					'name' => __('II. NFC Hunt - Naloge'),
					'singular_name' => __('Question'),
					'plural_name' => __('Questions'),
				),
				'show_in_menu' => false,
				'public' => true,
				'has_archive' => true,
				'rewrite' => array('slug' => 'naloge'),
				'show_in_rest' => true,
				'supports' => array('title', 'custom-fields', 'editor')
			)
		);

		
	}

	public function alter_post_type(){
		// when on ?post_type=gmnfc_quiz then delete the "add new post" button
		if(isset($_GET['post_type']) && $_GET['post_type'] == 'gmnfc_quiz'){
			echo '<style>
				.wrap > a{
					display: none !important;
				}
			</style>';
		}

	}

	// function that hides the admin bar if user isnt an admin
	public function hide_admin_bar(){
		if (!current_user_can('administrator') && !is_admin()) {
			show_admin_bar(false);
		}
	}


}