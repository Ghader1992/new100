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
			add_action( 'woocommerce_product_data_tabs', array( $this, 'add_custom_fields_product_tab' ) );
			add_action( 'woocommerce_product_data_panels', array( $this, 'custom_fields_product_tab_content' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_custom_fields_data' ) );
		}

		/**
		 * Add Custom Fields product tab.
		 */
		public function add_custom_fields_product_tab( $tabs ) {
			$tabs['capfp_custom_fields'] = array(
				'label'    => __( 'Custom Fields', 'capfp' ),
				'target'   => 'capfp_custom_fields_data',
				'class'    => array( 'show_if_simple', 'show_if_variable' ),
				'priority' => 75,
			);
			return $tabs;
		}

		/**
		 * Content for the Custom Fields product tab.
		 */
		public function custom_fields_product_tab_content() {
			global $post, $thepostid, $product_object;
			$product_id = $thepostid;

			$all_global_field_groups = get_option( 'capfp_field_groups', array() );
			$product_fields_config_saved = get_post_meta( $product_id, '_capfp_product_fields_config', true );
			if ( ! is_array( $product_fields_config_saved ) ) {
				$product_fields_config_saved = array();
			}

            $active_group_ids_in_config = array();
            if(is_array($product_fields_config_saved)){
                foreach($product_fields_config_saved as $p_field_cfg){
                    if(isset($p_field_cfg['original_group_id'])) {
                        $active_group_ids_in_config[$p_field_cfg['original_group_id']] = true;
                    }
                }
            }
            $active_group_ids_in_config = array_keys($active_group_ids_in_config);
			?>
			<div id="capfp_custom_fields_data" class="panel woocommerce_options_panel">
				<?php wp_nonce_field( 'capfp_save_product_custom_fields', 'capfp_product_custom_fields_nonce' ); ?>
				<div class="options_group">
					<p class="form-field">
						<label for="capfp_select_field_groups_for_product"><?php esc_html_e( 'Apply Field Groups', 'capfp' ); ?></label>
						<?php if ( ! empty( $all_global_field_groups ) ) : ?>
							<select id="capfp_select_field_groups_for_product" name="_capfp_selected_field_group_ids_for_sync[]" multiple="multiple" class="wc-enhanced-select" style="width:100%;" data-placeholder="<?php esc_attr_e( 'Select field groups to apply/sync to this product...', 'capfp' ); ?>">
								<?php foreach ( $all_global_field_groups as $global_group ) : ?>
									<?php if ( isset( $global_group['id'] ) && isset( $global_group['name'] ) ) : ?>
										<option value="<?php echo esc_attr( $global_group['id'] ); ?>" <?php selected( in_array( $global_group['id'], $active_group_ids_in_config ), true ); ?>>
											<?php echo esc_html( $global_group['name'] ); ?>
										</option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
							<span class="description"><?php esc_html_e( 'Select groups to load fields from. Saving the product will sync these fields. You can then configure conditional logic below.', 'capfp' ); ?></span>
						<?php else : ?>
							<p>
								<?php printf( wp_kses_post( __( 'No field groups defined. Please <a href="%s">create global field groups</a> first.', 'capfp' ) ), esc_url( admin_url( 'admin.php?page=capfp-custom-fields-settings' ) ) ); ?>
							</p>
						<?php endif; ?>
					</p>
				</div>

				<div id="capfp_product_fields_config_ui_wrapper" class="options_group">
					<h3><?php esc_html_e('Configured Fields for this Product', 'capfp'); ?></h3>
					<?php if ( !empty($product_fields_config_saved) ) : ?>
						<div class="capfp-product-fields-list">
						<?php foreach( $product_fields_config_saved as $field_index => $field_config ) : ?>
							<?php
							$original_group_name = __('Unknown Group', 'capfp');
							$original_field_label = isset($field_config['label']) ? $field_config['label'] : __('Unknown Field', 'capfp');
                            if(isset($field_config['original_group_id'])) {
                                foreach($all_global_field_groups as $g_group){
                                    if(isset($g_group['id']) && $g_group['id'] === $field_config['original_group_id']){ // Check isset for g_group['id']
                                        $original_group_name = $g_group['name'];
                                        if(isset($g_group['fields']) && is_array($g_group['fields'])){
                                            foreach($g_group['fields'] as $g_field){
                                                if(isset($g_field['id']) && $g_field['id'] === $field_config['original_field_id']){ // Check isset for g_field['id']
                                                    break;
                                                }
                                            }
                                        }
                                        break;
                                    }
                                }
                            }
							$field_unique_key = isset($field_config['unique_key']) ? esc_attr( $field_config['unique_key'] ) : 'field_' . $field_index;
							?>
							<div class="capfp-product-field-item" data-field-key="<?php echo $field_unique_key; ?>">
								<h4><?php echo esc_html( $original_field_label ); ?> <small>(from: <?php echo esc_html($original_group_name); ?>)</small></h4>
								<input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][label]" value="<?php echo esc_attr(isset($field_config['label']) ? $field_config['label'] : ''); ?>" />
								<input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][type]" value="<?php echo esc_attr(isset($field_config['type']) ? $field_config['type'] : ''); ?>" />
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][unique_key]" value="<?php echo esc_attr(isset($field_config['unique_key']) ? $field_config['unique_key'] : ''); ?>" />
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][original_group_id]" value="<?php echo esc_attr(isset($field_config['original_group_id']) ? $field_config['original_group_id'] : ''); ?>" />
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][original_field_id]" value="<?php echo esc_attr(isset($field_config['original_field_id']) ? $field_config['original_field_id'] : ''); ?>" />
                                <?php if(isset($field_config['options']) && is_array($field_config['options'])): ?>
                                    <?php foreach($field_config['options'] as $opt_idx => $opt_val): ?>
                                        <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][options][<?php echo $opt_idx; ?>][label]" value="<?php echo esc_attr(isset($opt_val['label']) ? $opt_val['label'] : ''); ?>" />
                                        <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][options][<?php echo $opt_idx; ?>][value]" value="<?php echo esc_attr(isset($opt_val['value']) ? $opt_val['value'] : ''); ?>" />
                                        <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][options][<?php echo $opt_idx; ?>][price]" value="<?php echo esc_attr(isset($opt_val['price']) ? $opt_val['price'] : ''); ?>" />
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][required]" value="<?php echo esc_attr(isset($field_config['required']) ? $field_config['required'] : 'no'); ?>" />
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][min]" value="<?php echo esc_attr(isset($field_config['min']) ? $field_config['min'] : ''); ?>" />
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][max]" value="<?php echo esc_attr(isset($field_config['max']) ? $field_config['max'] : ''); ?>" />

								<div class="capfp-conditional-logic-rules">
									<h5><?php esc_html_e('Conditional Logic: Show this field if...', 'capfp'); ?> <button type="button" class="button button-small capfp-add-condition-rule"><?php esc_html_e('Add Rule', 'capfp'); ?></button></h5>
									<div class="capfp-condition-rules-list">
										<?php
										$rules = isset($field_config['conditions']) && is_array($field_config['conditions']) ? $field_config['conditions'] : array();
										if(empty($rules)) { $rules[] = array('field'=>'','operator'=>'','value'=>''); }

										foreach($rules as $rule_idx => $rule): ?>
										<div class="capfp-condition-rule">
											<select class="capfp-condition-field" name="_capfp_product_fields_config[<?php echo $field_index; ?>][conditions][<?php echo $rule_idx; ?>][field]">
												<option value=""><?php esc_html_e('-- Select Field --', 'capfp'); ?></option>
												<?php foreach($product_fields_config_saved as $other_field_config): ?>
													<?php if(isset($other_field_config['unique_key']) && isset($field_unique_key) && $other_field_config['unique_key'] === $field_unique_key) continue; ?>
													<option value="<?php echo esc_attr(isset($other_field_config['unique_key']) ? $other_field_config['unique_key'] : ''); ?>" <?php selected(isset($rule['field']) ? $rule['field'] : '', isset($other_field_config['unique_key']) ? $other_field_config['unique_key'] : ''); ?>>
														<?php echo esc_html(isset($other_field_config['label']) ? $other_field_config['label'] : ''); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<select class="capfp-condition-operator" name="_capfp_product_fields_config[<?php echo $field_index; ?>][conditions][<?php echo $rule_idx; ?>][operator]">
												<option value="is" <?php selected(isset($rule['operator']) ? $rule['operator'] : '', 'is'); ?>><?php esc_html_e('Is', 'capfp'); ?></option>
												<option value="is_not" <?php selected(isset($rule['operator']) ? $rule['operator'] : '', 'is_not'); ?>><?php esc_html_e('Is Not', 'capfp'); ?></option>
											</select>
											<input type="text" class="capfp-condition-value" name="_capfp_product_fields_config[<?php echo $field_index; ?>][conditions][<?php echo $rule_idx; ?>][value]" value="<?php echo esc_attr(isset($rule['value']) ? $rule['value'] : ''); ?>" placeholder="<?php esc_attr_e('Value or Option Value', 'capfp'); ?>" />
											<button type="button" class="button button-small capfp-remove-condition-rule">&times;</button>
										</div>
										<?php endforeach; ?>
									</div>
								</div>
                                <hr style="margin-top:10px; margin-bottom:10px;">
							</div>
						<?php endforeach; ?>
						</div>
					<?php else: ?>
						<p><?php esc_html_e('No fields configured for this product yet. Select field groups above and save the product to load their fields for configuration.', 'capfp'); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<?php $this->conditional_logic_js_templates($product_fields_config_saved); ?>
			<style>
				.capfp-product-field-item { border:1px solid #eee; padding:10px; margin-bottom:10px; background:#fdfdfd; }
				.capfp-condition-rule { display:flex; gap:5px; margin-bottom:5px; align-items:center; }
				.capfp-condition-rule select, .capfp-condition-rule input[type="text"] { flex-grow:1; }
                .capfp-conditional-logic-rules h5 { font-size: 1em; margin-bottom: 5px;}
                .capfp-conditional-logic-rules h5 button { margin-left: 10px; vertical-align: middle;}
                .capfp-remove-condition-rule { color: #a00 !important; border-color: #a00 !important; background: #fff5f5 !important; padding: 0px 5px !important; line-height: 1.5 !important; min-height: auto !important;}
			</style>
			<?php
		}

		/**
		 * Save custom fields data for the product.
		 *
		 * @param int $product_id Product ID.
		 */
		public function save_custom_fields_data( $product_id ) {
			if ( ! isset( $_POST['capfp_product_custom_fields_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['capfp_product_custom_fields_nonce'] ), 'capfp_save_product_custom_fields' ) ) {
				return;
			}

			$all_global_field_groups = get_option( 'capfp_field_groups', array() );
			// Start with the currently saved configuration for this product.
			$product_config_before_save = get_post_meta( $product_id, '_capfp_product_fields_config', true );
			if ( ! is_array( $product_config_before_save ) ) {
				$product_config_before_save = array();
			}

			// Create a map of the current product's field conditions by unique_key for easy preservation.
			$current_product_conditions_map = array();
			foreach($product_config_before_save as $saved_field) {
				if (isset($saved_field['unique_key'])) {
					$current_product_conditions_map[$saved_field['unique_key']] = isset($saved_field['conditions']) && is_array($saved_field['conditions']) ? $saved_field['conditions'] : array();
				}
			}

			$selected_group_ids_for_sync = isset( $_POST['_capfp_selected_field_group_ids_for_sync'] ) && is_array( $_POST['_capfp_selected_field_group_ids_for_sync'] )
										? array_map( 'sanitize_text_field', $_POST['_capfp_selected_field_group_ids_for_sync'] )
										: array();

			$synced_product_config_map = array(); // This will hold the fields after syncing structure (keyed by unique_key)

			// 1. Synchronize field structure: Build the new structure based on selected global groups.
			// For fields that should exist, copy their base properties from global settings.
			// Preserve existing conditions if the field was already part of the product's config.
			if (is_array($all_global_field_groups)) {
				foreach ( $all_global_field_groups as $global_group ) {
					if ( isset( $global_group['id'] ) && in_array( $global_group['id'], $selected_group_ids_for_sync ) && isset( $global_group['fields'] ) && is_array( $global_group['fields'] ) ) {
						foreach ( $global_group['fields'] as $global_field ) {
							if (!isset($global_field['id'])) continue; // Skip malformed global fields

							$unique_key = $global_group['id'] . '_' . $global_field['id'];

							// Base entry from global field definition
							$field_entry = array(
								'unique_key'        => $unique_key,
								'original_group_id' => $global_group['id'],
								'original_field_id' => $global_field['id'],
								'label'             => isset($global_field['label']) ? $global_field['label'] : '',
								'type'              => isset($global_field['type']) ? $global_field['type'] : 'text',
								'options'           => isset( $global_field['options'] ) && is_array($global_field['options']) ? $global_field['options'] : array(),
								'required'          => isset( $global_field['required'] ) ? $global_field['required'] : 'no',
								'min'               => isset( $global_field['min'] ) ? $global_field['min'] : '',
								'max'               => isset( $global_field['max'] ) ? $global_field['max'] : '',
								'conditions'        => array() // Default to empty
							);

							// If this field existed before (based on unique_key), preserve its conditions
							if (isset($current_product_conditions_map[$unique_key])) {
								$field_entry['conditions'] = $current_product_conditions_map[$unique_key];
							}
							$synced_product_config_map[$unique_key] = $field_entry;
						}
					}
				}
			}
			// $synced_product_config_map now contains all fields that *should* be on the product,
			// with their base properties from global settings and any previously saved conditions preserved.

			// 2. Update conditions from POST data.
			// $_POST['_capfp_product_fields_config'] is an indexed array from the form.
			// Each item in this POST array represents a field that was displayed in the "Configured Fields for this Product" UI.
			if ( isset( $_POST['_capfp_product_fields_config'] ) && is_array( $_POST['_capfp_product_fields_config'] ) ) {
				foreach ( $_POST['_capfp_product_fields_config'] as $submitted_field_data_from_post ) {
					if ( isset( $submitted_field_data_from_post['unique_key'] ) ) {
						$posted_unique_key = sanitize_text_field($submitted_field_data_from_post['unique_key']);
						// Only update conditions if this field is actually part of the currently synced configuration
						if (isset($synced_product_config_map[$posted_unique_key])) {
							if ( array_key_exists( 'conditions', $submitted_field_data_from_post ) && is_array($submitted_field_data_from_post['conditions']) ) {
								$synced_product_config_map[ $posted_unique_key ]['conditions'] = $this->sanitize_conditions( $submitted_field_data_from_post['conditions'] );
							} else {
								// If 'conditions' key is not set or not an array in the submitted data for this field,
								// it means all rules were deleted or no rules were set for this field in the UI.
								$synced_product_config_map[ $posted_unique_key ]['conditions'] = array();
							}
						}
					}
				}
			}

			// Convert map back to a simple numerically indexed array for saving.
			$final_product_config_to_save = array_values( $synced_product_config_map );

			update_post_meta( $product_id, '_capfp_product_fields_config', $final_product_config_to_save );
		}

		/**
		 * Sanitize conditional logic rules.
		 */
		private function sanitize_conditions( $conditions_data ) {
			$sanitized_conditions = array();
			if ( ! is_array( $conditions_data ) ) {
				return $sanitized_conditions;
			}
			foreach ( $conditions_data as $rule ) {
				$trigger_field = isset( $rule['field'] ) ? sanitize_text_field( $rule['field'] ) : '';
				$operator      = isset( $rule['operator'] ) ? sanitize_text_field( $rule['operator'] ) : '';
				$value         = isset( $rule['value'] ) ? sanitize_text_field( $rule['value'] ) : '';

				if ( ! empty( $trigger_field ) && ! empty( $operator ) ) {
					$sanitized_conditions[] = array(
						'field'    => $trigger_field,
						'operator' => $operator,
						'value'    => $value,
					);
				}
			}
			return $sanitized_conditions;
		}

        /**
         * JS Templates for conditional logic rules.
         */
        private function conditional_logic_js_templates($product_fields_config_saved = array()) {
            ?>
            <script type="text/template" id="tmpl-capfp-condition-rule">
            <div class="capfp-condition-rule">
                <select class="capfp-condition-field" name="{{ data.name_prefix }}[field]">
                    <option value=""><?php esc_html_e('-- Select Field --', 'capfp'); ?></option>
                    <?php if(!empty($product_fields_config_saved)): ?>
                        <?php foreach($product_fields_config_saved as $field_cfg): ?>
                    <% if (data.current_field_key !== '<?php echo esc_js(isset($field_cfg['unique_key']) ? $field_cfg['unique_key'] : ''); ?>') { %>
                            <option value="<?php echo esc_attr(isset($field_cfg['unique_key']) ? $field_cfg['unique_key'] : ''); ?>"><?php echo esc_html(isset($field_cfg['label']) ? $field_cfg['label'] : ''); ?></option>
                    <% } %>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <select class="capfp-condition-operator" name="{{ data.name_prefix }}[operator]">
                    <option value="is"><?php esc_html_e('Is', 'capfp'); ?></option>
                    <option value="is_not"><?php esc_html_e('Is Not', 'capfp'); ?></option>
                </select>
                <input type="text" class="capfp-condition-value" name="{{ data.name_prefix }}[value]" value="" placeholder="<?php esc_attr_e('Value or Option Value', 'capfp'); ?>" />
                <button type="button" class="button button-small capfp-remove-condition-rule">&times;</button>
            </div>
            </script>

            <script type="text/javascript">
            jQuery(document).ready(function($){
                $('#capfp_product_fields_config_ui_wrapper').on('click', '.capfp-add-condition-rule', function(){
                    var $rulesList = $(this).closest('.capfp-conditional-logic-rules').find('.capfp-condition-rules-list');
                    var $fieldItem = $(this).closest('.capfp-product-field-item');
                    var fieldIndex = $fieldItem.closest('.capfp-product-fields-list > div').index();
                    var currentFieldKey = $fieldItem.data('field-key');
                    var ruleIndex = $rulesList.find('.capfp-condition-rule').length;
                    var namePrefix = '_capfp_product_fields_config[' + fieldIndex + '][conditions][' + ruleIndex + ']';

                    var template = wp.template('capfp-condition-rule');
                    // Ensure product_fields_config_saved is available to the JS template if needed for dynamic options,
                    // or ensure the options are correctly generated by PHP within the template string itself.
                    // The current PHP template for tmpl-capfp-condition-rule already iterates $product_fields_config_saved.
                    $rulesList.append(template({ name_prefix: namePrefix, current_field_key: currentFieldKey }));
                });

                $('#capfp_product_fields_config_ui_wrapper').on('click', '.capfp-remove-condition-rule', function(){
                    $(this).closest('.capfp-condition-rule').remove();
                });
            });
            </script>
            <?php
        }
	}
}
