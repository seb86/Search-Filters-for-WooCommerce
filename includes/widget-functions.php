<?php
/**
 * Search Filters for WooCommerce Widget Functions
 *
 * Widget related functions and widget registration.
 *
 * @author   Sébastien Dumont
 * @category Core
 * @package  Search Filters for WooCommerce/Functions
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include WooCommerce abstract widget classes.
//include_once( WC()->plugin_path() . '/includes/abstracts/abstract-wc-widget.php' );

// Include search filter widgets.
include_once( dirname( __FILE__ ) . '/widgets/class-sfwc-widget-product-categories.php' );
include_once( dirname( __FILE__ ) . '/widgets/class-sfwc-widget-product-tags.php' );
include_once( dirname( __FILE__ ) . '/widgets/class-sfwc-widget-price-filter.php' );
include_once( dirname( __FILE__ ) . '/widgets/class-sfwc-widget-submit.php' );

/**
 * Register Widgets.
 *
 * @since 1.0.0
 */
function sfwc_register_widgets() {
	register_widget( 'SFWC_Widget_Product_Categories' );
  register_widget( 'SFWC_Widget_Product_Tags' );
	register_widget( 'SFWC_Widget_Price_Filter' );
	register_widget( 'SFWC_Widget_Submit' );
}
add_action( 'widgets_init', 'sfwc_register_widgets' );
