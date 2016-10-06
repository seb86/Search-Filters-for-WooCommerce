<?php
/*
 * Plugin Name: Search Filters for WooCommerce
 * Version:     1.0.0 Beta
 * Description: Provides multiselection search filter widgets for WooCommerce.
 * Author:      Sébastien Dumont
 * Author URI:  https://sebastiendumont.com
 *
 * Text Domain: wcsearchfilters
 * Domain Path: languages
 *
 * Requires at least: 4.3
 * Tested up to: 4.6.1
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Search_Filters' ) ) {

	class WC_Search_Filters {

		/* Plugin version. */
		const VERSION = '1.0.0';

		/* Required WC version. */
		const REQ_WC_VERSION = '2.6.0';

		/* Text domain. */
		const TEXT_DOMAIN = 'wcsearchfilters';

		/**
		 * The single instance of the class
		 *
		 * @access protected
		 * @static
		 * @since  1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main Search Filters for WooCommerce Instance
		 *
		 * Ensures only one instance of Search Filters for WooCommerce is loaded or can be loaded.
		 *
		 * @access public
		 * @static
		 * @since  1.0.0
		 * @see    WC_Search_Filters()
		 * @return Search Filters for WooCommerce instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		} // END instance()

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden
			_doing_it_wrong(__FUNCTION__, __('Cheatin’ huh?', 'wcsearchfilters'), self::VERSION);
		} // END __clone()

		/**
		 * Disable unserializing of the class
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong(__FUNCTION__, __('Cheatin’ huh?', 'wcsearchfilters'), self::VERSION);
		} // END __wakeup()

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load_plugin' ) );
			add_action( 'init', array( $this, 'init_plugin' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_meta_links' ), 10, 4 );

			add_action( 'woocommerce_product_query', array( $this, 'filter_woocommerce_product_query' ), 1, 1 );
			add_filter( 'woocommerce_page_title', array( $this, 'woocommerce_page_title' ), 10, 1 );

			$this->includes(); // Include required files
		} // END __construct()

		/**
		* Plugin URL
		*
		* @access public
		* @static
		* @since  1.0.0
		* @return string
		*/
		public static function plugin_url() {
			return plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
		} // END plugin_url()

		/**
		 * Plugin Path
		 *
		 * @access public
		 * @static
		 * @since  1.0.0
		 * @return string
		 */
		public static function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		} // END plugin_path()

		/**
		 * Check requirements on activation.
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function load_plugin() {
			// Check that the required WooCommerce is running.
			if ( version_compare( WC()->version, self::REQ_WC_VERSION, '<' ) ) {
				add_action( 'admin_notices', array( $this, 'wc_searchfilters_admin_notice' ) );
				return false;
			}
		} // END load_plugin()

		/**
		 * Display a warning message if minimum version of WooCommerce check fails.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function wc_searchfilters_admin_notice() {
			echo '<div class="error"><p>' . sprintf( __( '%1$s requires at least %2$s v%3$s in order to function. Please upgrade %2$s.', 'wcsearchfilters' ), 'Search Filters for WooCommerce', 'WooCommerce', self::REQ_WC_VERSION ) . '</p></div>';
		} // END wcs_wcsearchfilters_wc_admin_notice()

		/**
		 * Initialize the plugin if ready.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function init_plugin() {
			// Load text domain.
			load_plugin_textdomain( 'wcsearchfilters', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		} // END init_plugin()

		/**
		 * Show row meta on the plugin screen.
		 *
		 * @access public
		 * @since  1.0.0
		 * @param  mixed $links Plugin Row Meta
		 * @param  mixed $file  Plugin Base file
		 * @return array
		 */
		public function plugin_meta_links( $links, $file, $data, $status ) {
			if ( $file == plugin_basename( __FILE__ ) ) {
				$author1 = '<a href="' . $data[ 'AuthorURI' ] . '">' . $data[ 'Author' ] . '</a>';
				$links[ 1 ] = sprintf( __( 'By %s', 'wcsearchfilters' ), $author1 );
			}

			return $links;
		} // END plugin_meta_links()

		/**
		 * Filters the WooCommerce Page Title
		 *
		 * @access public
		 * @since  1.0.0
		 * @param  string $page_title
		 * @return string
		 */
		public function woocommerce_page_title( $page_title ) {
			$categories = isset( $_GET['product_categories'] ) ? explode( ',', esc_attr( $_GET['product_categories'] ) ) : '';

			if ( ! empty( $categories ) ) {
				$current_categories = ucwords( implode( ' and ', $categories ) );
				$current_categories = str_replace( '-', ' ', $current_categories );
				$page_title = str_replace( 'And', 'and', $current_categories );
			}

			return $page_title;
		} // END woocommerce_page_title()

		/**
		 * Filters the product query for product categories and tags.
		 *
		 * @access public
		 * @since  1.0.0
		 * @param  object $q
		 * @return void
		 */
		public function filter_woocommerce_product_query( $q ) {
			// Product Categories
			if ( isset( $_GET['product_categories'] ) ) {
				$q->set( 'product_cat', esc_attr( $_GET['product_categories'] ) );
			}

			// Product Tags
			if ( isset( $_GET['product_tags'] ) ) {
				$q->set( 'product_tag', esc_attr( $_GET['product_tags'] ) );
			}
		} // END filter_woocommerce_product_query()

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function includes() {
			include_once('includes/widget-functions.php');
		} // END includes()

	} // END WC_Search_Filters()

} // END class exists 'WC_Search_Filters'

/**
 * This loads the plugin.
 */
function run_wcsearchfilters() {
	return WC_Search_Filters::instance();
} // END run_wcsearchfilters()

add_action( 'plugins_loaded', 'run_wcsearchfilters', 20 );
//add_action( 'woocommerce_loaded', 'run_wcsearchfilters', 10 );
