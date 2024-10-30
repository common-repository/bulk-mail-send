<?php
/**
 * Plugin Name: Bulk Mail Send
 * Plugin URI:  https://wordpress.org/plugins/bulk-mail-send/
 * Description: Send bulk emails to registered users and orders.
 * Version:     1.12
 * Author:      Katsushi Kawamori
 * Author URI:  https://riverforest-wp.info/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bulk-mail-send
 * WC requires at least: 3.0
 * WC tested up to: 8.6
 *
 * @package Bulk Mail Send
 */

/*
	Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if ( ! class_exists( 'BulkMailSend' ) ) {
	require_once __DIR__ . '/lib/class-bulkmailsend.php';
}
if ( ! class_exists( 'BulkMailSendAdmin' ) ) {
	require_once __DIR__ . '/lib/class-bulkmailsendadmin.php';
}

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
