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

				$product_fields_config_all = get_post_meta( $product_id, '_capfp_product_fields_config', true );

				if ( !empty( $product_fields_config_all ) && is_array( $product_fields_config_all ) ) {
					$product_fields_map = array();
					foreach($product_fields_config_all as $pfc) {
						if(isset($pfc['unique_key'])) {
							$product_fields_map[$pfc['unique_key']] = $pfc;
						}
					}

					foreach ( $submitted_fields as $unique_field_id => $submitted_value ) {
						if ( isset( $product_fields_map[ $unique_field_id ] ) ) {
							$field_config = $product_fields_map[ $unique_field_id ];

							$value_to_store = null;
							$display_value = '';
							$price = 0;
							$label = $field_config['label'];

							$is_visible_on_server = true;
							if (class_exists('CAPFP_Frontend')) {
								$frontend_checker = new CAPFP_Frontend();
								$all_submitted_values_for_check = isset($_POST['capfp_field']) ? wc_clean(wp_unslash($_POST['capfp_field'])) : array();
								if(method_exists($frontend_checker, 'is_field_conditionally_visible')){
									$is_visible_on_server = $frontend_checker->is_field_conditionally_visible( $field_config, $all_submitted_values_for_check, $product_fields_config_all );
								}
							}

							if (!$is_visible_on_server) {
								continue;
							}

							switch ( $field_config['type'] ) {
								case 'text':
									$value_to_store = sanitize_text_field( $submitted_value );
									$display_value = $value_to_store;
									break;
                                case 'number':
                                    if (is_numeric($submitted_value)) {
                                        $num_val = floatval($submitted_value);
                                        $min_val = isset($field_config['min']) && $field_config['min'] !== '' ? floatval($field_config['min']) : null;
                                        $max_val = isset($field_config['max']) && $field_config['max'] !== '' ? floatval($field_config['max']) : null;
                                        if ( ($min_val === null || $num_val >= $min_val) && ($max_val === null || $num_val <= $max_val) ) {
                                            $value_to_store = $num_val;
                                            $display_value = (string) $num_val;
                                        } else {
                                            $value_to_store = null;
                                        }
                                    } else if ($submitted_value === '' && !(isset($field_config['required']) && $field_config['required'] === 'yes')) {
										$value_to_store = '';
										$display_value = '';
									}
                                    break;
								case 'checkbox':
								case 'select':
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

							if ( $value_to_store !== null ) {
								if ($value_to_store === '' && isset($field_config['required']) && $field_config['required'] === 'yes' && $is_visible_on_server) {
									// Skip empty required fields
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

			$_product_id_for_price = $variation_id ? $variation_id : $product_id;
			$_product_for_price = wc_get_product($_product_id_for_price);
			if ($_product_for_price) {
				$cart_item_data['capfp_original_base_price'] = floatval($_product_for_price->get_price('edit'));
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
			static $processed_cart_item_keys = array();

			if ( isset( $cart_item['key'] ) && isset( $processed_cart_item_keys[ $cart_item['key'] ] ) ) {
				return $item_data;
			}

			if ( isset( $cart_item['capfp_custom_fields'] ) && is_array( $cart_item['capfp_custom_fields'] ) ) {
				$meta_added_for_this_item_in_this_call = false;
				foreach ( $cart_item['capfp_custom_fields'] as $field_unique_id => $field_data ) {
					$display_text = $field_data['display'];
					if ( isset($field_data['price']) && $field_data['price'] > 0 ) {
						$display_text .= ' (' . wc_price( $field_data['price'] ) . ')';
					}

					$item_data[] = array(
						'key'     => $field_data['label'],
						'value'   => $field_data['value'],
						'display' => $display_text,
					);
					$meta_added_for_this_item_in_this_call = true;
				}

				if ( $meta_added_for_this_item_in_this_call && isset( $cart_item['key'] ) ) {
					$processed_cart_item_keys[ $cart_item['key'] ] = true;
				}
			}
			return $item_data;
		}

		/**
		 * Calculate custom fields prices and add to cart item total.
		 */
		public function calculate_custom_fields_prices( $cart_obj ) {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}

			foreach ( $cart_obj->get_cart() as $cart_item_key => $cart_item ) {
				$base_price = null;
				if ( isset( $cart_item['capfp_original_base_price'] ) ) {
					$base_price = floatval( $cart_item['capfp_original_base_price'] );
				} else {
					$product = wc_get_product( $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'] );
					if ( $product ) {
						$base_price = floatval( $product->get_price('edit') );
                        if (isset($cart_obj->cart_contents[ $cart_item_key ])) { // Ensure item still exists
						    $cart_obj->cart_contents[ $cart_item_key ]['capfp_original_base_price'] = $base_price;
                        }
					}
				}

				if ( $base_price === null ) {
					continue;
				}

				$additional_price_to_add = 0;
				if ( isset( $cart_item['capfp_custom_fields'] ) && is_array( $cart_item['capfp_custom_fields'] ) ) {
					foreach ( $cart_item['capfp_custom_fields'] as $field_data ) {
						if ( isset( $field_data['price'] ) && is_numeric( $field_data['price'] ) ) {
							$additional_price_to_add += floatval( $field_data['price'] );
						}
					}
				}

				$new_price = $base_price + $additional_price_to_add;
				$cart_item['data']->set_price( $new_price );
			}
		}
	}
}
