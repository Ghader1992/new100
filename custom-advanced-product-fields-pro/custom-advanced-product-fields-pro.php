<?php
/**
 * Plugin Name: Custom Advanced Product Fields PRO
 * Plugin URI: https://example.com/custom-advanced-product-fields-pro
 * Description: Adds advanced custom fields to WooCommerce products, enabling features like conditional logic, price adjustments, and more.
 * Version: 1.0.0
 * Author: Jules
 * Author URI: https://example.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: capfp
 * Domain Path: /languages
 * WC requires at least: 3.0
 * WC tested up to: 8.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define CAPFP_PLUGIN_FILE.
if ( ! defined( 'CAPFP_PLUGIN_FILE' ) ) {
	define( 'CAPFP_PLUGIN_FILE', __FILE__ );
}

// Define CAPFP_PLUGIN_PATH.
if ( ! defined( 'CAPFP_PLUGIN_PATH' ) ) {
    define( 'CAPFP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

// Define CAPFP_PLUGIN_URL.
if ( ! defined( 'CAPFP_PLUGIN_URL' ) ) {
    define( 'CAPFP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Define CAPFP_VERSION.
if ( ! defined( 'CAPFP_VERSION' ) ) {
    define( 'CAPFP_VERSION', '1.0.0' );
}

if ( ! class_exists( 'Custom_Advanced_Product_Fields_Pro' ) ) {
	/**
	 * Main Custom Advanced Product Fields Pro Class.
	 *
	 * @class Custom_Advanced_Product_Fields_Pro
	 */
	final class Custom_Advanced_Product_Fields_Pro {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public $version = CAPFP_VERSION;

		/**
		 * Admin instance.
		 *
		 * @var CAPFP_Admin
		 */
		public $admin = null;

		/**
		 * Frontend instance.
		 *
		 * @var CAPFP_Frontend
		 */
		public $frontend = null;

		/**
		 * Cart instance.
		 *
		 * @var CAPFP_Cart
		 */
		public $cart = null;

		/**
		 * Order instance.
		 *
		 * @var CAPFP_Order
		 */
		public $order = null;

		/**
		 * The single instance of the class.
		 *
		 * @var Custom_Advanced_Product_Fields_Pro
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main Custom_Advanced_Product_Fields_Pro Instance.
		 *
		 * Ensures only one instance of Custom_Advanced_Product_Fields_Pro is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @return Custom_Advanced_Product_Fields_Pro - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Custom_Advanced_Product_Fields_Pro Constructor.
		 */
		public function __construct() {
			$this->includes();
			$this->init_hooks();

			if ( $this->is_woocommerce_activated() ) {
				$this->admin    = new CAPFP_Admin();
				$this->frontend = new CAPFP_Frontend();
				$this->cart     = new CAPFP_Cart();
				$this->order    = new CAPFP_Order();
			} else {
				add_action( 'admin_notices', array( $this, 'woocommerce_not_activated_notice' ) );
			}
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since 1.0.0
		 */
		private function init_hooks() {
			// Initialize plugin here
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes() {
			require_once CAPFP_PLUGIN_PATH . 'includes/class-capfp-admin.php';
			require_once CAPFP_PLUGIN_PATH . 'includes/class-capfp-frontend.php';
			require_once CAPFP_PLUGIN_PATH . 'includes/class-capfp-cart.php';
			require_once CAPFP_PLUGIN_PATH . 'includes/class-capfp-order.php';
		}

		/**
		 * Load Localisation files.
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain(
				'capfp',
				false,
				dirname( plugin_basename( CAPFP_PLUGIN_FILE ) ) . '/languages/'
			);
		}

        /**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public function plugin_path() {
			return CAPFP_PLUGIN_PATH;
		}

		/**
		 * Get the plugin url.
		 *
		 * @return string
		 */
		public function plugin_url() {
			return CAPFP_PLUGIN_URL;
		}

		/**
		 * Check if WooCommerce is activated.
		 *
		 * @return bool
		 */
		public function is_woocommerce_activated() {
			return class_exists( 'WooCommerce' );
		}

		/**
		 * WooCommerce not activated notice.
		 */
		public function woocommerce_not_activated_notice() {
			?>
			<div class="error">
				<p>
					<?php
					printf(
						/* translators: %s: WooCommerce plugin name */
						esc_html__( '"Custom Advanced Product Fields PRO" requires "%s" to be installed and activated. Please install and activate WooCommerce.', 'capfp' ),
						'<strong>WooCommerce</strong>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function capfp_run_plugin() {
	return Custom_Advanced_Product_Fields_Pro::instance();
}

// Set up the plugin instance.
add_action( 'plugins_loaded', 'capfp_run_plugin' );
