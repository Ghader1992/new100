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

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Frontend hooks and filters will go here.
			// For example, displaying fields on the product page.
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_custom_fields' ), 10 );
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_custom_fields' ), 10, 3 );
            add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'replace_loop_add_to_cart_button' ), 10, 2 );

		}

		/**
		 * Display custom fields on the single product page.
		 */
		public function display_custom_fields() {
			global $product;
			$product_id = $product->get_id();

			// Check if Lock Installation is enabled for this product.
			$lock_installation_enabled = get_post_meta( $product_id, '_capfp_lock_installation_enabled', true );
			$lock_installation_cost = get_post_meta( $product_id, '_capfp_lock_installation_cost', true );

			if ( 'yes' === $lock_installation_enabled ) {
				echo '<div class="capfp-custom-fields-wrapper">';
				echo '<div class="capfp-field-group">';
				echo '<label for="capfp_lock_installation_service">';
				esc_html_e( 'Lock Installation Service', 'capfp' );
				if ( ! empty( $lock_installation_cost ) && floatval( $lock_installation_cost ) > 0 ) {
					// translators: %s: price
					echo ' (' . sprintf( esc_html__( 'additional cost of %s', 'capfp' ), wc_price( $lock_installation_cost ) ) . ')';
				}
				echo '</label>';
				echo '<input type="checkbox" id="capfp_lock_installation_service" name="capfp_field_lock_installation_service" value="yes">';
				echo '</div>';
				// Placeholder for more fields
				echo '</div>';
				wp_nonce_field( 'capfp_add_to_cart_custom_fields', 'capfp_custom_fields_nonce_frontend' );
			}

			// Placeholder: Here we would loop through all configured custom fields for the product
			// and render them based on their type (text, number, select etc.)
			// and also implement conditional logic display using JavaScript.
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
            // Verify nonce if fields were submitted
            if ( isset( $_POST['capfp_field_lock_installation_service'] ) || count( $this->get_posted_custom_fields_data() > 0 ) ) { // check if any of our fields are posted
                if ( ! isset( $_POST['capfp_custom_fields_nonce_frontend'] ) || ! wp_verify_nonce( sanitize_key( $_POST['capfp_custom_fields_nonce_frontend'] ), 'capfp_add_to_cart_custom_fields' ) ) {
                    wc_add_notice( __( 'Security check failed. Please try again.', 'capfp' ), 'error' );
                    return false;
                }
            }

			// Example validation for the lock installation service
			$lock_installation_enabled = get_post_meta( $product_id, '_capfp_lock_installation_enabled', true );

			// If lock installation is enabled and was expected, but not selected (e.g. if it was made mandatory later)
			// This is a basic example. More complex validation for other field types (required, number format etc.) will be added.
			// For now, we are only checking if it's enabled. The selection is optional by default.

			// Placeholder: Loop through all configured fields for this product.
			// Check for 'required' fields and validate their data types.
			// Check conditional logic constraints.
			// Example:
			// $configured_fields = get_post_meta( $product_id, '_capfp_custom_fields_data', true );
			// if ( is_array( $configured_fields ) ) {
			// foreach ( $configured_fields as $field_key => $field_config ) {
			// if ( $field_config['required'] && empty( $_POST[ 'capfp_field_' . $field_key ] ) ) ) {
			// wc_add_notice( sprintf( __( '%s is a required field.', 'capfp' ), $field_config['label'] ), 'error' );
			// $passed = false;
			// }
			// // Add more validation for type (number, select options etc.)
			// }
			// }
			return $passed;
		}

        /**
         * Replace add to cart button on archives if product has custom fields.
         *
         * @param string $html Original HTML.
         * @param object $product Product object.
         * @return string Modified HTML.
         */
        public function replace_loop_add_to_cart_button( $html, $product ) {
            if ( $product ) {
                $product_id = $product->get_id();
                // Check if lock installation is enabled OR if there are other generic custom fields
                $lock_installation_enabled = get_post_meta( $product_id, '_capfp_lock_installation_enabled', true );
                // $generic_custom_fields = get_post_meta( $product_id, '_capfp_custom_fields_data', true ); // Placeholder

                if ( 'yes' === $lock_installation_enabled /* || !empty($generic_custom_fields) */ ) {
                    $button_text = __( 'Select options', 'capfp' );
                    $link        = $product->get_permalink();
                    $html        = sprintf( '<a href="%s" class="button product_type_%s">%s</a>', esc_url( $link ), esc_attr( $product->get_type() ), esc_html( $button_text ) );
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
