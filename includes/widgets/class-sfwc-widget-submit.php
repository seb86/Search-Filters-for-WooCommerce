<?php
/**
 * Product Search Widget.
 *
 * @author   Sébastien Dumont
 * @category Widgets
 * @package  Search Filters for WooCommerce/Widgets
 * @version  1.0.0
 * @extends  WC_Widget
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFWC_Widget_Submit extends WC_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_search_reset';
		$this->widget_description = __( 'Search and Reset Buttons.', 'wcsearchfilters' );
    $this->widget_id          = 'search_filters_for_wc_submit';
		$this->widget_name        = __( 'Search Filters for WC - Search & Reset', 'wcsearchfilters' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Title', 'wcsearchfilters' ),
			),
		);

		parent::__construct();
	}

	/**
	 * Output widget.
	 *
	 * @see WP_Widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		global $wp;

		if ( '' === get_option( 'permalink_structure' ) ) {
			$form_action = remove_query_arg( array( 'page', 'paged' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
		} else {
			$form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( trailingslashit( $wp->request ) ) );
		}

		$fresh_form_action = $form_action; // This is used so we don't have to reset the queried url when we submit again.

		// If product categories was queried we need to keep them when we submit the form again.
		if ( isset( $_GET['product_categories'] ) ) {
			$form_action = add_query_arg( 'product_categories', esc_attr( $_GET['product_categories'] ), $form_action );
		}

		// If product tags was queried we need to keep them when we submit the form again.
		if ( isset( $_GET['product_tags'] ) ) {
			$form_action = add_query_arg( 'product_tags', esc_attr( $_GET['product_tags'] ), $form_action );
		}

		// Preserver the orderby variable if set.
		if ( isset( $_GET['orderby'] ) ) {
			$form_action = add_query_arg( 'orderby', esc_attr( $_GET['orderby'] ), $form_action );
		}

		$orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : '';

		$this->widget_start( $args, $instance );

		echo '<script type="text/javascript">
		jQuery(document).ready(function(){
			var product_categories = "";
			var product_tags = "";
			var orderby = "' . $orderby . '";

			function load_categories() {
				jQuery(".product-categories input[type=checkbox]:checked").each( function( index ) {

					if ( index > 0 ) {
						product_categories += "," + jQuery(this).val();
					} else {
						product_categories += jQuery(this).val();
					}

				});
			} // END load_categories()

			load_categories(); // Check for categories already selected first.

			// Check each product category on change.
			jQuery(".product-categories").on("change", "input:checkbox", function(){
				product_categories = ""; // Reset product categories
				load_categories();
			});

			function load_tags() {
				jQuery(".product-tags input[type=checkbox]:checked").each( function( index ) {

					if ( index > 0 ) {
						product_tags += "," + jQuery(this).val();
					} else {
						product_tags += jQuery(this).val();
					}

				});
			} // END load_tags()

			load_tags(); // Check for tags already selected first.

			// Check each product tag on change.
			jQuery(".product-tags").on("change", "input:checkbox", function(){
				product_tags = ""; // Reset product tags
				load_tags();
			});

			// Prepare the search query.
			function prepare_search() {
				var form_action = "' . $fresh_form_action . '";

				if ( product_categories !== "" ) {
					form_action += "?product_categories=" + product_categories;
				}

				if ( product_tags !== "" ) {
					// If none of the product categories were selected.
					if ( product_categories == "" ) {
						form_action += "?product_tags=" + product_tags;
					} else {
						form_action += "&product_tags=" + product_tags;
					}
				}

				if ( orderby !== "" ) {
					form_action += "&orderby=" + orderby;
				}

				// Redirect customer to results
				jQuery("#sfwc-search").attr("href", form_action);
			} // END prepare_search()

			prepare_search(); // Prepare the search button encase the customer has already queried the results once already.

			// Search Button Clicked
			jQuery("#sfwc-search").on("click", function(){
				//e.preventDefault();

				prepare_search();
			});
		});
		</script>';

		echo '<a id="sfwc-search" class="button default" href="#">' . __( 'Search', 'wcsearchfilters' ) . '</a>';
		echo '<a id="sfwc-reset" class="button alt" href="' . $fresh_form_action . '">' . __( 'Reset', 'wcsearchfilters' ) . '</a>';

		$this->widget_end( $args );
	}
}
