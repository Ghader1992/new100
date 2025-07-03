<?php
/**
 * Custom Advanced Product Fields PRO Order
 *
 * @package CustomAdvancedProductFieldsPro/Order
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CAPFP_Order' ) ) {

	/**
	 * CAPFP_Order class.
	 */
	class CAPFP_Order {

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Order hooks and filters will go here.
			add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_custom_fields_to_order_items' ), 10, 4 );
			// The display of meta in order details (admin and customer emails/view order page) is often handled by default
            // by WooCommerce if the meta key doesn't start with '_'.
            // However, we can use woocommerce_order_item_meta_display to customize if needed.
		}

		/**
		 * Add custom field data to order line items.
		 *
		 * @param WC_Order_Item_Product $item          Order item object.
		 * @param string                $cart_item_key Cart item key.
		 * @param array                 $values        Cart item values.
		 * @param WC_Order              $order         Order object.
		 */
		public function add_custom_fields_to_order_items( $item, $cart_item_key, $values, $order ) {
			if ( isset( $values['capfp_custom_fields'] ) && is_array( $values['capfp_custom_fields'] ) ) {
				foreach ( $values['capfp_custom_fields'] as $field_unique_id => $field_data ) {
					$display_text_for_order = $field_data['display'];
					if ( $field_data['price'] > 0 ) {
						$display_text_for_order .= ' (' . wc_price( $field_data['price'] ) . ')';
					}
					// Storing with unique ID in meta key can be useful for later retrieval if needed,
                    // but for display, the label is better.
                    // Meta key: $field_data['label'] (Original Field Label)
                    // Meta value: $display_text_for_order
					$item->add_meta_data( $field_data['label'], $display_text_for_order, true );
				}
			}
		}

		// Example of how to customize display if needed:
		/*
		add_filter( 'woocommerce_order_item_meta_display', array( $this, 'display_custom_fields_in_order_item_meta' ), 10, 3 );
		public function display_custom_fields_in_order_item_meta($html, $item, $args) {
			// You can iterate through $item->get_meta_data() and customize HTML output
			// if the default display is not sufficient.
			return $html;
		}
		*/
	}
}
