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
    *   Set an additional cost for this service.
*   **Frontend Display:**
    *   If enabled, the "Lock Installation Service" option (as a checkbox) appears on the single product page above the "Add to Cart" button.
    *   The additional cost is clearly displayed.
*   **Price Adjustment:** The product price is dynamically updated in the cart and checkout if the installation service is selected.
*   **Data Persistence:** Selected service option is passed to the cart, checkout, and saved with the order details.
*   **Shop/Archive Page Behavior:** If a product has the "Lock Installation Service" (or other future custom options) enabled, the "Add to Cart" button on shop/archive pages changes to "Select Options" and links to the single product page.
*   **Secure and Clean Code:** Built with WordPress and WooCommerce best practices, including nonces, sanitization, and escaping.

**Upcoming Features (to fulfill the full brief):**

*   Support for multiple custom field types: Text, Number, Select (dropdown).
*   For "Select" type: optional additional price per option.
*   Advanced conditional logic to show/hide fields based on other field values.
*   Comprehensive UI for managing multiple custom fields per product.
*   Validation for required fields and data types.

## Installation

1.  **Download:** Download the `custom-advanced-product-fields-pro.zip` file.
2.  **Upload to WordPress:**
    *   In your WordPress admin panel, go to `Plugins` > `Add New`.
    *   Click on `Upload Plugin`.
    *   Choose the `custom-advanced-product-fields-pro.zip` file you downloaded.
    *   Click `Install Now`.
3.  **Activate:** After installation, click `Activate Plugin`.
4.  **WooCommerce Requirement:** This plugin requires WooCommerce to be installed and activated. If WooCommerce is not active, the plugin will display a notice and will not function.

## How to Test (Current "Lock Installation Service" Feature)

1.  **Ensure WooCommerce is Active:** Go to `Plugins` and make sure WooCommerce is installed and activated.
2.  **Edit a Product:**
    *   Go to `Products` > `All Products` (or `Add New`).
    *   Edit an existing Simple or Variable product (or create a new one).
3.  **Configure Lock Installation Service:**
    *   In the "Product data" section, you will find a new tab: **"Custom Fields"**. Click on it.
    *   You will see options for "Enable Lock Installation Service":
        *   Check the box to enable the service for this product.
        *   Enter a numerical value for "Lock Installation Cost (SAR)" (e.g., `50`).
    *   Save/Update the product.
4.  **View Product on Frontend:**
    *   Go to the product page on your store's frontend.
    *   You should see a checkbox for "Lock Installation Service" above the "Add to Cart" button, displaying the cost if set.
5.  **Test Adding to Cart:**
    *   Check the "Lock Installation Service" checkbox.
    *   Add the product to your cart.
6.  **View Cart:**
    *   Go to your cart page.
    *   The product price should reflect the base price plus the installation cost.
    *   The "Lock Installation Service: Yes (additional cost of X SAR)" should be listed as an item meta.
7.  **Proceed to Checkout:**
    *   The details and total price should carry over correctly.
8.  **Place an Order (Optional - requires a test payment gateway):**
    *   If you complete an order, check the order details (both in the customer account and admin order view). The selected service should be recorded.
9.  **Test Shop/Archive Page:**
    *   Navigate to your shop page or a product category page where the configured product appears.
    *   The button for that product should now say "Select Options" and link to the product's single page, instead of "Add to Cart". Products without the service enabled should still show "Add to Cart" (or their usual button).

## Plugin Development Notes

*   **Text Domain:** `capfp`
*   **Main Class:** `Custom_Advanced_Product_Fields_Pro`
*   **Key Files:**
    *   `custom-advanced-product-fields-pro.php`: Main plugin loader.
    *   `includes/class-capfp-admin.php`: Handles admin settings (product edit page tab).
    *   `includes/class-capfp-frontend.php`: Handles frontend display on product pages and validation.
    *   `includes/class-capfp-cart.php`: Handles cart item data and price adjustments.
    *   `includes/class-capfp-order.php`: Handles saving data to order items.

This README provides a basic outline. As more features are developed, this file will be updated with more comprehensive instructions and details.
