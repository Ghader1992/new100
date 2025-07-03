# Custom Advanced Product Fields PRO for WooCommerce

**Contributors:** Jules
**Tags:** woocommerce, custom fields, product fields, product options, conditional logic, woocommerce product addons
**Requires at least:** 5.0
**Tested up to:** 6.4
**WC requires at least:** 3.0
**WC tested up to:** 8.5
**Stable tag:** 1.0.0
**License:** GPLv2 or later
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

Adds highly configurable custom fields to your WooCommerce products, allowing for features like conditional logic, additional pricing, and more.

## Description

Custom Advanced Product Fields PRO enhances your WooCommerce store by enabling you to add various types of custom fields to your products. This allows customers to personalize products or add extra services, with options for additional costs and conditional display of fields.

**Core Features (Version 1.0.0 - Initial focus on "Lock Installation Service"):**

*   **Custom Tab on Product Edit Page:** A "Custom Fields" tab is added to the WooCommerce product edit screen (for simple and variable products).
*   **"Lock Installation Service" Option:**
    *   Store owners can enable a "Lock Installation Service" for individual products.
    *   Define reusable "Field Groups" globally (under `WooCommerce > Product Custom Fields`).
    *   Each group can contain multiple fields.
*   **Supported Field Types:**
    *   **Text:** Simple text input.
    *   **Number:** Numerical input with optional Min/Max validation.
    *   **Checkbox:** A single checkbox, typically for boolean options (e.g., "Yes/No"). Can have an associated price.
    *   **Select:** Dropdown list of options. Each option can have an additional price.
*   **Required Fields:** Mark fields as mandatory.
*   **Conditional Logic:**
    *   Show or hide fields on the product page based on the values selected in other fields configured for that same product.
    *   Supports "Is" and "Is Not" operators.
*   **Product-Level Configuration:**
    *   Assign global Field Groups to individual products (Simple & Variable).
    *   Configure conditional logic for each field instance on a product.
*   **Frontend Display:**
    *   Custom fields are displayed on the single product page above the "Add to Cart" button.
    *   Additional prices for options are clearly shown.
