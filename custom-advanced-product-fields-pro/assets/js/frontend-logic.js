jQuery(document).ready(function($) {
    if (typeof capfp_frontend_data === 'undefined' || typeof capfp_frontend_data.fields_config === 'undefined') {
        return;
    }

    const fieldsConfig = capfp_frontend_data.fields_config;
    const fieldInputPrefix = capfp_frontend_data.field_prefix; // e.g. capfp_field_
    const fieldContainerPrefix = 'capfp_field_container_';

    function getFieldValue(fieldKey) {
        const fieldData = fieldsConfig[fieldKey];
        if (!fieldData) return null;

        const inputSelector = '#' + fieldInputPrefix + fieldKey;
        const $input = $(inputSelector);

        if (!$input.length) {
             // Check for radio buttons if a field type 'radio' is added later
            const radioSelector = 'input[name="' + fieldInputPrefix.replace('#', '') + '[' + fieldKey + ']"]:checked';
            const $radioInput = $(radioSelector);
            if ($radioInput.length) {
                return $radioInput.val();
            }
            return null; // Or handle other complex types
        }

        if (fieldData.type === 'checkbox') {
            return $input.is(':checked') ? $input.val() : ''; // Return checkbox value if checked, else empty
        }
        return $input.val();
    }

    function evaluateCondition(condition) {
        const targetFieldValue = getFieldValue(condition.field);
        if (targetFieldValue === null) return false; // Dependent field not found or no value

        const conditionValue = condition.value;

        switch (condition.operator) {
            case 'is':
                return targetFieldValue === conditionValue;
            case 'is_not':
                return targetFieldValue !== conditionValue;
            // Add more operators: 'contains', 'not_contains', 'greater_than', 'less_than' etc.
            // For 'greater_than', 'less_than', ensure values are numbers:
            // case 'greater_than':
            //     return parseFloat(targetFieldValue) > parseFloat(conditionValue);
            default:
                return false;
        }
    }

    function checkFieldVisibility(fieldKey) {
        const fieldData = fieldsConfig[fieldKey];
        if (!fieldData || !fieldData.conditions || fieldData.conditions.length === 0) {
            $('#' + fieldContainerPrefix + fieldKey).show(); // Show if no conditions
            return;
        }

        let meetsAllConditions = true; // Assuming AND logic for multiple rules on one field
                                     // For OR logic, this would need adjustment (e.g. meetsAnyCondition)
        for (const condition of fieldData.conditions) {
            if (!condition.field || !condition.operator) continue; // Skip incomplete rules

            if (!evaluateCondition(condition)) {
                meetsAllConditions = false;
                break;
            }
        }

        if (meetsAllConditions) {
            $('#' + fieldContainerPrefix + fieldKey).show();
        } else {
            $('#' + fieldContainerPrefix + fieldKey).hide();
            // Optional: Clear the value of hidden fields if desired
            // clearFieldValue(fieldKey);
        }
    }

    // Initial check for all fields on page load
    for (const fieldKey in fieldsConfig) {
        if (fieldsConfig.hasOwnProperty(fieldKey)) {
            checkFieldVisibility(fieldKey); // Initial check

            // Attach event listeners to dependent fields
            // This is a bit broad; ideally, only listen to fields that are actual dependencies.
            // For now, re-evaluate all conditional fields if any CAPFP input changes.
            // More optimized: iterate fieldData.conditions, find condition.field, and attach listener to that specific field.
        }
    }

    // Attach event listeners more dynamically
    // When any of our custom field inputs change, re-evaluate visibility for all fields that might depend on them.
    // This is simpler than tracking exact dependencies but might do more work than necessary on each change.
    $('.capfp-custom-fields-wrapper').on('change keyup', '.capfp-input', function() {
        for (const fieldKeyToUpdate in fieldsConfig) {
            if (fieldsConfig.hasOwnProperty(fieldKeyToUpdate) && fieldsConfig[fieldKeyToUpdate].conditions && fieldsConfig[fieldKeyToUpdate].conditions.length > 0) {
                 // Check if the changed field is a dependency for fieldKeyToUpdate
                let isDependency = false;
                const changedFieldKey = $(this).attr('id') ? $(this).attr('id').replace(fieldInputPrefix, '') : ($(this).attr('name') ? $(this).attr('name').match(/\[(.*?)\]/)[1] : null);

                if(changedFieldKey){
                    for(const condition of fieldsConfig[fieldKeyToUpdate].conditions){
                        if(condition.field === changedFieldKey){
                            isDependency = true;
                            break;
                        }
                    }
                }
                // If the changed field is a dependency OR if we want a global re-check (simpler)
                // For now, let's do a global re-check for simplicity, can be optimized later.
                checkFieldVisibility(fieldKeyToUpdate);
            }
        }
    });

    // Initial visibility check for all fields after setting up listeners
    // This ensures that even if no 'change' event is fired initially, the state is correct.
     for (const fieldKey in fieldsConfig) {
        if (fieldsConfig.hasOwnProperty(fieldKey)) {
             if (fieldsConfig[fieldKey].conditions && fieldsConfig[fieldKey].conditions.length > 0) {
                checkFieldVisibility(fieldKey);
             }
        }
    }

});
