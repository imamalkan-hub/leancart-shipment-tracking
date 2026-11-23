<?php
/**
 * Plugin Name: LeanCart Shipment Tracking
 * Plugin URI:  https://poolresurfacingguide.com/
 * Description: Enter the carrier and tracking number in the order details. The information appears automatically on the "My Account → My Orders" page and in the shipping email.
 * Version:     1.0.0
 * Author:      LeanCart
 * Author URI:  https://leancart.dev
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: shipment-tracking
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEANCART_SHIPMENT_TRACKING_DEMO_SITE' ) ) {
	define( 'LEANCART_SHIPMENT_TRACKING_DEMO_SITE', 'https://poolresurfacingguide.com' );
}

require_once __DIR__ . '/includes/class-leancart-framework.php';

final class LeanCart_Shipment_Tracking {

	const SLUG = 'shipment-tracking';

	public static function init() {
		LeanCart_Framework::boot( array(
			'slug'    => self::SLUG,
			'name'    => 'LeanCart Shipment Tracking',
			'version' => '1.0.0',
			'file'    => __FILE__,
			'site'    => LEANCART_SHIPMENT_TRACKING_DEMO_SITE,
		) );

		// WooCommerce may load after these plugins; defer the check to runtime.
		add_action( 'admin_notices', array( __CLASS__, 'wc_notice' ) );

		load_plugin_textdomain( 'shipment-tracking', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		add_action( 'leancart_' . self::SLUG . '_settings_fields', array( __CLASS__, 'fields' ) );

		// WooCommerce may load after this plugin; hooks are registered once WC is ready.
		if ( LeanCart_Framework::is_wc() ) {
			self::hooks();
		} else {
			add_action( 'woocommerce_loaded', array( __CLASS__, 'hooks' ) );
		}
	}

	public static function wc_notice() {
		if ( LeanCart_Framework::is_wc() ) { return; }
		echo '<div class="notice notice-warning"><p><strong>' . esc_html( 'LeanCart Shipment Tracking' ) . '</strong>: ';
		esc_html_e( 'This plugin requires WooCommerce to be active.', 'shipment-tracking' );
		echo '</p></div>';
	}

	public static function fields() {
		LeanCart_Framework::field_text( 'carriers', __('Carriers', 'shipment-tracking'), __('Separate with commas: Yurtiçi Kargo,Aras Kargo,Sürat Kargo', 'shipment-tracking'), 'text' );
	}


	public static function hooks() {
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( __CLASS__, 'admin_fields' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'customer' ) );
		add_action( 'woocommerce_email_after_order_table', array( __CLASS__, 'email' ), 10, 4 );
	}

	public static function admin_fields( $order ) {
		$list_id = 'lc_carriers_list';
		$carriers = array_filter( array_map( 'trim', explode( ',', (string) LeanCart_Framework::setting( 'carriers' ) ) ) );
		echo '<datalist id="' . esc_attr( $list_id ) . '">';
		foreach ( $carriers as $carrier ) { echo '<option value="' . esc_attr( $carrier ) . '"></option>'; }
		echo '</datalist>';
		echo '<div class="lc-tracking" style="padding:10px;border:1px solid #dcdcde;margin-top:10px"><h3>' . esc_html__( 'Shipment tracking', 'shipment-tracking' ) . '</h3>';
		echo '<p class="form-field"><label>' . esc_html__( 'Carrier', 'shipment-tracking' ) . '<input type="text" list="' . esc_attr( $list_id ) . '" name="_lc_carrier" value="' . esc_attr( $order->get_meta( '_lc_carrier' ) ) . '" /></label></p>';
		echo '<p class="form-field"><label>' . esc_html__( 'Tracking number', 'shipment-tracking' ) . '<input type="text" name="_lc_tracking" value="' . esc_attr( $order->get_meta( '_lc_tracking' ) ) . '" /></label></p></div>';
	}

	public static function save( $id ) {
		if ( ! current_user_can( 'edit_shop_orders', $id ) ) { return; }
		if ( isset( $_POST['woocommerce_meta_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) { return; }
		$order = wc_get_order( $id );
		if ( ! $order ) { return; }
		foreach ( array( '_lc_carrier', '_lc_tracking' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) { $order->update_meta_data( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ); }
		}
		$order->save();
	}

	public static function customer( $order ) {
		$carrier = $order->get_meta( '_lc_carrier' );
		$tracking = $order->get_meta( '_lc_tracking' );
		if ( ! $carrier && ! $tracking ) { return; }
		echo '<p><strong>' . esc_html__( 'Carrier:', 'shipment-tracking' ) . '</strong> ' . esc_html( $carrier ) . ' &mdash; ' . esc_html( $tracking ) . '</p>';
	}

	public static function email( $order, $sent_to_admin, $plain_text, $email ) {
		$carrier = $order->get_meta( '_lc_carrier' );
		$tracking = $order->get_meta( '_lc_tracking' );
		if ( ! $carrier && ! $tracking ) { return; }
		if ( $plain_text ) {
			echo "\n" . esc_html( sprintf( __( 'Carrier: %s — %s', 'shipment-tracking' ), $carrier, $tracking ) ) . "\n";
			return;
		}
		self::customer( $order );
	}


}

LeanCart_Shipment_Tracking::init();

