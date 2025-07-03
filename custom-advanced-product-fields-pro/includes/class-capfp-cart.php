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

				// Get the product's specific field configuration
				$product_fields_config_all = get_post_meta( $product_id, '_capfp_product_fields_config', true );

				if ( !empty( $product_fields_config_all ) && is_array( $product_fields_config_all ) ) {
					// Create a map of product's fields by their unique_key for easier lookup
					$product_fields_map = array();
					foreach($product_fields_config_all as $pfc) {
						if(isset($pfc['unique_key'])) {
							$product_fields_map[$pfc['unique_key']] = $pfc;
						}
					}

					foreach ( $submitted_fields as $unique_field_id => $submitted_value ) {
						// Check if this submitted field is actually part of the product's configuration
						if ( isset( $product_fields_map[ $unique_field_id ] ) ) {
							$field_config = $product_fields_map[ $unique_field_id ]; // This is the config synced to the product

							$value_to_store = null; // Initialize to null
							$display_value = '';
							$price = 0;
							$label = $field_config['label'];

							// Server-side validation for visibility (important if JS is bypassed or fails)
							// This uses the full POST data to check conditions, not just $submitted_fields which is cleaned.
							$is_visible_on_server = true; // Assume visible unless conditions say otherwise
							if (class_exists('CAPFP_Frontend')) { // Check if class exists to avoid error if called in a weird context
								$frontend_checker = new CAPFP_Frontend(); // Temporary instance for visibility check
								// We need all submitted values for the visibility check, not just the current one.
								$all_submitted_values_for_check = isset($_POST['capfp_field']) ? wc_clean(wp_unslash($_POST['capfp_field'])) : array();
								$is_visible_on_server = $frontend_checker->is_field_conditionally_visible( $field_config, $all_submitted_values_for_check, $product_fields_config_all );
							}

							if (!$is_visible_on_server) {
								continue; // Don't process or add data for fields that should be hidden
							}


							switch ( $field_config['type'] ) {
								case 'text':
									$value_to_store = sanitize_text_field( $submitted_value );
									$display_value = $value_to_store;
									break;
                                case 'number':
                                    // Ensure it's numeric after sanitization and respect validation rules
                                    if (is_numeric($submitted_value)) {
                                        $num_val = floatval($submitted_value);
                                        $min_val = isset($field_config['min']) && $field_config['min'] !== '' ? floatval($field_config['min']) : null;
                                        $max_val = isset($field_config['max']) && $field_config['max'] !== '' ? floatval($field_config['max']) : null;

                                        if ( ($min_val === null || $num_val >= $min_val) && ($max_val === null || $num_val <= $max_val) ) {
                                            $value_to_store = $num_val;
                                            $display_value = (string) $num_val;
                                        } else {
                                            // Value was submitted but invalid (e.g. out of range), skip or handle as error?
                                            // For add_cart_item_data, we usually assume validation passed.
                                            // If it's here, it means validation might have been bypassed or needs re-check.
                                            // For now, we'll only store valid values.
                                            $value_to_store = null;
                                        }
                                    } else if ($submitted_value === '' && !(isset($field_config['required']) && $field_config['required'] === 'yes')) {
										// Allow empty non-required number field
										$value_to_store = '';
										$display_value = '';
									}
                                    break;
								case 'checkbox': // Single checkbox representing the field
								case 'select':
									// The $field_config['options'] here comes from the product meta,
									// which was synced from the global settings.
									if ( !empty($field_config['options']) && is_array($field_config['options']) ) {
                                        foreach($field_config['options'] as $option_cfg) {
                                            if (isset($option_cfg['value']) && $option_cfg['value'] === $submitted_value) {
                                                $value_to_store = $option_cfg['value'];
                                                $display_value = $option_cfg['label'];
                                                $price = isset( $option_cfg['price'] ) && $option_cfg['price'] !== '' ? floatval( $option_cfg['price'] ) : 0;
                                                break;
                                            }
                                        }
                                    }
									break;
							}

							// Ensure value_to_store is not null OR it's an intentionally empty allowed string (for non-required text/number)
							if ( $value_to_store !== null ) {
								if ($value_to_store === '' && isset($field_config['required']) && $field_config['required'] === 'yes' && $is_visible_on_server) {
									// This case should ideally be caught by 'woocommerce_add_to_cart_validation'
									// but as a safeguard, don't add empty required fields.
								} else {
									$processed_custom_fields_data[ $unique_field_id ] = array(
										'label'   => $label,
										'value'   => $value_to_store,
										'display' => $display_value,
										'price'   => $price,
	                                    'field_id'=> $field_config['original_field_id'],
	                                    'group_id'=> $field_config['original_group_id']
									);
								}
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