*   **Dynamic Price Adjustment:** The product price is dynamically updated in the cart and checkout based on selected options with costs.
*   **Validation:**
    *   Client-side and Server-side validation for required fields.
    *   Data type validation (e.g., Number fields, valid select/checkbox options).
    *   Conditional logic is respected during server-side validation (e.g., hidden required fields won't cause errors).
*   **Data Persistence:** Selected/entered custom field data is passed to the cart, checkout, and saved with the order details.
*   **Shop/Archive Page Behavior:** If a product has active custom fields, the "Add to Cart" button on shop/archive pages changes to "Select Options" and links to the single product page.
*   **Secure and Clean Code:** Built with WordPress and WooCommerce best practices (nonces, sanitization, escaping OOP).

## Installation

1.  **Download:** Download the `custom-advanced-product-fields-pro.zip` file (if provided as a ZIP). Otherwise, ensure all plugin files are in a directory named `custom-advanced-product-fields-pro`.
2.  **Upload to WordPress:**
    *   In your WordPress admin panel, go to `Plugins` > `Add New`.
    *   Click on `Upload Plugin`.
    *   Choose the `custom-advanced-product-fields-pro.zip` file you downloaded.
    *   Click `Install Now`.
3.  **Activate:** After installation, click `Activate Plugin`.
4.  **WooCommerce Requirement:** This plugin requires WooCommerce to be installed and activated. If WooCommerce is not active, the plugin will display a notice and will not function.

## How to Use & Test

### 1. Define Global Field Groups

1.  **Navigate to Settings:** In your WordPress admin, go to `WooCommerce` > `Product Custom Fields`.
2.  **Create a Field Group:**
    *   Click "Add New Field Group".
    *   Enter a **Group Name** (e.g., "Service Options", "Personalization").
3.  **Add Fields to the Group:**
    *   Click "Add Field to Group".
    *   **Label:** The text displayed to customers (e.g., "Gift Message", "Extended Warranty").
    *   **Type:** Choose from:
        *   `Text`: For short text inputs.
        *   `Number`: For numerical inputs. You can specify optional Min/Max values.
        *   `Checkbox`: For a single true/false option.
        *   `Select`: For a dropdown list of choices.
    *   **Required:** Check if the customer must fill or select this field.
    *   **Options (for Checkbox/Select):**
        *   Click "Add Option".
        *   **Opt Label:** What the customer sees for this choice (e.g., "Yes, please", "2-Year Plan").
        *   **Opt Value:** The internal value saved for this choice (e.g., "yes_gift", "2_year_warranty"). Must be unique within the field.
        *   **Opt Price:** Optional additional cost if this option is chosen (e.g., `5.00`). Leave blank for no extra cost.
4.  **Repeat** adding fields and options as needed.
5.  **Save Changes:** Click the "Save Changes" button at the bottom.

### 2. Apply Field Groups & Configure Logic on a Product

1.  **Edit a Product:** Go to `Products` and edit an existing Simple or Variable product (or create a new one).
2.  **Go to "Custom Fields" Tab:** In the "Product data" section, click the "Custom Fields" tab.
3.  **Apply Field Groups:**
    *   From the "Apply Field Groups" multi-select dropdown, choose the global Field Group(s) you want to use for this product (e.g., "Service Options").
    *   **Important:** Click **"Update" (or "Publish")** for the product. This saves your selection and loads the fields from the chosen group(s) into the product's configuration. The page will reload.
4.  **Configure Conditional Logic (After Reload):**
    *   Once the page reloads (or if you edit the product again), the "Custom Fields" tab will now list the individual fields from the selected group(s) under "Configured Fields for this Product".
    *   For each field, you can expand its "Conditional Logic" section.
    *   Click "Add Rule" to define a condition.
    *   **Show this field if...**
        *   **[Select Field]:** Choose another field *from the list of fields configured for this product* that this current field depends on.
        *   **[Operator]:** Choose "Is" or "Is Not".
        *   **[Value]:** Enter the exact **Option Value** of the dependent field that should trigger this rule. For example, if "Extended Warranty" (a select field) has an option with value "ew_1y", you'd enter "ew_1y" here. For a checkbox, this would be the value of its checked state (e.g., "sp_yes").
    *   You can add multiple rules to a single field (they are treated with AND logic - all rules must be true).
5.  **Update Product:** Click "Update" to save the conditional logic settings.

### 3. Test on Frontend

1.  **View Product:** Go to the product page on your store's frontend.
    *   Fields should appear above the "Add to Cart" button.
    *   Test the conditional logic: make selections in one field and see if other fields correctly show/hide.
    *   Check if additional prices for options are displayed.
2.  **Test Validation:**
    *   Try to add to cart without filling required fields (both always-visible and conditionally-visible ones).
    *   Enter incorrect data types (e.g., text in a number field, values outside min/max).
3.  **Test Adding to Cart:**
    *   Make valid selections, including options with additional costs.
    *   Add the product to your cart.
4.  **View Cart & Checkout:**
    *   Verify that the selected custom field data (labels, chosen values/options, prices) is displayed correctly.
    *   Check that the product price has been dynamically adjusted to include any additional costs.
5.  **Place an Order:** (Requires a test payment gateway or COD).
    *   Check the order details (in customer account and admin order view). The selected custom field data should be recorded with the order item.
6.  **Test Shop/Archive Page:**
    *   Navigate to your shop page or a product category page where the configured product appears.
    *   The button for that product should now say "Select Options" and link to the product's single page. Products without custom fields should behave normally.

## Plugin Development Notes

*   **Text Domain:** `capfp`
*   **Product Meta Key for Field Configurations:** `_capfp_product_fields_config`
*   **Global Settings Option Key:** `capfp_field_groups`
*   **Main Class:** `Custom_Advanced_Product_Fields_Pro`
*   **Key Files:**
    *   `custom-advanced-product-fields-pro.php`: Main plugin loader.
    *   `includes/class-capfp-admin.php`: Handles admin settings (product edit page tab).
    *   `includes/class-capfp-frontend.php`: Handles frontend display on product pages and validation.
    *   `includes/class-capfp-cart.php`: Handles cart item data and price adjustments.
    *   `includes/class-capfp-order.php`: Handles saving data to order items.

This README provides a basic outline. As more features are developed, this file will be updated with more comprehensive instructions and details.
