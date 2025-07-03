<?php
/**
 * Custom Advanced Product Fields PRO Settings Page
 *
 * @package CustomAdvancedProductFieldsPro/Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CAPFP_Settings_Page' ) ) {

	/**
	 * CAPFP_Settings_Page class.
	 */
	class CAPFP_Settings_Page {

		/**
		 * Option group.
		 *
		 * @var string
		 */
		private $option_group = 'capfp_settings';

		/**
		 * Option name.
		 *
		 * @var string
		 */
		private $option_name = 'capfp_field_groups';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}

		/**
		 * Add settings page to admin menu.
		 */
		public function add_settings_page() {
			add_submenu_page(
				'woocommerce', // Parent slug (WooCommerce menu)
				__( 'Product Custom Fields', 'capfp' ), // Page title
				__( 'Product Custom Fields', 'capfp' ), // Menu title
				'manage_woocommerce', // Capability
				'capfp-custom-fields-settings', // Menu slug
				array( $this, 'render_settings_page' ) // Callback function
			);
		}

		/**
		 * Register plugin settings.
		 */
		public function register_settings() {
			register_setting(
				$this->option_group,
				$this->option_name,
				array( $this, 'sanitize_settings' )
			);
		}

		/**
		 * Render the settings page.
		 */
		public function render_settings_page() {
			?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form method="post" action="options.php">
					<?php
					settings_fields( $this->option_group );
					do_settings_sections( 'capfp-custom-fields-settings' ); // Page slug for settings sections
					?>

					<h2><?php esc_html_e( 'Field Groups', 'capfp' ); ?></h2>
					<p><?php esc_html_e( 'Define reusable groups of custom fields that can be applied to products.', 'capfp' ); ?></p>

					<div id="capfp-field-groups-wrapper">
						<?php
						$field_groups = get_option( $this->option_name, array() );
						if ( empty( $field_groups ) ) {
							$field_groups[] = array( 'name' => '' ); // Start with one empty group
						}

						$field_groups_data = get_option( $this->option_name, array() );
						$group_idx = 0;

						if ( empty( $field_groups_data ) ) {
							// Add a default empty group if none exist to show the UI structure
							$field_groups_data[] = array('name' => '', 'fields' => array());
						}

						foreach ( $field_groups_data as $group_key => $group_data ) {
							$this->render_field_group_template( $group_key, $group_data );
							$group_idx = $group_key + 1; // Ensure next new group has a unique index
						}
						?>
					</div>

					<p>
						<button type="button" id="capfp-add-field-group" class="button button-secondary">
							<span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Add New Field Group', 'capfp' ); ?>
						</button>
					</p>

					<?php submit_button( __( 'Save Changes', 'capfp' ) ); ?>
				</form>
			</div>

			<!-- Underscore.js Templates -->
			<?php $this->include_js_templates(); ?>

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					let groupCount = <?php echo $group_idx; ?>;
					let capfp_option_name = '<?php echo esc_js($this->option_name); ?>';

					// Add Field Group
					$('#capfp-add-field-group').on('click', function() {
						const groupTemplate = wp.template('capfp-field-group');
						$('#capfp-field-groups-wrapper').append(groupTemplate({ group_index: groupCount, group_name_attr: capfp_option_name + '[' + groupCount + '][name]', group_fields_attr_prefix: capfp_option_name + '[' + groupCount + '][fields]' }));
						// Trigger change to ensure select2 is initialized if present in field template
						$('#capfp-field-groups-wrapper .capfp-field-group[data-index="' + groupCount + '"]').find('.capfp-field-type-select').change();
						groupCount++;
					});

					// Remove Field Group
					$('#capfp-field-groups-wrapper').on('click', '.capfp-remove-group', function() {
						$(this).closest('.capfp-field-group').remove();
					});

					// Add Field to Group
					$('#capfp-field-groups-wrapper').on('click', '.capfp-add-field', function() {
						const groupIndex = $(this).closest('.capfp-field-group').data('index');
						const fieldsWrapper = $(this).siblings('.capfp-fields-in-group-wrapper');
						const fieldCount = fieldsWrapper.find('.capfp-field-item').length;
						const fieldTemplate = wp.template('capfp-field-item');

						fieldsWrapper.append(fieldTemplate({
							group_index: groupIndex,
							field_index: fieldCount,
							field_attr_prefix: capfp_option_name + '[' + groupIndex + '][fields][' + fieldCount + ']'
						}));
						// Trigger change to ensure select2 is initialized
						fieldsWrapper.find('.capfp-field-item[data-field-index="' + fieldCount + '"]').find('.capfp-field-type-select').change();
					});

					// Remove Field from Group
					$('#capfp-field-groups-wrapper').on('click', '.capfp-remove-field', function() {
						$(this).closest('.capfp-field-item').remove();
					});

					// Add Option to Checkbox/Select Field
					$('#capfp-field-groups-wrapper').on('click', '.capfp-add-option', function() {
						const fieldItem = $(this).closest('.capfp-field-item');
						const groupIndex = fieldItem.closest('.capfp-field-group').data('index');
						const fieldIndex = fieldItem.data('field-index');
						const optionsWrapper = fieldItem.find('.capfp-field-options-wrapper');
						const optionCount = optionsWrapper.find('.capfp-field-option-item').length;
						const optionTemplate = wp.template('capfp-field-option-item');

						optionsWrapper.append(optionTemplate({
							group_index: groupIndex,
							field_index: fieldIndex,
							option_index: optionCount,
							option_attr_prefix: capfp_option_name + '[' + groupIndex + '][fields][' + fieldIndex + '][options][' + optionCount + ']'
						}));
					});

					// Remove Option
					$('#capfp-field-groups-wrapper').on('click', '.capfp-remove-option', function() {
						$(this).closest('.capfp-field-option-item').remove();
					});

					// Show/Hide Options based on Field Type
					$('#capfp-field-groups-wrapper').on('change', '.capfp-field-type-select', function() {
						const fieldItem = $(this).closest('.capfp-field-item');
						const fieldType = $(this).val();

						// Hide all type-specific attribute sections first
						fieldItem.find('.field-specific-attributes').hide();
						// Show the one for the selected type
						fieldItem.find('.field-specific-attributes[data-type="' + fieldType + '"]').show();

						const optionsDiv = fieldItem.find('.capfp-field-options-container');
						if (fieldType === 'checkbox' || fieldType === 'select' || fieldType === 'radio') {
							optionsDiv.show();
						} else {
							optionsDiv.hide();
						}
					});
					// Trigger initial change for existing fields on page load
					$('.capfp-field-type-select').change();

				});
			</script>
			<style>
				.capfp-field-group, .capfp-field-item, .capfp-field-option-item { border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 15px; background-color: #fff; }
				.capfp-field-group h3, .capfp-field-item h4 { margin-top: 0; }
				.capfp-field-item, .capfp-field-option-item { margin-left: 20px; background-color: #f9f9f9;}
				.capfp-field-option-item { margin-left: 40px; background-color: #f0f0f0;}
				.capfp-field-group > p, .capfp-field-item > div > p, .capfp-field-item > p.field-specific-attributes, .capfp-field-option-item > p { margin-bottom: 10px; }
				.capfp-field-item label, .capfp-field-option-item label { display: inline-block; width: 120px; vertical-align: top; }
				.capfp-field-item input[type="text"], .capfp-field-item input[type="number"], .capfp-field-item select, .capfp-field-option-item input[type="text"] { width: 25em; margin-right: 10px;}
				.capfp-field-item .field-specific-attributes input[type="number"] { width: 10em; }
				.capfp-remove-group, .capfp-remove-field, .capfp-remove-option { float: right; color: #a00; border-color: #a00; background: #f2dede; }
				.capfp-remove-group:hover, .capfp-remove-field:hover, .capfp-remove-option:hover { border-color: #600; color: #600; background: #e4b9b9;}
				hr { border-top: 1px solid #ddd; border-bottom: none; margin-top: 20px; margin-bottom: 20px;}
			</style>
			<?php
		}

        /**
		 * Renders a single field group template.
		 *
		 * @param int   $group_key  The index of the group.
		 * @param array $group_data The data for the group.
		 */
		private function render_field_group_template( $group_key, $group_data ) {
			$group_name = isset( $group_data['name'] ) ? $group_data['name'] : '';
			$fields     = isset( $group_data['fields'] ) && is_array( $group_data['fields'] ) ? $group_data['fields'] : array();
			?>
			<script type="text/template" id="tmpl-capfp-field-group-<?php echo esc_attr( $group_key ); ?>">
				<?php echo $this->get_field_group_html( $group_key, $group_name, $fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</script>
			<div class="capfp-field-group" data-index="<?php echo esc_attr( $group_key ); ?>">
				<?php echo $this->get_field_group_html( $group_key, $group_name, $fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php
		}

		/**
		 * Gets the HTML for a field group.
		 */
		private function get_field_group_html( $group_key, $group_name, $fields_data ) {
			ob_start();
			?>
			<div class="capfp-group-header">
				<h3><?php esc_html_e( 'Field Group', 'capfp' ); ?></h3>
				<button type="button" class="button capfp-remove-group"><?php esc_html_e( 'Remove Group', 'capfp' ); ?></button>
			</div>
			<p>
				<label for="capfp_group_name_<?php echo esc_attr( $group_key ); ?>"><?php esc_html_e( 'Group Name:', 'capfp' ); ?></label>
				<input type="text" class="regular-text"
					   id="capfp_group_name_<?php echo esc_attr( $group_key ); ?>"
					   name="<?php echo esc_attr( $this->option_name . '[' . $group_key . '][name]' ); ?>"
					   value="<?php echo esc_attr( $group_name ); ?>"
					   placeholder="<?php esc_attr_e( 'e.g., Installation Options', 'capfp' ); ?>" />
			</p>
			<hr>
			<h4><?php esc_html_e( 'Fields in this Group:', 'capfp' ); ?></h4>
			<div class="capfp-fields-in-group-wrapper">
				<?php
				$field_idx = 0;
				if ( ! empty( $fields_data ) ) {
					foreach ( $fields_data as $field_key => $field_item ) {
						echo $this->get_field_item_html( $group_key, $field_key, $field_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$field_idx = $field_key + 1;
					}
				}
				?>
			</div>
			<button type="button" class="button capfp-add-field">
				<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Field to Group', 'capfp' ); ?>
			</button>
			<?php
			return ob_get_clean();
		}


		/**
		 * Gets the HTML for a single field item.
		 */
		private function get_field_item_html( $group_key, $field_key, $field_data ) {
			ob_start();
			$field_label = isset( $field_data['label'] ) ? $field_data['label'] : '';
			$field_type  = isset( $field_data['type'] ) ? $field_data['type'] : 'text';
			$field_required = isset( $field_data['required'] ) ? $field_data['required'] : 'no';
			$options     = isset( $field_data['options'] ) && is_array( $field_data['options'] ) ? $field_data['options'] : array();
			$field_attr_prefix = $this->option_name . '[' . $group_key . '][fields][' . $field_key . ']';
			?>
			<div class="capfp-field-item" data-field-index="<?php echo esc_attr( $field_key ); ?>">
				<div class="capfp-field-header">
					<h5><?php esc_html_e( 'Field', 'capfp' ); ?></h5>
					<button type="button" class="button capfp-remove-field"><?php esc_html_e( 'Remove Field', 'capfp' ); ?></button>
				</div>
				<div>
					<p>
						<label for="<?php echo esc_attr( $field_attr_prefix . '[label]' ); ?>"><?php esc_html_e( 'Label:', 'capfp' ); ?></label>
						<input type="text" id="<?php echo esc_attr( $field_attr_prefix . '[label]' ); ?>"
							name="<?php echo esc_attr( $field_attr_prefix . '[label]' ); ?>"
							value="<?php echo esc_attr( $field_label ); ?>"
							placeholder="<?php esc_attr_e( 'e.g., Select Color', 'capfp' ); ?>" />
					</p>
					<p>
						<label for="<?php echo esc_attr( $field_attr_prefix . '[type]' ); ?>"><?php esc_html_e( 'Type:', 'capfp' ); ?></label>
						<select class="capfp-field-type-select" id="<?php echo esc_attr( $field_attr_prefix . '[type]' ); ?>" name="<?php echo esc_attr( $field_attr_prefix . '[type]' ); ?>">
							<option value="text" <?php selected( $field_type, 'text' ); ?>><?php esc_html_e( 'Text', 'capfp' ); ?></option>
							<option value="number" <?php selected( $field_type, 'number' ); ?>><?php esc_html_e( 'Number', 'capfp' ); ?></option>
							<option value="checkbox" <?php selected( $field_type, 'checkbox' ); ?>><?php esc_html_e( 'Checkbox', 'capfp' ); ?></option>
							<option value="select" <?php selected( $field_type, 'select' ); ?>><?php esc_html_e( 'Select', 'capfp' ); ?></option>
						</select>
					</p>
					<p class="field-specific-attributes" data-type="number" style="<?php echo esc_attr( $field_type === 'number' ? '' : 'display:none;' ); ?>">
						<label for="<?php echo esc_attr( $field_attr_prefix . '[min]' ); ?>"><?php esc_html_e( 'Min Value:', 'capfp' ); ?></label>
						<input type="number" step="any" id="<?php echo esc_attr( $field_attr_prefix . '[min]' ); ?>"
							   name="<?php echo esc_attr( $field_attr_prefix . '[min]' ); ?>"
							   value="<?php echo esc_attr( isset($field_data['min']) ? $field_data['min'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Optional', 'capfp' ); ?>" />
						<label for="<?php echo esc_attr( $field_attr_prefix . '[max]' ); ?>"><?php esc_html_e( 'Max Value:', 'capfp' ); ?></label>
						<input type="number" step="any" id="<?php echo esc_attr( $field_attr_prefix . '[max]' ); ?>"
							   name="<?php echo esc_attr( $field_attr_prefix . '[max]' ); ?>"
							   value="<?php echo esc_attr( isset($field_data['max']) ? $field_data['max'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Optional', 'capfp' ); ?>" />
					</p>
					<p>
						<label for="<?php echo esc_attr( $field_attr_prefix . '[required]' ); ?>"><?php esc_html_e( 'Required:', 'capfp' ); ?></label>
						<input type="checkbox" id="<?php echo esc_attr( $field_attr_prefix . '[required]' ); ?>"
							name="<?php echo esc_attr( $field_attr_prefix . '[required]' ); ?>"
							value="yes" <?php checked( $field_required, 'yes' ); ?> />
					</p>
				</div>
				<div class="capfp-field-options-container" style="<?php echo esc_attr( ( $field_type === 'checkbox' || $field_type === 'select' ) ? '' : 'display:none;' ); ?>">
					<h4><?php esc_html_e( 'Options:', 'capfp' ); ?></h4>
					<div class="capfp-field-options-wrapper">
						<?php
						$opt_idx = 0;
						if ( ! empty( $options ) ) {
							foreach ( $options as $option_key => $option_item ) {
								echo $this->get_field_option_html( $group_key, $field_key, $option_key, $option_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$opt_idx = $option_key + 1;
							}
						}
						?>
					</div>
					<button type="button" class="button capfp-add-option">
						<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Option', 'capfp' ); ?>
					</button>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Gets the HTML for a single field option item.
		 */
		private function get_field_option_html( $group_key, $field_key, $option_key, $option_data ) {
			ob_start();
			$option_label = isset( $option_data['label'] ) ? $option_data['label'] : '';
			$option_value = isset( $option_data['value'] ) ? $option_data['value'] : '';
			$option_price = isset( $option_data['price'] ) ? $option_data['price'] : '';
			$option_attr_prefix = $this->option_name . '[' . $group_key . '][fields][' . $field_key . '][options][' . $option_key . ']';
			?>
			<div class="capfp-field-option-item" data-option-index="<?php echo esc_attr( $option_key ); ?>">
				<button type="button" class="button capfp-remove-option"><?php esc_html_e( 'Remove Option', 'capfp' ); ?></button>
				<p>
					<label for="<?php echo esc_attr( $option_attr_prefix . '[label]' ); ?>"><?php esc_html_e( 'Opt Label:', 'capfp' ); ?></label>
					<input type="text" id="<?php echo esc_attr( $option_attr_prefix . '[label]' ); ?>"
						   name="<?php echo esc_attr( $option_attr_prefix . '[label]' ); ?>"
						   value="<?php echo esc_attr( $option_label ); ?>" placeholder="<?php esc_attr_e( 'e.g., Yes', 'capfp' ); ?>" />
				</p>
				<p>
					<label for="<?php echo esc_attr( $option_attr_prefix . '[value]' ); ?>"><?php esc_html_e( 'Opt Value:', 'capfp' ); ?></label>
					<input type="text" id="<?php echo esc_attr( $option_attr_prefix . '[value]' ); ?>"
						   name="<?php echo esc_attr( $option_attr_prefix . '[value]' ); ?>"
						   value="<?php echo esc_attr( $option_value ); ?>" placeholder="<?php esc_attr_e( 'e.g., yes_value', 'capfp' ); ?>" />
				</p>
				<p>
					<label for="<?php echo esc_attr( $option_attr_prefix . '[price]' ); ?>"><?php esc_html_e( 'Opt Price:', 'capfp' ); ?></label>
					<input type="number" step="any" min="0" id="<?php echo esc_attr( $option_attr_prefix . '[price]' ); ?>"
						   name="<?php echo esc_attr( $option_attr_prefix . '[price]' ); ?>"
						   value="<?php echo esc_attr( $option_price ); ?>" placeholder="<?php esc_attr_e( 'e.g., 10.00 (optional)', 'capfp' ); ?>" class="wc_input_price" />
				</p>
			</div>
			<?php
			return ob_get_clean();
		}


		/**
		 * Includes JS templates for repeater fields.
		 */
		private function include_js_templates() {
			?>
			<script type="text/template" id="tmpl-capfp-field-group">
				<div class="capfp-field-group" data-index="{{ data.group_index }}">
					<?php echo $this->get_field_group_html( '{{ data.group_index }}', '', array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</script>

			<script type="text/template" id="tmpl-capfp-field-item">
				<?php echo $this->get_field_item_html( '{{ data.group_index }}', '{{ data.field_index }}', array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</script>

			<script type="text/template" id="tmpl-capfp-field-option-item">
				<?php echo $this->get_field_option_html( '{{ data.group_index }}', '{{ data.field_index }}', '{{ data.option_index }}', array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</script>
			<?php
		}


		/**
		 * Sanitize settings data.
		 *
		 * @param array $input Raw input data.
		 * @return array Sanitized data.
		 */
		public function sanitize_settings( $input ) {
			$sanitized_input = array();
			if ( ! is_array( $input ) ) {
				return $sanitized_input;
			}

			foreach ( $input as $group_key => $group_data ) {
				if ( ! is_array( $group_data ) || empty( $group_data['name'] ) ) {
					continue;
				}

				$sanitized_group = array();
				$sanitized_group['name'] = sanitize_text_field( $group_data['name'] );
				$sanitized_group['id'] = sanitize_title( $group_data['name'] . '-' . $group_key ); // Auto-generate an ID

				if ( isset( $group_data['fields'] ) && is_array( $group_data['fields'] ) ) {
					$sanitized_fields = array();
					foreach ( $group_data['fields'] as $field_key => $field_data ) {
						if ( ! is_array( $field_data ) || empty( $field_data['label'] ) || empty( $field_data['type'] ) ) {
							continue;
						}
						$sanitized_field = array(
							'label'    => sanitize_text_field( $field_data['label'] ),
							'type'     => sanitize_text_field( $field_data['type'] ),
							'id'       => sanitize_title( $field_data['label'] . '-' . $field_key ), // Auto-generate field ID
							'required' => isset( $field_data['required'] ) && 'yes' === $field_data['required'] ? 'yes' : 'no',
						);

                        if ( $sanitized_field['type'] === 'number') {
                            if (isset($field_data['min']) && $field_data['min'] !== '') {
                                $sanitized_field['min'] = sanitize_text_field($field_data['min']); // Keep as text, HTML5 handles number interpretation
                            }
                            if (isset($field_data['max']) && $field_data['max'] !== '') {
                                $sanitized_field['max'] = sanitize_text_field($field_data['max']);
                            }
                        }

						if ( in_array( $sanitized_field['type'], array( 'checkbox', 'select', 'radio' ), true ) && isset( $field_data['options'] ) && is_array( $field_data['options'] ) ) {
							$sanitized_options = array();
							foreach ( $field_data['options'] as $option_key => $option_data ) {
								if ( ! is_array( $option_data ) || ! isset( $option_data['label'] ) || ! isset( $option_data['value'] ) ) {
									continue;
								}
								$sanitized_option = array(
									'label' => sanitize_text_field( $option_data['label'] ),
									'value' => sanitize_text_field( $option_data['value'] ),
									'price' => isset( $option_data['price'] ) ? wc_format_decimal( sanitize_text_field( $option_data['price'] ) ) : '',
								);
								if ( !empty($sanitized_option['label']) && !empty($sanitized_option['value']) ) {
									$sanitized_options[] = $sanitized_option;
								}
							}
							if (!empty($sanitized_options)) {
								$sanitized_field['options'] = $sanitized_options;
							}
						}
						$sanitized_fields[] = $sanitized_field;
					}
					if ( ! empty( $sanitized_fields ) ) {
						$sanitized_group['fields'] = $sanitized_fields;
					}
				}
				$sanitized_input[] = $sanitized_group;
			}
			return $sanitized_input;
		}
	}
}

// The main plugin class will handle instantiation.
