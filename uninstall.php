<?php
/**
 * Uninstall
 *
 * @package Bulk Mail Send
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;

/* For Single site */
if ( ! is_multisite() ) {
	$blogusers = get_users( array( 'fields' => array( 'ID' ) ) );
	foreach ( $blogusers as $user ) {
		delete_user_option( $user->ID, 'bulkmailsend', false );
		delete_user_option( $user->ID, 'bulkmailsenduser_search_text', false );
		delete_user_option( $user->ID, 'bulkmailsendorder_search_text', false );
		delete_user_option( $user->ID, 'bulkmailsenduser_role', false );
		delete_user_option( $user->ID, 'bulkmailsendorder_product', false );
		delete_user_option( $user->ID, 'bms_user_per_page', false );
		delete_user_option( $user->ID, 'bms_order_per_page', false );
	}
} else {
	/* For Multisite */
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->prefix}blogs" );
	$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $blogid ) {
		switch_to_blog( $blogid );
		$blogusers = get_users(
			array(
				'blog_id' => $blogid,
				'fields' => array( 'ID' ),
			)
		);
		foreach ( $blogusers as $user ) {
			delete_user_option( $user->ID, 'bulkmailsend', false );
			delete_user_option( $user->ID, 'bulkmailsenduser_search_text', false );
			delete_user_option( $user->ID, 'bulkmailsendorder_search_text', false );
			delete_user_option( $user->ID, 'bulkmailsenduser_role', false );
			delete_user_option( $user->ID, 'bulkmailsendorder_product', false );
			delete_user_option( $user->ID, 'bms_user_per_page', false );
			delete_user_option( $user->ID, 'bms_order_per_page', false );
		}
	}
	switch_to_blog( $original_blog_id );
}
