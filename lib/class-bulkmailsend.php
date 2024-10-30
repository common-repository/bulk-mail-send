<?php
/**
 * Bulk Mail Send
 *
 * @package    Bulk Mail Send
 * @subpackage BulkMailSend Main function
/*  Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
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

$bulkmailsend = new BulkMailSend();

/** ==================================================
 * Class Main function
 *
 * @since 1.00
 */
class BulkMailSend {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'bms_user_role_filter_form', array( $this, 'role_filter_form' ) );
		add_action( 'bms_order_product_filter_form', array( $this, 'product_filter_form' ) );
		add_action( 'bms_user_per_page_set', array( $this, 'user_per_page_set' ), 10, 1 );
		add_action( 'bms_order_per_page_set', array( $this, 'order_per_page_set' ), 10, 1 );
	}

	/** ==================================================
	 * Role filter form
	 *
	 * @since 1.00
	 */
	public function role_filter_form() {

		$scriptname = admin_url( 'admin.php?page=bulkmailsend-user-selector' );
		?>
		<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
		<?php
		wp_nonce_field( 'bmsu_user_filter', 'bulk_media_send_user_filter' );
		?>
		<select name="role">
		<?php
		$role = get_user_option( 'bulkmailsenduser_role', get_current_user_id() );
		global $wp_roles;
		$all_roles = $wp_roles->roles;
		$select = false;
		foreach ( $all_roles as $key => $value ) {
			if ( $role == $key ) {
				$select = true;
				?>
				<option value="<?php echo esc_attr( $key ); ?>" selected><?php echo esc_html( $value['name'] ); ?></option>
				<?php
			} else {
				?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value['name'] ); ?></option>
				<?php
			}
		}
		if ( $select ) {
			?>
			<option value=""><?php esc_html_e( 'All roles', 'bulk-mail-send' ); ?></option>
			<?php
		} else {
			?>
			<option value="" selected><?php esc_html_e( 'All roles', 'bulk-mail-send' ); ?></option>
			<?php
		}
		?>
		</select>

		<?php
		$search_text = get_user_option( 'bulkmailsenduser_search_text', get_current_user_id() );
		if ( ! $search_text ) {
			?>
			<input style="vertical-align: middle;" name="search_text" type="text" value="" placeholder="<?php echo esc_attr__( 'Search' ); ?>">
			<?php
		} else {
			?>
			<input style="vertical-align: middle;" name="search_text" type="text" value="<?php echo esc_attr( $search_text ); ?>">
			<?php
		}

		submit_button( __( 'Search' ), 'large', 'bulk-mail-send-user-filter', false );
		?>
		</form>
		<?php
	}

	/** ==================================================
	 * Product filter form
	 *
	 * @since 1.00
	 */
	public function product_filter_form() {

		$scriptname = admin_url( 'admin.php?page=bulkmailsend-order-selector' );
		?>
		<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
		<?php
		wp_nonce_field( 'bmsu_order_filter', 'bulk_media_send_order_filter' );
		?>
		<select name="product">
		<?php
		$order = get_user_option( 'bulkmailsendorder_product', get_current_user_id() );
		global $wpdb;
		$all_products = $wpdb->get_col(
			"
			SELECT post_title
			FROM {$wpdb->prefix}posts
			WHERE post_type = 'product'
			"
		);
		$select = false;
		foreach ( $all_products as $product ) {
			if ( $order == $product ) {
				$select = true;
				?>
				<option value="<?php echo esc_attr( $product ); ?>" selected><?php echo esc_html( $product ); ?></option>
				<?php
			} else {
				?>
				<option value="<?php echo esc_attr( $product ); ?>"><?php echo esc_html( $product ); ?></option>
				<?php
			}
		}
		if ( $select ) {
			?>
			<option value=""><?php esc_html_e( 'All products', 'woocommerce' ); ?></option>
			<?php
		} else {
			?>
			<option value="" selected><?php esc_html_e( 'All products', 'woocommerce' ); ?></option>
			<?php
		}
		?>
		</select>

		<?php
		$search_text = get_user_option( 'bulkmailsendorder_search_text', get_current_user_id() );
		if ( ! $search_text ) {
			?>
			<input style="vertical-align: middle;" name="search_text" type="text" value="" placeholder="<?php echo esc_attr__( 'Search' ); ?>">
			<?php
		} else {
			?>
			<input style="vertical-align: middle;" name="search_text" type="text" value="<?php echo esc_attr( $search_text ); ?>">
			<?php
		}

		submit_button( __( 'Search' ), 'large', 'bulk-mail-send-order-filter', false );
		?>
		</form>
		<?php
	}

	/** ==================================================
	 * Per page input form for user
	 *
	 * @param int $uid  user ID.
	 * @since 1.05
	 */
	public function user_per_page_set( $uid ) {

		?>
		<div style="margin: 0px; text-align: right;">
			<?php esc_html_e( 'Number of items per page:' ); ?><input type="number" step="1" min="1" max="9999" style="width: 80px;" name="per_page" value="<?php echo esc_attr( get_user_option( 'bms_user_per_page', $uid ) ); ?>" form="selectmailsend_user_forms" />
			<?php submit_button( __( 'Change' ), 'large', 'user_per_page_change', false, array( 'form' => 'selectmailsend_user_forms' ) ); ?>
		</div>
		<?php
	}

	/** ==================================================
	 * Per page input form for order
	 *
	 * @param int $uid  user ID.
	 * @since 1.05
	 */
	public function order_per_page_set( $uid ) {

		?>
		<div style="margin: 0px; text-align: right;">
			<?php esc_html_e( 'Number of items per page:' ); ?><input type="number" step="1" min="1" max="9999" style="width: 80px;" name="per_page" value="<?php echo esc_attr( get_user_option( 'bms_order_per_page', $uid ) ); ?>" form="selectmailsend_order_forms" />
			<?php submit_button( __( 'Change' ), 'large', 'order_per_page_change', false, array( 'form' => 'selectmailsend_order_forms' ) ); ?>
		</div>
		<?php
	}
}


