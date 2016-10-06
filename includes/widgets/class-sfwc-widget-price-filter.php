<?php
/**
 * Price Filter Widget and related functions.
 *
 * Generates a range slider to filter products by price.
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

class SFWC_Widget_Price_Filter extends WC_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_price_filter';
		$this->widget_description = __( 'Shows a price filter slider in a widget which lets you narrow down the list of shown products when viewing product categories.', 'wcsearchfilters' );
		$this->widget_id          = 'search_filters_for_wc_price_filter';
		$this->widget_name        = __( 'Search Filters for WC - Price Filter', 'wcsearchfilters' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Filter by price', 'wcsearchfilters' ),
				'label' => __( 'Title', 'wcsearchfilters' ),
			),
		);

		/*$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'wc-jquery-ui-touchpunch', WC()->plugin_url() . '/assets/js/jquery-ui-touch-punch/jquery-ui-touch-punch' . $suffix . '.js', array( 'jquery-ui-slider' ), WC_VERSION, true );

		wp_register_script( 'wc-price-slider', WC()->plugin_url() . '/assets/js/frontend/price-slider' . $suffix . '.js', array( 'jquery-ui-slider', 'wc-jquery-ui-touchpunch' ), WC_VERSION, true );

		wp_localize_script( 'wc-price-slider', 'woocommerce_price_slider_params', array(
			'currency_symbol' => get_woocommerce_currency_symbol(),
			'currency_pos'    => get_option( 'woocommerce_currency_pos' ),
			'min_price'       => isset( $_GET['min_price'] ) ? esc_attr( $_GET['min_price'] ) : '',
			'max_price'       => isset( $_GET['max_price'] ) ? esc_attr( $_GET['max_price'] ) : '',
		) );*/

		parent::__construct();
	}

	/**
	 * Output widget.
	 *
	 * @see    WP_Widget
	 * @access public
	 * @param  array $args
	 * @param  array $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {
    global $_chosen_attributes, $wpdb, $wp;

		extract( $args );

		/*if ( ! is_post_type_archive( 'product' ) && ! is_tax( get_object_taxonomies( 'product' ) ) )
			return;*/

		if ( sizeof( WC()->query->unfiltered_product_ids ) == 0 )
			return; // None shown - return

		$min_price = isset( $_GET['min_price'] ) ? esc_attr( $_GET['min_price'] ) : '';
		$max_price = isset( $_GET['max_price'] ) ? esc_attr( $_GET['max_price'] ) : '';

		wp_enqueue_script( 'wc-price-slider' );

		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		// Remember current filters/search
		$fields = '';

		if ( get_search_query() )
			$fields .= '<input type="hidden" name="s" value="' . get_search_query() . '" />';

		// If product categories was queried we need to keep them when we submit the form again.
		if ( isset( $_GET['product_categories'] ) )
			$fields .= '<input type="hidden" name="product_categories" value="' . esc_attr( $_GET['product_categories'] ) . '">';

		// If product tags was queried we need to keep them when we submit the form again.
		if ( isset( $_GET['product_tags'] ) )
			$fields .= '<input type="hidden" name="product_tags" value="' . esc_attr( $_GET['product_tags'] ) . '">';

		if ( ! empty( $_GET['orderby'] ) )
			$fields .= '<input type="hidden" name="orderby" value="' . esc_attr( $_GET['orderby'] ) . '" />';

		if ( $_chosen_attributes ) foreach ( $_chosen_attributes as $attribute => $data ) {
			$taxonomy_filter = 'filter_' . str_replace( 'pa_', '', $attribute );
			$fields .= '<input type="hidden" name="' . esc_attr( $taxonomy_filter ) . '" value="' . esc_attr( implode( ',', $data['terms'] ) ) . '" />';

			if ( $data['query_type'] == 'or' )
				$fields .= '<input type="hidden" name="' . esc_attr( str_replace( 'pa_', 'query_type_', $attribute ) ) . '" value="or" />';
		}

		$min = $max = 0;

		$post_min = $post_max = '';

		if ( sizeof( WC()->query->layered_nav_product_ids ) === 0 ) {
			$min = floor( $wpdb->get_var(
				$wpdb->prepare('
					SELECT min(meta_value + 0)
					FROM %1$s
					LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
					WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
					AND meta_value != ""
				', $wpdb->posts, $wpdb->postmeta, '_price', '_min_variation_price' )
			) );

			$max = ceil( $wpdb->get_var(
				$wpdb->prepare('
					SELECT max(meta_value + 0)
					FROM %1$s
					LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
					WHERE meta_key = \'%3$s\'
				', $wpdb->posts, $wpdb->postmeta, '_price' )
			) );

		} else {

			$min = floor( $wpdb->get_var(
				$wpdb->prepare('
					SELECT min(meta_value + 0)
					FROM %1$s
					LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
					WHERE ( meta_key =\'%3$s\' OR meta_key =\'%4$s\' )
					AND meta_value != ""
					AND (
						%1$s.ID IN (' . implode( ',', array_map( 'absint', WC()->query->layered_nav_product_ids ) ) . ')
						OR (
							%1$s.post_parent IN (' . implode( ',', array_map( 'absint', WC()->query->layered_nav_product_ids ) ) . ')
							AND %1$s.post_parent != 0
						)
					)
				', $wpdb->posts, $wpdb->postmeta, '_price', '_min_variation_price'
			) ) );

			$max = ceil( $wpdb->get_var(
				$wpdb->prepare('
					SELECT max(meta_value + 0)
					FROM %1$s
					LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
					WHERE meta_key =\'%3$s\'
					AND (
						%1$s.ID IN (' . implode( ',', array_map( 'absint', WC()->query->layered_nav_product_ids ) ) . ')
						OR (
							%1$s.post_parent IN (' . implode( ',', array_map( 'absint', WC()->query->layered_nav_product_ids ) ) . ')
							AND %1$s.post_parent != 0
						)
					)
				', $wpdb->posts, $wpdb->postmeta, '_price'
			) ) );

		}

		if ( $min == $max )
			return;

		echo $before_widget . $before_title . $title . $after_title;

		if ( get_option( 'permalink_structure' ) == '' )
			$form_action = remove_query_arg( array( 'page', 'paged' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );

		else
			$form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( $wp->request ) );

		echo '<form method="get" action="' . esc_attr( $form_action ) . '">
			<div class="price_slider_wrapper">
				<div class="price_slider" style="display:none;"></div>
				<div class="price_slider_amount">
					<input type="text" id="min_price" name="min_price" value="' . esc_attr( $min_price ) . '" data-min="'.esc_attr( apply_filters( 'woocommerce_price_filter_widget_amount', $min ) ).'" placeholder="'.__('Min price', 'woocommerce' ).'" />
					<input type="text" id="max_price" name="max_price" value="' . esc_attr( $max_price ) . '" data-max="'.esc_attr( apply_filters( 'woocommerce_price_filter_widget_amount', $max ) ).'" placeholder="'.__( 'Max price', 'woocommerce' ).'" />
					<button type="submit" class="button">'.__( 'Filter', 'woocommerce' ).'</button>
					<div class="price_label" style="display:none;">
						'.__( 'Price:', 'woocommerce' ).' <span class="from"></span> &mdash; <span class="to"></span>
					</div>
					' . $fields . '
					<div class="clear"></div>
				</div>
			</div>
		</form>';

		$this->widget_end( $args );
	}

	/**
	 * Get filtered min price for current products.
	 * @return int
	 */
	/*protected function get_filtered_price() {
		global $wpdb, $wp_the_query;

		$args       = $wp_the_query->query_vars;
		$tax_query  = isset( $args['tax_query'] ) ? $args['tax_query'] : array();
		$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

		if ( ! empty( $args['taxonomy'] ) && ! empty( $args['term'] ) ) {
			$tax_query[] = array(
				'taxonomy' => $args['taxonomy'],
				'terms'    => array( $args['term'] ),
				'field'    => 'slug',
			);
		}

		foreach ( $meta_query as $key => $query ) {
			if ( ! empty( $query['price_filter'] ) || ! empty( $query['rating_filter'] ) ) {
				unset( $meta_query[ $key ] );
			}
		}

		$meta_query = new WP_Meta_Query( $meta_query );
		$tax_query  = new WP_Tax_Query( $tax_query );

		$meta_query_sql = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
		$tax_query_sql  = $tax_query->get_sql( $wpdb->posts, 'ID' );

		$sql  = "SELECT min( CAST( price_meta.meta_value AS DECIMAL(2) ) ) as min_price, max( CAST( price_meta.meta_value AS DECIMAL(2) ) ) as max_price FROM {$wpdb->posts} ";
		$sql .= " LEFT JOIN {$wpdb->postmeta} as price_meta ON {$wpdb->posts}.ID = price_meta.post_id " . $tax_query_sql['join'] . $meta_query_sql['join'];
		$sql .= " 	WHERE {$wpdb->posts}.post_type IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_post_type', array( 'product' ) ) ) ) . "')
					AND {$wpdb->posts}.post_status = 'publish'
					AND price_meta.meta_key IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_meta_keys', array( '_price' ) ) ) ) . "')
					AND price_meta.meta_value > '' ";
		$sql .= $tax_query_sql['where'] . $meta_query_sql['where'];

		return $wpdb->get_row( $sql );
	}*/
}
