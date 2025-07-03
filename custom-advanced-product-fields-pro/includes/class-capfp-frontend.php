<?php
/**
 * Custom Advanced Product Fields PRO Frontend
 *
 * @package CustomAdvancedProductFieldsPro/Frontend
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CAPFP_Frontend' ) ) {

    /**
     * CAPFP_Frontend class.
     */
    class CAPFP_Frontend {

		private static $fields_displayed_this_request = false;

        /**
         * Constructor.
         */
        public function __construct() {
            // Frontend hooks and filters will go here.
            add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_custom_fields' ), 10 );
            add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_custom_fields' ), 10, 3 );
            add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'replace_loop_add_to_cart_button' ), 10, 2 );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
        }

        /**
         * Enqueue frontend styles.
         */
        public function enqueue_frontend_styles() {
            // Only enqueue on single product pages
            if ( is_product() ) {
                wp_enqueue_style(
                    'capfp-frontend-styles', // Handle
                    CAPFP_PLUGIN_URL . 'assets/css/capfp-frontend.css', // Path to CSS file
                    array(), // Dependencies
                    CAPFP_VERSION // Version
                );
            }
        }

        /**
         * Display custom fields on the single product page.
         */
        public function display_custom_fields() {
			if (self::$fields_displayed_this_request) {
				return;
			}

            global $product;
            if ( ! $product ) {
                return;
            }
            $product_id = $product->get_id();

            $product_fields_config = get_post_meta( $product_id, '_capfp_product_fields_config', true );

            if ( empty( $product_fields_config ) || ! is_array( $product_fields_config ) ) {
                return;
            }

            // Prepare data for JS
            $js_fields_config = array();

            echo '<div class="capfp-custom-fields-wrapper">';
            wp_nonce_field( 'capfp_add_to_cart_custom_fields', 'capfp_custom_fields_nonce_frontend' );

            foreach ( $product_fields_config as $field_index => $field_config ) {
                // unique_key is already $group_id . '_' . $field_id
                $field_unique_key = $field_config['unique_key'];
                $field_id_attr = 'capfp_field_' . $field_unique_key;
                $field_name_attr = 'capfp_field[' . $field_unique_key . ']';

                // Add to JS config
                $js_fields_config[$field_unique_key] = array(
                    'conditions' => isset($field_config['conditions']) ? $field_config['conditions'] : array(),
                    'type'       => $field_config['type']
                );

                // Add a wrapper div for each field for easy show/hide
                echo '<div id="capfp_field_container_' . esc_attr( $field_unique_key ) . '" class="capfp-field-item-container capfp-field-group form-row" data-field-key="' . esc_attr( $field_unique_key ) . '">';
                echo '<label for="' . esc_attr( $field_id_attr ) . '">' . esc_html( $field_config['label'] );
                if ( isset( $field_config['required'] ) && 'yes' === $field_config['required'] ) {
                    echo ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';
                }
                echo '</label>';

                switch ( $field_config['type'] ) {
                    case 'text':
                        echo '<input type="text" id="' . esc_attr( $field_id_attr ) . '" name="' . esc_attr( $field_name_attr ) . '" class="input-text capfp-input" />';
                        break;
                    case 'number':
                        $min_attr = isset( $field_config['min'] ) && $field_config['min'] !== '' ? ' min="' . esc_attr( $field_config['min'] ) . '"' : '';
                        $max_attr = isset( $field_config['max'] ) && $field_config['max'] !== '' ? ' max="' . esc_attr( $field_config['max'] ) . '"' : '';
                        echo '<input type="number" id="' . esc_attr( $field_id_attr ) . '" name="' . esc_attr( $field_name_attr ) . '" class="input-text capfp-input"' . $min_attr . $max_attr . ' step="any" />';
                        break;
					case 'checkbox':
						$checkbox_value = 'yes'; // Default value if no options
						$display_option_label = '';

                        if ( ! empty( $field_config['options'] ) && is_array( $field_config['options'] ) ) {
							$option = $field_config['options'][0]; // Use the first option for value and potential price
							if (isset($option['value'])) {
								$checkbox_value = $option['value'];
							}
							// Only display option label if it's different from the main field label
							if (isset($option['label']) && $option['label'] !== $field_config['label']) {
								$display_option_label = $option['label'];
                            }
							$price = isset( $option['price'] ) ? floatval( $option['price'] ) : 0;
                        } else {
							// No options defined, it's a simple 'yes' value checkbox, no separate option label or price from option
							$price = 0;
						}

						echo '<input type="checkbox" id="' . esc_attr( $field_id_attr ) . '" name="' . esc_attr( $field_name_attr ) . '" value="' . esc_attr( $checkbox_value ) . '" class="input-checkbox capfp-input" />';

						if ( !empty($display_option_label) ) {
							echo ' <label for="'. esc_attr( $field_id_attr ) .'" class="checkbox-label">' . esc_html( $display_option_label ) . '</label>';
						}

						if ( $price > 0 ) {
							echo ' <span class="capfp-option-price"> (+ ' . wc_price( $price ) . ')</span>';
						}
                        break;
                    case 'select':
                        if ( ! empty( $field_config['options'] ) && is_array( $field_config['options'] ) ) {
                            echo '<select id="' . esc_attr( $field_id_attr ) . '" name="' . esc_attr( $field_name_attr ) . '" class="capfp-select capfp-input">';
                            if ( ! (isset( $field_config['required'] ) && 'yes' === $field_config['required']) ) {
                                echo '<option value="">' . esc_html__( '--- Select ---', 'capfp' ) . '</option>';
                            }
                            foreach ( $field_config['options'] as $option_idx => $option ) {
                                $price_suffix = '';
                                if ( ! empty( $option['price'] ) && floatval( $option['price'] ) > 0 ) {
                                    $price_suffix = ' (+ ' . wc_price( floatval( $option['price'] ) ) . ')';
                                }
                                echo '<option value="' . esc_attr( $option['value'] ) . '" data-price="' . esc_attr( !empty($option['price']) ? floatval($option['price']) : 0 ) . '">' . esc_html( $option['label'] ) . $price_suffix . '</option>'; // Removed esc_html() from $price_suffix
                            }
                            echo '</select>';
                        }
                        break;
                }
                // Display field description if available
                // if (!empty($field_config['description'])) {
                //     echo '<span class="description">' . esc_html($field_config['description']) . '</span>';
                // }
                echo '</div>'; // .capfp-field-item-container
            }
            echo '</div>'; // .capfp-custom-fields-wrapper

            // Localize script data for conditional logic
            wp_register_script( 'capfp-frontend-logic', CAPFP_PLUGIN_URL . 'assets/js/frontend-logic.js', array( 'jquery' ), CAPFP_VERSION, true );
            wp_localize_script( 'capfp-frontend-logic', 'capfp_frontend_data', array(
                'fields_config' => $js_fields_config,
                'field_prefix'  => 'capfp_field_' // Prefix for input IDs/names
            ) );
            wp_enqueue_script( 'capfp-frontend-logic' );

			self::$fields_displayed_this_request = true;
        }

        /**
         * Validate custom fields before adding to cart.
         *
         * @param bool $passed Validation status.
         * @param int  $product_id Product ID.
         * @param int  $quantity Quantity.
         * @return bool
         */
        public function validate_custom_fields( $passed, $product_id, $quantity ) {
            if ( isset( $_POST['capfp_field'] ) && ( ! isset( $_POST['capfp_custom_fields_nonce_frontend'] ) || ! wp_verify_nonce( sanitize_key( $_POST['capfp_custom_fields_nonce_frontend'] ), 'capfp_add_to_cart_custom_fields' ) ) ) {
                wc_add_notice( __( 'Security check failed. Please try again.', 'capfp' ), 'error' );
                return false;
            }

            $submitted_fields_data = isset( $_POST['capfp_field'] ) ? wc_clean( wp_unslash( $_POST['capfp_field'] ) ) : array();

            $product_fields_config = get_post_meta( $product_id, '_capfp_product_fields_config', true );

            if ( empty( $product_fields_config ) || ! is_array( $product_fields_config ) ) {
                return $passed;
            }

            $submitted_values_map = array();
            if (isset($_POST['capfp_field']) && is_array($_POST['capfp_field'])) {
                foreach (wc_clean(wp_unslash($_POST['capfp_field'])) as $unique_key => $value) {
                    $submitted_values_map[$unique_key] = $value;
                }
            }

            foreach ( $product_fields_config as $field_config ) {
                $unique_key = $field_config['unique_key'];
                $is_field_required = isset( $field_config['required'] ) && 'yes' === $field_config['required'];
                $submitted_value_for_current_field = isset( $submitted_values_map[ $unique_key ] ) ? $submitted_values_map[ $unique_key ] : null;

                $is_conditionally_visible = $this->is_field_conditionally_visible( $field_config, $submitted_values_map, $product_fields_config );

                if ( ! $is_conditionally_visible ) {
                    continue;
                }

                if ( $is_field_required && ( is_null( $submitted_value_for_current_field ) || $submitted_value_for_current_field === '' ) ) {
                    wc_add_notice( sprintf( __( '%s is a required field.', 'capfp' ), esc_html( $field_config['label'] ) ), 'error' );
                    $passed = false;
                }

                if ( ! is_null( $submitted_value_for_current_field ) && $submitted_value_for_current_field !== '' ) {
                    switch ( $field_config['type'] ) {
                        case 'text':
                            // Already sanitized by wc_clean.
                            break;
                        case 'number':
                            if ( ! is_numeric( $submitted_value_for_current_field ) ) {
                                wc_add_notice( sprintf( __( '%s must be a number.', 'capfp' ), esc_html( $field_config['label'] ) ), 'error' );
                                $passed = false;
                            } else {
                                $num_val = floatval($submitted_value_for_current_field);
                                if ( isset( $field_config['min'] ) && $field_config['min'] !== '' && $num_val < floatval($field_config['min']) ) {
                                    wc_add_notice( sprintf( __( '%1$s cannot be less than %2$s.', 'capfp' ), esc_html( $field_config['label'] ), $field_config['min'] ), 'error' );
                                    $passed = false;
                                }
                                if ( isset( $field_config['max'] ) && $field_config['max'] !== '' && $num_val > floatval($field_config['max']) ) {
                                    wc_add_notice( sprintf( __( '%1$s cannot be greater than %2$s.', 'capfp' ), esc_html( $field_config['label'] ), $field_config['max'] ), 'error' );
                                    $passed = false;
                                }
                            }
                            break;
                        case 'checkbox':
                        case 'select':
                            $valid_option_found = false;
                            if ( ! empty( $field_config['options'] ) && is_array( $field_config['options'] ) ) {
                                foreach ( $field_config['options'] as $option_config ) {
                                    if ( isset( $option_config['value'] ) && $option_config['value'] === $submitted_value_for_current_field ) {
                                        $valid_option_found = true;
                                        break;
                                    }
                                }
                            } elseif ($field_config['type'] === 'checkbox' && $submitted_value_for_current_field === "yes" && empty($field_config['options'])) {
                                $valid_option_found = true;
                            }
                            if ( ! $valid_option_found ) {
                                wc_add_notice( sprintf( __( 'Invalid option selected for %s.', 'capfp' ), esc_html( $field_config['label'] ) ), 'error' );
                                $passed = false;
                            }
                            break;
                    }
                }
            }
            return $passed;
        }

        /**
         * Check if a field should be visible based on its conditions and current submitted values.
         *
         * @param array $field_config
         * @param array $submitted_values_map
         * @param array $all_product_fields
         * @return bool
         */
        public function is_field_conditionally_visible( $field_config, $submitted_values_map, $all_product_fields ) {
            // Note: This public visibility is a temporary change to allow CAPFP_Cart to call it.
            // Consider refactoring to a shared helper or a different approach for inter-class dependencies.
            if ( ! isset( $field_config['conditions'] ) || empty( $field_config['conditions'] ) ) {
                return true;
            }

            foreach ( $field_config['conditions'] as $condition ) {
                if ( empty( $condition['field'] ) || empty( $condition['operator'] ) ) {
                    continue;
                }

                $dependent_field_key = $condition['field'];
                $dependent_field_value = isset( $submitted_values_map[ $dependent_field_key ] ) ? $submitted_values_map[ $dependent_field_key ] : null;

                $condition_target_value = $condition['value'];
                $operator = $condition['operator'];

                $match = false;
                switch ( $operator ) {
                    case 'is':
                        $match = ( $dependent_field_value === $condition_target_value );
                        break;
                    case 'is_not':
                        $match = ( $dependent_field_value !== $condition_target_value );
                        break;
                }

                if ( ! $match ) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Replace add to cart button on archives if product has custom fields.
         *
         * @param string $html Original HTML.
         * @param object $product Product object.
         * @return string Modified HTML.
         */
        public function replace_loop_add_to_cart_button( $html, $product ) {
            if ( $product && ( $product->is_type('simple') || $product->is_type('variable') ) ) {
                $product_id = $product->get_id();
                $selected_group_ids = get_post_meta( $product_id, '_capfp_selected_field_group_ids', true );

                if ( ! empty( $selected_group_ids ) && is_array( $selected_group_ids ) ) {
                    $all_field_groups_config = get_option( 'capfp_field_groups', array() );
                    $has_active_fields = false;
                    if (!empty($all_field_groups_config)) {
                        foreach($selected_group_ids as $s_id) {
                            foreach($all_field_groups_config as $g_cfg) {
                                if (isset($g_cfg['id']) && $g_cfg['id'] === $s_id && !empty($g_cfg['fields'])) {
                                    $has_active_fields = true;
                                    break 2;
                                }
                            }
                        }
                    }

                    if ($has_active_fields) {
                        $button_text = apply_filters( 'capfp_loop_select_options_button_text', __( 'Select options', 'capfp' ), $product );
                        $link        = $product->get_permalink();
                        $html        = sprintf( '<a href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" class="button %s product_type_%s">%s</a>',
                                            esc_url( $link ),
                                            esc_attr( $product->get_id() ),
                                            esc_attr( $product->get_sku() ),
                                            $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
                                            esc_attr( $product->get_type() ),
                                            esc_html( $button_text )
                                        );
                    }
                }
            }
            return $html;
        }

        /**
         * Helper to get posted custom field data.
         *
         * @return array
         */
        private function get_posted_custom_fields_data() {
            $posted_data = array();
            if ( ! empty( $_POST ) ) {
                foreach ( $_POST as $key => $value ) {
                    if ( strpos( $key, 'capfp_field_' ) === 0 ) {
                        $field_key = str_replace( 'capfp_field_', '', $key );
                        $posted_data[ $field_key ] = wc_clean( wp_unslash( $value ) );
                    }
                }
            }
            return $posted_data;
        }
    }
}
