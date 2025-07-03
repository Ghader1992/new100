<?php
/**
 * Custom Advanced Product Fields PRO Cart
 *
 * @package CustomAdvancedProductFieldsPro/Cart
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CAPFP_Cart' ) ) {

	/**
	 * CAPFP_Cart class.
	 */
	class CAPFP_Cart {

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Cart hooks and filters will go here.
			add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_custom_fields_to_cart_item' ), 10, 3 );
			add_filter( 'woocommerce_get_item_data', array( $this, 'display_custom_fields_in_cart' ), 10, 2 );
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'calculate_custom_fields_prices' ), 20 );
		}

		/**
		 * Add custom field data to cart item.
		 *
		 * @param array $cart_item_data Existing cart item data.
		 * @param int   $product_id     Product ID.
		 * @param int   $variation_id   Variation ID.
		 * @return array Modified cart item data.
		 */
		public function add_custom_fields_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
			$posted_custom_fields = array();

			// Check for Lock Installation Service.
			if ( isset( $_POST['capfp_field_lock_installation_service'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['capfp_field_lock_installation_service'] ) ) ) {
				$lock_cost = get_post_meta( $product_id, '_capfp_lock_installation_cost', true );
				$lock_cost_float = !empty($lock_cost) ? floatval($lock_cost) : 0;

				$posted_custom_fields['lock_installation_service'] = array(
					'label' => __( 'Lock Installation Service', 'capfp' ),
					'value' => __( 'Yes', 'capfp' ),
					'price' => $lock_cost_float,
                    'display' => __( 'Lock Installation Service', 'capfp' ) . ( $lock_cost_float > 0 ? ' (' . wc_price( $lock_cost_float ) . ')' : '' ),
				);
			}

			// Placeholder: Loop through all configured custom fields for the product.
			// $configured_fields = get_post_meta( $product_id, '_capfp_custom_fields_data', true );
			// if( is_array( $configured_fields ) ) {
			//     foreach ( $configured_fields as $field_key => $field_config ) {
			//         if ( isset( $_POST[ 'capfp_field_' . $field_key ] ) ) {
			//             $value = sanitize_text_field( wp_unslash( $_POST[ 'capfp_field_' . $field_key ] ) );
			//             $price = 0;
			//             $display_value = $value;
			//             // If it's a select field, get option price and display label
			//             if ( $field_config['type'] === 'select' && isset($field_config['options'][$value]) ) {
            //                  $price = isset($field_config['options'][$value]['price']) ? floatval($field_config['options'][$value]['price']) : 0;
            //                  $display_value = $field_config['options'][$value]['label'];
            //              }
			//             $posted_custom_fields[ $field_key ] = array(
            //                  'label' => $field_config['label'],
            //                  'value' => $value,
            //                  'price' => $price,
            //                  'display' => $display_value . ($price > 0 ? ' (' . wc_price($price) . ')' : ''),
            //              );
			//         }
			//     }
			// }


			if ( ! empty( $posted_custom_fields ) ) {
				$cart_item_data['capfp_custom_fields'] = $posted_custom_fields;
			}

			return $cart_item_data;
		}

		/**
		 * Display custom field data in cart.
		 *
		 * @param array $item_data Default item data.
		 * @param array $cart_item Cart item data.
		 * @return array Modified item data.
		 */
		public function display_custom_fields_in_cart( $item_data, $cart_item ) {
			if ( isset( $cart_item['capfp_custom_fields'] ) && is_array( $cart_item['capfp_custom_fields'] ) ) {
				foreach ( $cart_item['capfp_custom_fields'] as $field_key => $field_data ) {
					$item_data[] = array(
						'key'     => $field_data['label'],
						'value'   => $field_data['value'], // This might need to be $field_data['display'] if you want the price part here too
						'display' => $field_data['display'], // Use the pre-formatted display string
					);
				}
			}
			return $item_data;
		}

		/**
		 * Calculate custom fields prices and add to cart item total.
		 *
		 * @param WC_Cart $cart Cart object.
		 */
		public function calculate_custom_fields_prices( $cart ) {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}

			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				$additional_price = 0;
				if ( isset( $cart_item['capfp_custom_fields'] ) && is_array( $cart_item['capfp_custom_fields'] ) ) {
					foreach ( $cart_item['capfp_custom_fields'] as $field_key => $field_data ) {
						if ( isset( $field_data['price'] ) && is_numeric( $field_data['price'] ) ) {
							$additional_price += floatval( $field_data['price'] );
						}
					}
				}

				if ( $additional_price > 0 ) {
					$original_price = $cart_item['data']->get_price( 'edit' ); // Get base price
                    $new_price = $original_price + $additional_price;
					$cart_item['data']->set_price( $new_price );
				}
			}
		}
	}
}
