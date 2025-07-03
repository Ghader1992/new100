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
			global $post, $thepostid, $product_object;
			$product_id = $thepostid; // $post->ID might not be reliable here.

			$all_global_field_groups = get_option( 'capfp_field_groups', array() );
			// This meta will store the actual configuration for the product, including conditional logic.
			// It's an array of field configurations, not just group IDs.
			$product_fields_config_saved = get_post_meta( $product_id, '_capfp_product_fields_config', true );
			if ( ! is_array( $product_fields_config_saved ) ) {
				$product_fields_config_saved = array();
			}

			// Get IDs of groups currently configured for this product to pre-select in the dropdown.
			$selected_group_ids_for_product = array_column($product_fields_config_saved, 'group_id'); // Assuming 'group_id' is stored. More accurately, we'll need to map.
                                                                                                    // For now, let's re-evaluate. We need to select based on what groups are *represented* in the config.
            $active_group_ids_in_config = array();
            if(is_array($product_fields_config_saved)){
                foreach($product_fields_config_saved as $p_field_cfg){
                    if(isset($p_field_cfg['original_group_id'])) { // We'll store original_group_id for each field
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
							// Ensure field_config has what we need, find original group and field name for display
							$original_group_name = __('Unknown Group', 'capfp');
							$original_field_label = isset($field_config['label']) ? $field_config['label'] : __('Unknown Field', 'capfp');
                            if(isset($field_config['original_group_id'])) {
                                foreach($all_global_field_groups as $g_group){
                                    if($g_group['id'] === $field_config['original_group_id']){
                                        $original_group_name = $g_group['name'];
                                        if(isset($g_group['fields']) && is_array($g_group['fields'])){
                                            foreach($g_group['fields'] as $g_field){
                                                if($g_field['id'] === $field_config['original_field_id']){
                                                    // $original_field_label = $g_field['label']; // Use the saved label, as it might be overridden
                                                    break;
                                                }
                                            }
                                        }
                                        break;
                                    }
                                }
                            }
							$field_unique_key = esc_attr( $field_config['unique_key'] ); // e.g. groupid_fieldid_timestamp or just groupid_fieldid if unique enough
							?>
							<div class="capfp-product-field-item" data-field-key="<?php echo $field_unique_key; ?>">
								<h4><?php echo esc_html( $original_field_label ); ?> <small>(from: <?php echo esc_html($original_group_name); ?>)</small></h4>
								<input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][label]" value="<?php echo esc_attr($field_config['label']); ?>" />
								<input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][type]" value="<?php echo esc_attr($field_config['type']); ?>" />
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][unique_key]" value="<?php echo esc_attr($field_config['unique_key']); ?>" />
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][original_group_id]" value="<?php echo esc_attr($field_config['original_group_id']); ?>" />
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][original_field_id]" value="<?php echo esc_attr($field_config['original_field_id']); ?>" />
                                <?php if(isset($field_config['options']) && is_array($field_config['options'])): ?>
                                    <?php foreach($field_config['options'] as $opt_idx => $opt_val): ?>
                                        <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][options][<?php echo $opt_idx; ?>][label]" value="<?php echo esc_attr($opt_val['label']); ?>" />
                                        <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][options][<?php echo $opt_idx; ?>][value]" value="<?php echo esc_attr($opt_val['value']); ?>" />
                                        <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][options][<?php echo $opt_idx; ?>][price]" value="<?php echo esc_attr($opt_val['price']); ?>" />
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if(isset($field_config['required'])): ?>
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][required]" value="<?php echo esc_attr($field_config['required']); ?>" />
                                <?php endif; ?>
                                <?php if(isset($field_config['min'])): ?>
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][min]" value="<?php echo esc_attr($field_config['min']); ?>" />
                                <?php endif; ?>
                                <?php if(isset($field_config['max'])): ?>
                                <input type="hidden" name="_capfp_product_fields_config[<?php echo $field_index; ?>][max]" value="<?php echo esc_attr($field_config['max']); ?>" />
                                <?php endif; ?>


								<div class="capfp-conditional-logic-rules">
									<h5><?php esc_html_e('Conditional Logic: Show this field if...', 'capfp'); ?> <button type="button" class="button button-small capfp-add-condition-rule"><?php esc_html_e('Add Rule', 'capfp'); ?></button></h5>
									<div class="capfp-condition-rules-list">
										<?php
										$rules = isset($field_config['conditions']) ? $field_config['conditions'] : array();
										if(empty($rules)) { $rules[] = array('field'=>'','operator'=>'','value'=>''); } // Show one empty rule

										foreach($rules as $rule_idx => $rule): ?>
										<div class="capfp-condition-rule">
											<select class="capfp-condition-field" name="_capfp_product_fields_config[<?php echo $field_index; ?>][conditions][<?php echo $rule_idx; ?>][field]">
												<option value=""><?php esc_html_e('-- Select Field --', 'capfp'); ?></option>
												<?php foreach($product_fields_config_saved as $other_field_idx => $other_field_config): ?>
													<?php if($other_field_config['unique_key'] === $field_unique_key) continue; // Can't depend on itself ?>
													<option value="<?php echo esc_attr($other_field_config['unique_key']); ?>" <?php selected(isset($rule['field']) ? $rule['field'] : '', $other_field_config['unique_key']); ?>>
														<?php echo esc_html($other_field_config['label']); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<select class="capfp-condition-operator" name="_capfp_product_fields_config[<?php echo $field_index; ?>][conditions][<?php echo $rule_idx; ?>][operator]">
												<option value="is" <?php selected(isset($rule['operator']) ? $rule['operator'] : '', 'is'); ?>><?php esc_html_e('Is', 'capfp'); ?></option>
												<option value="is_not" <?php selected(isset($rule['operator']) ? $rule['operator'] : '', 'is_not'); ?>><?php esc_html_e('Is Not', 'capfp'); ?></option>
												<?php /* More operators later: contains, not_contains, greater_than, less_than for numbers */ ?>
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
			$current_product_config = get_post_meta( $product_id, '_capfp_product_fields_config', true );
			if(!is_array($current_product_config)) $current_product_config = array();

			$new_product_config = array();

			// --- Sync global groups to product specific config ---
			$selected_group_ids_for_sync = isset( $_POST['_capfp_selected_field_group_ids_for_sync'] ) && is_array( $_POST['_capfp_selected_field_group_ids_for_sync'] )
										? array_map( 'sanitize_text_field', $_POST['_capfp_selected_field_group_ids_for_sync'] )
										: array();

            // Build a map of existing fields by unique_key to preserve their conditions
            $existing_fields_map = array();
            foreach($current_product_config as $existing_field_conf) {
                if(isset($existing_field_conf['unique_key'])) {
                    $existing_fields_map[$existing_field_conf['unique_key']] = $existing_field_conf;
                }
            }

			foreach ( $all_global_field_groups as $global_group ) {
				if ( isset( $global_group['id'] ) && in_array( $global_group['id'], $selected_group_ids_for_sync ) && isset( $global_group['fields'] ) && is_array( $global_group['fields'] ) ) {
					foreach ( $global_group['fields'] as $global_field ) {
						$unique_key_for_field = $global_group['id'] . '_' . $global_field['id']; // This is the key for matching

						$field_entry = array(
							'unique_key'        => $unique_key_for_field, // Used for dependencies
							'original_group_id' => $global_group['id'],
							'original_field_id' => $global_field['id'],
							'label'             => $global_field['label'], // Default from global, can be overridden later
							'type'              => $global_field['type'],
							'options'           => isset( $global_field['options'] ) ? $global_field['options'] : array(),
							'required'          => isset( $global_field['required'] ) ? $global_field['required'] : 'no',
							'min'               => isset( $global_field['min'] ) ? $global_field['min'] : '',
							'max'               => isset( $global_field['max'] ) ? $global_field['max'] : '',
                            'conditions'        => array() // Default empty conditions
						);

                        // If this field already existed, preserve its conditions
                        if(isset($existing_fields_map[$unique_key_for_field]) && isset($existing_fields_map[$unique_key_for_field]['conditions'])) {
                            $field_entry['conditions'] = $existing_fields_map[$unique_key_for_field]['conditions'];
                        }
						$new_product_config[] = $field_entry;
					}
				}
			}

            // Now, if there was a direct submission of _capfp_product_fields_config (e.g. conditions being edited)
            // we need to merge those changes carefully.
            // The hidden fields for basic field properties ensure they are re-submitted.
            // The main thing to sanitize and process here are the conditions.
            if (isset($_POST['_capfp_product_fields_config']) && is_array($_POST['_capfp_product_fields_config'])) {
                $submitted_config_map = array();
                foreach($_POST['_capfp_product_fields_config'] as $idx => $s_field_conf) {
                    if(isset($s_field_conf['unique_key'])) {
                         $submitted_config_map[$s_field_conf['unique_key']] = $s_field_conf;
                    }
                }

                foreach($new_product_config as $idx => $synced_field_conf) {
                    $ukey = $synced_field_conf['unique_key'];
                    if(isset($submitted_config_map[$ukey]) && isset($submitted_config_map[$ukey]['conditions'])) {
                        $new_product_config[$idx]['conditions'] = $this->sanitize_conditions($submitted_config_map[$ukey]['conditions']);
                    }
                }
            }

			update_post_meta( $product_id, '_capfp_product_fields_config', $new_product_config );
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
				if ( ! empty( $rule['field'] ) && ! empty( $rule['operator'] ) ) { // Value can sometimes be empty intentionally
					$sanitized_rule = array(
						'field'    => sanitize_text_field( $rule['field'] ),
						'operator' => sanitize_text_field( $rule['operator'] ),
						'value'    => sanitize_text_field( $rule['value'] ),
					);
					$sanitized_conditions[] = $sanitized_rule;
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
                    <% if (data.current_field_key !== '<?php echo esc_js($field_cfg['unique_key']); ?>') { %>
                            <option value="<?php echo esc_attr($field_cfg['unique_key']); ?>"><?php echo esc_html($field_cfg['label']); ?></option>
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
                    var fieldIndex = $fieldItem.closest('.capfp-product-fields-list > div').index(); // Get the index of the field config in the array
                    var currentFieldKey = $fieldItem.data('field-key');
                    var ruleIndex = $rulesList.find('.capfp-condition-rule').length;
                    var namePrefix = '_capfp_product_fields_config[' + fieldIndex + '][conditions][' + ruleIndex + ']';

                    var template = wp.template('capfp-condition-rule');
                    $rulesList.append(template({ name_prefix: namePrefix, current_field_key: currentFieldKey }));
                });

                $('#capfp_product_fields_config_ui_wrapper').on('click', '.capfp-remove-condition-rule', function(){
                    $(this).closest('.capfp-condition-rule').remove();
                });

                // When groups are changed, the fields list will re-render on save.
                // So, no need to dynamically update the "Select Field" dropdown for conditions on group change here.
                // It will be populated correctly on next page load after saving.
            });
            </script>
            <?php
        }

		}
	}
}
