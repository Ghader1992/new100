<?php
/**
 * Custom Advanced Product Fields PRO Admin
 *
 * @package CustomAdvancedProductFieldsPro/Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CAPFP_Admin' ) ) {

	/**
	 * CAPFP_Admin class.
	 */
	class CAPFP_Admin {

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Admin hooks and filters will go here.
			// For example, adding the custom product tab.
			add_action( 'woocommerce_product_data_tabs', array( $this, 'add_custom_fields_product_tab' ) );
			add_action( 'woocommerce_product_data_panels', array( $this, 'custom_fields_product_tab_content' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_custom_fields_data' ) );
		}

		/**
		 * Add Custom Fields product tab.
		 *
		 * @param array $tabs Existing tabs.
		 * @return array Modified tabs.
		 */
		public function add_custom_fields_product_tab( $tabs ) {
			$tabs['capfp_custom_fields'] = array(
				'label'    => __( 'Custom Fields', 'capfp' ),
				'target'   => 'capfp_custom_fields_data',
				'class'    => array( 'show_if_simple', 'show_if_variable' ), // Show for simple and variable products.
				'priority' => 75, // Adjust priority as needed.
			);
			return $tabs;
		}

		/**
		 * Content for the Custom Fields product tab.
		 */
		public function custom_fields_product_tab_content() {
			global $post;
			$product_id = $post->ID;
			?>
			<div id="capfp_custom_fields_data" class="panel woocommerce_options_panel">
				<div class="options_group">
					<p class="form-field">
						<label for="capfp_lock_installation_enabled"><?php esc_html_e( 'Enable Lock Installation Service', 'capfp' ); ?></label>
						<input type="checkbox" class="checkbox" name="capfp_lock_installation_enabled" id="capfp_lock_installation_enabled" value="yes" <?php checked( get_post_meta( $product_id, '_capfp_lock_installation_enabled', true ), 'yes' ); ?>>
						<span class="description"><?php esc_html_e( 'Check this to offer lock installation service for this product.', 'capfp' ); ?></span>
					</p>
					<p class="form-field">
						<label for="capfp_lock_installation_cost"><?php esc_html_e( 'Lock Installation Cost (SAR)', 'capfp' ); ?></label>
						<input type="number" class="short wc_input_price" name="capfp_lock_installation_cost" id="capfp_lock_installation_cost" value="<?php echo esc_attr( get_post_meta( $product_id, '_capfp_lock_installation_cost', true ) ); ?>" placeholder="0.00" step="any" min="0">
						<span class="description"><?php esc_html_e( 'Enter the additional cost for the lock installation service.', 'capfp' ); ?></span>
					</p>
					<hr>
					<p><strong><?php esc_html_e( 'More custom field options will be available here.', 'capfp' ); ?></strong></p>
					<?php wp_nonce_field( 'capfp_save_custom_fields', 'capfp_custom_fields_nonce' ); ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Save custom fields data.
		 *
		 * @param int $product_id Product ID.
		 */
		public function save_custom_fields_data( $product_id ) {
			// Verify nonce.
			if ( ! isset( $_POST['capfp_custom_fields_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['capfp_custom_fields_nonce'] ), 'capfp_save_custom_fields' ) ) {
				return;
			}

			// Save Lock Installation Enabled.
			$lock_installation_enabled = isset( $_POST['capfp_lock_installation_enabled'] ) ? 'yes' : 'no';
			update_post_meta( $product_id, '_capfp_lock_installation_enabled', $lock_installation_enabled );

			// Save Lock Installation Cost.
			if ( isset( $_POST['capfp_lock_installation_cost'] ) ) {
				$lock_installation_cost = wc_clean( wp_unslash( $_POST['capfp_lock_installation_cost'] ) );
				update_post_meta( $product_id, '_capfp_lock_installation_cost', $lock_installation_cost === '' ? '' : wc_format_decimal( $lock_installation_cost ) );
			}

			// Placeholder for saving more complex custom fields.
			// $custom_fields_data = isset( $_POST['_capfp_custom_fields'] ) ? wc_clean( wp_unslash( $_POST['_capfp_custom_fields'] ) ) : array();
			// update_post_meta( $product_id, '_capfp_custom_fields_data', $custom_fields_data );
		}
	}
}
