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
			$processed_custom_fields_data = array();

			if ( isset( $_POST['capfp_field'] ) && is_array( $_POST['capfp_field'] ) ) {
				$submitted_fields = wc_clean( wp_unslash( $_POST['capfp_field'] ) );

				// We need the full configuration to get labels and prices
				$all_field_groups_config = get_option( 'capfp_field_groups', array() );
				$product_group_ids = get_post_meta( $product_id, '_capfp_selected_field_group_ids', true );

				if ( !empty($product_group_ids) && is_array($product_group_ids) && !empty($all_field_groups_config) ) {

					foreach ( $submitted_fields as $unique_field_id => $submitted_value ) {
						// $unique_field_id is "GROUPID_FIELDID"
						// Find the corresponding field configuration
						$field_config = null;
						$group_id_for_field = '';
						$field_id_for_field = '';

						// Extract group and field ID from unique_field_id
                        // This assumes unique_field_id format is 'groupid-fieldid' as generated in settings sanitization
                        // and then used in frontend display 'GROUPID_FIELDID' (hyphen vs underscore)
                        // Let's standardize on the sanitized ID format from options.
                        // The frontend uses $group_config['id'] . '_' . $field_config['id']

                        // Find which group this field belongs to
                        foreach ($all_field_groups_config as $group_cfg) {
                            if (isset($group_cfg['id']) && isset($group_cfg['fields']) && is_array($group_cfg['fields'])) {
                                foreach ($group_cfg['fields'] as $f_cfg) {
                                    if (isset($f_cfg['id']) && ($group_cfg['id'] . '_' . $f_cfg['id']) === $unique_field_id) {
                                        $field_config = $f_cfg;
                                        break 2; // Found field, break both loops
                                    }
                                }
                            }
                        }

						if ( $field_config ) {
							$value_to_store = '';
							$display_value = '';
							$price = 0;
							$label = $field_config['label'];

							switch ( $field_config['type'] ) {
								case 'text':
									$value_to_store = sanitize_text_field( $submitted_value );
									$display_value = $value_to_store;
									break;
                                case 'number':
                                    $value_to_store = is_numeric( $submitted_value ) ? floatval( $submitted_value ) : sanitize_text_field( $submitted_value );
                                    $display_value = (string) $value_to_store; // Display as string
                                    // Price for number field itself is not added here, only if options have prices.
                                    // If number field was to influence price directly (e.g. price per unit), that's different logic.
                                    break;
								case 'checkbox': // Single checkbox representing the field
								case 'select':
									if ( !empty($field_config['options']) && is_array($field_config['options']) ) {
                                        foreach($field_config['options'] as $option_cfg) {
                                            if (isset($option_cfg['value']) && $option_cfg['value'] === $submitted_value) {
                                                $value_to_store = $option_cfg['value'];
                                                $display_value = $option_cfg['label'];
                                                $price = isset( $option_cfg['price'] ) ? floatval( $option_cfg['price'] ) : 0;
                                                break;
                                            }
                                        }
                                    }
									break;
							}

							// Ensure value_to_store is not null before saving, but allow "0" or empty string if that's the actual valid input
							if ( $value_to_store !== null && ($value_to_store !== '' || $field_config['type'] === 'text' || $field_config['type'] === 'number') ) {
								$processed_custom_fields_data[ $unique_field_id ] = array(
									'label'   => $label,
									'value'   => $value_to_store, // Raw value
									'display' => $display_value,  // User-friendly display value
									'price'   => $price,
                                    'field_id'=> $field_config['id'], // Original field ID within its group
                                    'group_id'=> $group_cfg['id'] // Group ID
								);
							}
						}
					}
				}
			}

			if ( ! empty( $processed_custom_fields_data ) ) {
				$cart_item_data['capfp_custom_fields'] = $processed_custom_fields_data;
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
				foreach ( $cart_item['capfp_custom_fields'] as $field_unique_id => $field_data ) {
					$display_text = $field_data['display'];
					if ( $field_data['price'] > 0 ) {
						$display_text .= ' (' . wc_price( $field_data['price'] ) . ')';
					}
					$item_data[] = array(
						'key'     => $field_data['label'],
						'value'   => $field_data['value'], // Raw value stored
						'display' => $display_text,
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
