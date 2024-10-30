<?php
/**
 * Bulk Mail Send
 *
 * @package    Bulk Mail Send
 * @subpackage BulkMailSend Management screen
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

$bulkmailsendadmin = new BulkMailSendAdmin();

/** ==================================================
 * Management screen
 */
class BulkMailSendAdmin {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_action( 'admin_menu', array( $this, 'add_pages' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );

		if ( ! class_exists( 'TT_BulkMailSendUser_List_Table' ) ) {
			require_once __DIR__ . '/class-tt-bulkmailsenduser-list-table.php';
		}
		if ( ! class_exists( 'TT_BulkMailSendOrder_List_Table' ) ) {
			require_once __DIR__ . '/class-tt-bulkmailsendorder-list-table.php';
		}
	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'bulk-mail-send/bulkmailsend.php';
		}
		if ( $file == $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=bulkmailsend' ) . '">Bulk Mail Send</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=bulkmailsend-user-selector' ) . '">' . __( 'Select Users', 'bulk-mail-send' ) . '</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=bulkmailsend-order-selector' ) . '">' . __( 'Select Orders', 'bulk-mail-send' ) . '</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=bulkmailsend-settings' ) . '">' . __( 'Settings' ) . '</a>';
		}
		return $links;
	}

	/** ==================================================
	 * Add page
	 *
	 * @since 1.00
	 */
	public function add_pages() {
		add_menu_page(
			'Bulk Mail Send',
			'Bulk Mail Send',
			'manage_options',
			'bulkmailsend',
			array( $this, 'manage_page' ),
			'dashicons-email'
		);
		add_submenu_page(
			'bulkmailsend',
			__( 'Select Users', 'bulk-mail-send' ),
			__( 'Select Users', 'bulk-mail-send' ),
			'manage_options',
			'bulkmailsend-user-selector',
			array( $this, 'user_selector_page' )
		);
		add_submenu_page(
			'bulkmailsend',
			__( 'Select Orders', 'bulk-mail-send' ),
			__( 'Select Orders', 'bulk-mail-send' ),
			'manage_woocommerce',
			'bulkmailsend-order-selector',
			array( $this, 'order_selector_page' )
		);
		add_submenu_page(
			'bulkmailsend',
			__( 'Settings' ),
			__( 'Settings' ),
			'manage_options',
			'bulkmailsend-settings',
			array( $this, 'settings_page' )
		);
	}

	/** ==================================================
	 * Select Users
	 *
	 * @since 1.00
	 */
	public function user_selector_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated();

		$bulkmailsend_settings = get_user_option( 'bulkmailsend', get_current_user_id() );

		if ( isset( $_POST['bulk_mail_send_user'] ) && ! empty( $_POST['bulk_mail_send_user'] ) ) {
			if ( check_admin_referer( 'bmsu_send', 'bulk_mail_send_user' ) ) {
				if ( ! empty( $_POST['mails'] ) && ! empty( $_POST['subject'] ) && ! empty( $_POST['body'] ) ) {
					$mails = filter_var(
						wp_unslash( $_POST['mails'] ),
						FILTER_CALLBACK,
						array(
							'options' => function ( $value ) {
								return sanitize_email( $value );
							},
						)
					);
					$subject = sanitize_text_field( wp_unslash( $_POST['subject'] ) );
					$body = '<span style="white-space: pre-wrap;">' . sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) . '</span>';
					/* Mail Use HTML-Mails */
					add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
					foreach ( $mails as $key => $value ) {
						$body = str_replace( '%name%', $key, $body );
						$body = str_replace( '%signature%', $bulkmailsend_settings['signature'], $body );
						$mail_send = @wp_mail( $value, $subject, $body );
						if ( $mail_send ) {
							$success_mail[] = $value;
						} else {
							$error_mail[] = $value;
						}
					}
					/* Mail default */
					remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
					if ( ! empty( $success_mail ) ) {
						/* translators: Message */
						echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( sprintf( __( 'Send email[%1$s].', 'bulk-mail-send' ), implode( ',', $success_mail ) ) ) . '</li></ul></div>';
					}
					if ( ! empty( $error_mail ) ) {
						/* translators: Message */
						echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html( sprintf( __( 'Failed to send email[%1$s].', 'bulk-mail-send' ), implode( ',', $error_mail ) ) ) . '</li></ul></div>';
					}
				}
			}
		}

		$scriptname = admin_url( 'admin.php?page=bulkmailsend-user-selector' );

		?>
		<div class="wrap">

		<h2>Bulk Mail Send <a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-user-selector' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Select Users', 'bulk-mail-send' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-order-selector' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Select Orders', 'bulk-mail-send' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
		</h2>
		<div style="clear: both;"></div>

		<div class="wrap">
			<div style="margin: 5px; padding: 5px;">
				<details style="margin-bottom: 5px;">
				<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><?php echo esc_html( __( 'Email' ) . ' : ' . __( 'Please select multiple recipients below, and then compose and send an email.', 'bulk-mail-send' ) ); ?></summary>
					<div><?php esc_html_e( 'Subject', 'bulk-mail-send' ); ?> : <input type="text" name="subject" style="width: 500px;" form="selectmailsend_user_forms"></div>
					<div><?php esc_html_e( 'Body', 'bulk-mail-send' ); ?> : [ %name% => <?php esc_html_e( 'Name' ); ?> ] [ %signature% => <?php esc_html_e( 'Signature', 'bulk-mail-send' ); ?> ]</div>
					<div><textarea name="body" rows="20" cols="80" form="selectmailsend_user_forms"></textarea></div>
					<?php submit_button( __( 'Submit' ), 'primary', 'bulk_mail_send_user', false, array( 'form' => 'selectmailsend_user_forms' ) ); ?>
				</details>
				<?php
				$bulk_mail_send_user_list_table = new TT_BulkMailSendUser_List_Table();
				$bulk_mail_send_user_list_table->prepare_items();
				?>
				<form method="post" id="selectmailsend_user_forms" action="<?php echo esc_url( $scriptname ); ?>">
				<?php
				wp_nonce_field( 'bmsu_send', 'bulk_mail_send_user' );
				do_action( 'bms_user_per_page_set', get_current_user_id() );
				$bulk_mail_send_user_list_table->display();
				?>
				</form>
			</div>
		</div>

		<?php
	}

	/** ==================================================
	 * Select Orders
	 *
	 * @since 1.00
	 */
	public function order_selector_page() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated();

		$bulkmailsend_settings = get_user_option( 'bulkmailsend', get_current_user_id() );

		if ( isset( $_POST['bulk_mail_send_order'] ) && ! empty( $_POST['bulk_mail_send_order'] ) ) {
			if ( check_admin_referer( 'bmso_send', 'bulk_mail_send_order' ) ) {
				if ( ! empty( $_POST['mails'] ) && ! empty( $_POST['subject'] ) && ! empty( $_POST['body'] ) ) {
					$mails = filter_var(
						wp_unslash( $_POST['mails'] ),
						FILTER_CALLBACK,
						array(
							'options' => function ( $value ) {
								return sanitize_email( $value );
							},
						)
					);
					$subject = sanitize_text_field( wp_unslash( $_POST['subject'] ) );
					$body = '<span style="white-space: pre-wrap;">' . sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) . '</span>';
					/* Mail Use HTML-Mails */
					add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
					foreach ( $mails as $key => $value ) {
						$the_order = wc_get_order( $key );
						if ( is_a( $the_order, 'WC_Order_Refund' ) ) {
							$the_order = wc_get_order( $the_order->get_parent_id() );
						}
						$name = $the_order->get_billing_first_name() . ' ' . $the_order->get_billing_last_name();
						$body2 = str_replace( '%name%', $name, $body );
						$body2 = str_replace( '%signature%', $bulkmailsend_settings['signature'], $body2 );
						$mail_send = @wp_mail( $value, $subject, $body2 );
						if ( $mail_send ) {
							$success_mail[] = $value;
						} else {
							$error_mail[] = $value;
						}
					}
					/* Mail default */
					remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
					if ( ! empty( $success_mail ) ) {
						/* translators: Message */
						echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( sprintf( __( 'Send email[%1$s].', 'bulk-mail-send' ), implode( ',', $success_mail ) ) ) . '</li></ul></div>';
					}
					if ( ! empty( $error_mail ) ) {
						/* translators: Message */
						echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html( sprintf( __( 'Failed to send email[%1$s].', 'bulk-mail-send' ), implode( ',', $error_mail ) ) ) . '</li></ul></div>';
					}
				}
			}
		}

		$scriptname = admin_url( 'admin.php?page=bulkmailsend-order-selector' );

		?>
		<div class="wrap">

		<h2>Bulk Mail Send <a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-order-selector' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Select Orders', 'bulk-mail-send' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-user-selector' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Select Users', 'bulk-mail-send' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
		</h2>
		<div style="clear: both;"></div>
		<?php
		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_die( '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html__( 'WooCommerce is required.', 'bulk-mail-send' ) . '</li></ul></div>' );
		}
		?>
		<div class="wrap">
			<div style="margin: 5px; padding: 5px;">
				<details style="margin-bottom: 5px;">
				<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><?php echo esc_html( __( 'Email' ) . ' : ' . __( 'Please select multiple recipients below, and then compose and send an email.', 'bulk-mail-send' ) ); ?></summary>
					<div><?php esc_html_e( 'Subject', 'bulk-mail-send' ); ?> : <input type="text" name="subject" style="width: 500px;" form="selectmailsend_order_forms"></div>
					<div><?php esc_html_e( 'Body', 'bulk-mail-send' ); ?> : [ %name% => <?php esc_html_e( 'Name' ); ?> ] [ %signature% => <?php esc_html_e( 'Signature', 'bulk-mail-send' ); ?> ]</div>
					<div><textarea name="body" rows="20" cols="80" form="selectmailsend_order_forms"></textarea></div>
					<?php submit_button( __( 'Submit' ), 'primary', 'bulk_mail_send_order', false, array( 'form' => 'selectmailsend_order_forms' ) ); ?>
				</details>
				<?php
				$bulk_mail_send_order_list_table = new TT_BulkMailSendOrder_List_Table();
				$bulk_mail_send_order_list_table->prepare_items();
				?>
				<form method="post" id="selectmailsend_order_forms" action="<?php echo esc_url( $scriptname ); ?>">
				<?php
				wp_nonce_field( 'bmso_send', 'bulk_mail_send_order' );
				do_action( 'bms_order_per_page_set', get_current_user_id() );
				$bulk_mail_send_order_list_table->display();
				?>
				</form>
			</div>
		</div>

		<?php
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function settings_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated();

		$bulkmailsend_settings = get_user_option( 'bulkmailsend', get_current_user_id() );

		$scriptname = admin_url( 'admin.php?page=bulkmailsend-settings' );

		?>
		<div class="wrap">

		<h2>Bulk Mail Send <a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-settings' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Settings' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-user-selector' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Select Users', 'bulk-mail-send' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-order-selector' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Select Orders', 'bulk-mail-send' ); ?></a>
		</h2>
		<div style="clear: both;"></div>

			<div class="wrap">
				<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
				<?php wp_nonce_field( 'bms_settings', 'bulk_media_send_settings' ); ?>
				<details style="margin-bottom: 5px;" open>
				<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Signature', 'bulk-mail-send' ); ?></strong></summary>
					<div style="display: block;padding:5px 5px">
					<textarea name="signature" rows="8" cols="80"><?php echo( esc_html( $bulkmailsend_settings['signature'] ) ); ?></textarea>
					</div>
				</details>
				<?php submit_button( __( 'Save Changes' ), 'large', 'bulk-mail-send-settings-options-apply', true ); ?>
			</div>

		</div>
		<?php
	}

	/** ==================================================
	 * Main
	 *
	 * @since 1.00
	 */
	public function manage_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		?>

		<div class="wrap">

		<h2>Bulk Mail Send
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-user-selector' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Select Users', 'bulk-mail-send' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-order-selector' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Select Orders', 'bulk-mail-send' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulkmailsend-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
		</h2>
		<div style="clear: both;"></div>

		<h3><?php esc_html_e( 'Send bulk emails to registered users and orders.', 'bulk-mail-send' ); ?></h3>

		<?php $this->credit(); ?>

		</div>
		<?php
	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( __( 'https://wordpress.org/plugins/%s/faq', 'bulk-mail-send' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = __( 'https://shop.riverforest-wp.info/donate/', 'bulk-mail-send' );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'bulk-mail-send' ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;' ); ?></button>
		</div>

		<?php
	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 1.00
	 */
	private function options_updated() {

		$bulkmailsend_settings = get_user_option( 'bulkmailsend', get_current_user_id() );

		if ( isset( $_POST['bulk-mail-send-settings-options-apply'] ) && ! empty( $_POST['bulk-mail-send-settings-options-apply'] ) ) {
			if ( check_admin_referer( 'bms_settings', 'bulk_media_send_settings' ) ) {
				if ( ! empty( $_POST['signature'] ) ) {
					$bulkmailsend_settings['signature'] = sanitize_textarea_field( wp_unslash( $_POST['signature'] ) );
				} else {
					$bulkmailsend_settings['signature'] = null;
				}
				update_user_option( get_current_user_id(), 'bulkmailsend', $bulkmailsend_settings );
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings' ) . ' --> ' . __( 'Changes saved.' ) ) . '</li></ul></div>';
			}
		}

		if ( isset( $_POST['user_per_page_change'] ) && ! empty( $_POST['user_per_page_change'] ) ) {
			if ( check_admin_referer( 'bmsu_send', 'bulk_mail_send_user' ) ) {
				if ( ! empty( $_POST['per_page'] ) ) {
					$per_page = absint( $_POST['per_page'] );
					update_user_option( get_current_user_id(), 'bms_user_per_page', $per_page );
					echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings' ) . ' --> ' . __( 'Changes saved.' ) ) . '</li></ul></div>';
				}
			}
		}
		if ( isset( $_POST['order_per_page_change'] ) && ! empty( $_POST['order_per_page_change'] ) ) {
			if ( check_admin_referer( 'bmso_send', 'bulk_mail_send_order' ) ) {
				if ( ! empty( $_POST['per_page'] ) ) {
					$per_page = absint( $_POST['per_page'] );
					update_user_option( get_current_user_id(), 'bms_order_per_page', $per_page );
					echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings' ) . ' --> ' . __( 'Changes saved.' ) ) . '</li></ul></div>';
				}
			}
		}
	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		if ( ! get_user_option( 'bulkmailsend', get_current_user_id() ) ) {
			$bulkmailsend_tbl = array(
				'signature' => null,
			);
			update_user_option( get_current_user_id(), 'bulkmailsend', $bulkmailsend_tbl );
		} else {
			$bulkmailsend_settings = get_user_option( 'bulkmailsend', get_current_user_id() );
			if ( array_key_exists( 'user_per_page', $bulkmailsend_settings ) ) {
				unset( $bulkmailsend_settings['user_per_page'] );
				update_user_option( get_current_user_id(), 'bulkmailsend', $bulkmailsend_settings );
			}
			if ( array_key_exists( 'order_per_page', $bulkmailsend_settings ) ) {
				unset( $bulkmailsend_settings['order_per_page'] );
				update_user_option( get_current_user_id(), 'bulkmailsend', $bulkmailsend_settings );
			}
		}

		if ( ! get_user_option( 'bms_user_per_page', get_current_user_id() ) ) {
			update_user_option( get_current_user_id(), 'bms_user_per_page', 20 );
		}
		if ( ! get_user_option( 'bms_order_per_page', get_current_user_id() ) ) {
			update_user_option( get_current_user_id(), 'bms_order_per_page', 20 );
		}
	}

	/** ==================================================
	 * Mail content type
	 *
	 * @return string 'text/html'
	 * @since 1.00
	 */
	public function set_html_content_type() {
		return 'text/html';
	}
}


