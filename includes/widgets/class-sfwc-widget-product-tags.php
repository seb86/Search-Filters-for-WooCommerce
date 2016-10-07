<?php
/**
 * Product Tags Widget.
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

class SFWC_Widget_Product_Tags extends WC_Widget {

	/**
	 * Current Tags.
	 *
	 * @var bool
	 */
	public $current_tags;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_product_tags';
		$this->widget_description = __( 'A list of product tags to multiselect.', 'wcsearchfilters' );
		$this->widget_id          = 'search_filters_for_wc_product_tags';
		$this->widget_name        = __( 'Search Filters for WC - Product Tags', 'wcsearchfilters' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Product Tags', 'wcsearchfilters' ),
				'label' => __( 'Title', 'wcsearchfilters' ),
			),
			'orderby' => array(
				'type'  => 'select',
				'std'   => 'name',
				'label' => __( 'Order by', 'wcsearchfilters' ),
				'options' => array(
					'order' => __( 'Tag Order', 'wcsearchfilters' ),
					'name'  => __( 'Name', 'wcsearchfilters' ),
				),
			),
			/*'dropdown' => array(
				'type'  => 'multiselect',
				'std'   => '',
				'label' => __( 'Allowed Tags', 'wcsearchfilters' ),
				'options' => array(),
				'description' => __( 'If left empty, all product tags will show.'),
			),*/
			'count' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Show product counts', 'wcsearchfilters' ),
			),
			'hide_empty' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Hide empty tags', 'wcsearchfilters' ),
			),
		);

		parent::__construct();
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		global $wp, $wp_query, $post;

		$count      = isset( $instance['count'] ) ? $instance['count'] : $this->settings['count']['std'];
		$orderby    = isset( $instance['orderby'] ) ? $instance['orderby'] : $this->settings['orderby']['std'];
		$hide_empty = isset( $instance['hide_empty'] ) ? $instance['hide_empty'] : $this->settings['hide_empty']['std'];
		$list_args  = array( 'show_count' => $count, 'taxonomy' => 'product_tag', 'orderby' => $orderby, 'hide_empty' => $hide_empty );

		// Setup Current Tags
		$this->current_tags = false;

		if ( '' === get_option( 'permalink_structure' ) ) {
			$form_action = remove_query_arg( array( 'page', 'paged' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
		} else {
			$form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( trailingslashit( $wp->request ) ) );
		}

		// If product categories was queried we need to keep them when we submit the form again.
		if ( isset( $_GET['product_categories'] ) ) {
			$form_action = add_query_arg( 'product_categories', esc_attr( $_GET['product_categories'] ), $form_action );
		}

		// Preserver the orderby variable if set.
		if ( isset( $_GET['orderby'] ) ) {
			$form_action = add_query_arg( 'orderby', esc_attr( $_GET['orderby'] ), $form_action );
		}

		$this->widget_start( $args, $instance );

		echo '<ul class="product-tags">';

		$tags = get_terms( $list_args );

		//unset( $tags[0] ); // Removes the "All" tag.

		$output = '';

		$check_tags = isset( $_GET['product_tags'] ) ? explode( ',', esc_attr( $_GET['product_tags'] ) ) : '';

		foreach ( $tags as $tag ) {
			$output .= '<li class="tag-item tag-item-' . $tag->term_id;

			if ( ! empty( $check_tags ) ) {
				if ( in_array( $tag->slug, $check_tags ) ) {
					$output .= ' current-tag';
				}
			}

			$output .= '">';

			$output .= '<input type="checkbox" name="product_tags[]" value="' . esc_html( $tag->slug ) . '"';

			if ( ! empty( $check_tags ) ) {
				if ( in_array( $tag->slug, $check_tags ) ) {
					$output .= ' checked="checked"';
				}
			}

			$output .= '><label for="' . esc_html( $tag->slug ) . '">' . esc_html( $tag->name ) . '</label>';

			if ( $count ) {
				$output .= ' <span class="count">(' . $tag->count . ')</span>';
			}

			$output .= '</li>';
		}

		echo $output;

		echo '</ul>';

		$this->widget_end( $args );
	} // END widget()

}
