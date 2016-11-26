<?php
/**
 * Product Categories Widget.
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

class SFWC_Widget_Product_Categories extends WC_Widget {

	/**
	 * Current Categories.
	 *
	 * @var bool
	 */
	public $current_cats;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wp, $wp_query;

		$this->widget_cssclass    = 'woocommerce widget_product_categories';
		$this->widget_description = __( 'A list of product categories to multiselect.', 'wcsearchfilters' );
		$this->widget_id          = 'search_filters_for_wc_product_categories';
		$this->widget_name        = __( 'Search Filters for WC - Product Categories', 'wcsearchfilters' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Product Categories', 'wcsearchfilters' ),
				'label' => __( 'Title', 'wcsearchfilters' ),
			),
			'orderby' => array(
				'type'  => 'select',
				'std'   => 'name',
				'label' => __( 'Order by', 'wcsearchfilters' ),
				'options' => array(
					'order' => __( 'Category Order', 'wcsearchfilters' ),
					'name'  => __( 'Name', 'wcsearchfilters' ),
				),
			),
			/*'only_categories' => array(
				'type'        => 'multiselect_categories',
				'std'         => '',
				'label'       => __( 'Categories to Include', 'wcsearchfilters' ),
				'description' => __( 'If left empty, all product categories will show.'),
			),*/
			'only_categories'  => array(
				'type'        => 'text',
				'std'         => '',
				'label'       => __( 'Categories to Include', 'wcsearchfilters' ),
				'description' => __( 'Enter the category separated by `,`. If left empty, all product categories will show.'),
			),
			'count' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Show product counts', 'wcsearchfilters' ),
			),
			'hierarchical' => array(
				'type'  => 'checkbox',
				'std'   => 1,
				'label' => __( 'Show hierarchy', 'wcsearchfilters' ),
			),
			'show_children_only' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Only show children of the current category', 'wcsearchfilters' ),
			),
			'hide_empty' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Hide empty categories', 'wcsearchfilters' ),
			),
		);

		add_action( 'woocommerce_widget_field_multiselect_categories', array( $this, 'widget_form_multiselect_product_cats_field' ), 10, 4 );

		add_filter( 'woocommerce_widget_settings_sanitize_option', array( $this, 'save_product_cats_field' ), 10, 4 );

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

		$count              = isset( $instance['count'] ) ? $instance['count'] : $this->settings['count']['std'];
		$hierarchical       = isset( $instance['hierarchical'] ) ? $instance['hierarchical'] : $this->settings['hierarchical']['std'];
		$show_children_only = isset( $instance['show_children_only'] ) ? $instance['show_children_only'] : $this->settings['show_children_only']['std'];
		$orderby            = isset( $instance['orderby'] ) ? $instance['orderby'] : $this->settings['orderby']['std'];
		$hide_empty         = isset( $instance['hide_empty'] ) ? $instance['hide_empty'] : $this->settings['hide_empty']['std'];
		$only_categories    = isset( $instance['only_categories'] ) ? $instance['only_categories'] : $this->settings['only_categories']['std'];
		//$only_categories    = !empty( $instance['only_categories'] ) ? $instance['only_categories'] : '';
		$list_args          = array( 'show_count' => $count, 'hierarchical' => $hierarchical, 'taxonomy' => 'product_cat', 'orderby' => $orderby, 'hide_empty' => $hide_empty, 'include' => $only_categories );

		// Setup Current Categories
		$this->current_cats   = false;
		$this->cat_ancestors  = array();

		// Show Siblings and Children Only
		if ( $show_children_only && $this->current_cats ) {

			// Top level is needed
			$top_level = get_terms(
				'product_cat',
				array(
					'fields'       => 'ids',
					'parent'       => 0,
					'hierarchical' => true,
					'hide_empty'   => false,
				)
			);

			// Direct children are wanted
			$direct_children = get_terms(
				'product_cat',
				array(
					'fields'       => 'ids',
					'parent'       => $this->current_cats->term_id,
					'hierarchical' => true,
					'hide_empty'   => false,
				)
			);

			// Gather siblings of ancestors
			$siblings  = array();

			if ( $this->cat_ancestors ) {

				foreach ( $this->cat_ancestors as $ancestor ) {

					$ancestor_siblings = get_terms(
						'product_cat',
						array(
							'fields'       => 'ids',
							'parent'       => $ancestor,
							'hierarchical' => false,
							'hide_empty'   => false,
						)

					);

					$siblings = array_merge( $siblings, $ancestor_siblings );

				}
			}

			if ( $hierarchical ) {
				$include = array_merge( $top_level, $this->cat_ancestors, $siblings, $direct_children, array( $this->current_cat->term_id ) );
			} else {
				$include = array_merge( $direct_children );
			}

			$list_args['include'] = implode( ',', $include );

			if ( empty( $include ) ) {
				return;
			}

		} elseif ( $show_children_only ) {
			$list_args['depth']            = 1;
			$list_args['child_of']         = 0;
			$list_args['hierarchical']     = 1;
		}

		if ( '' === get_option( 'permalink_structure' ) ) {
			$form_action = remove_query_arg( array( 'page', 'paged' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
		} else {
			$form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( trailingslashit( $wp->request ) ) );
		}

		// If product tags was queried we need to keep them when we submit the form again.
		if ( isset( $_GET['product_tags'] ) ) {
			$form_action = add_query_arg( 'product_tags', esc_attr( $_GET['product_tags'] ), $form_action );
		}

		// Preserver the orderby variable if set.
		if ( isset( $_GET['orderby'] ) ) {
			$form_action = add_query_arg( 'orderby', esc_attr( $_GET['orderby'] ), $form_action );
		}

		$this->widget_start( $args, $instance );

		echo '<ul class="product-categories">';

		$categories = get_categories( $list_args );

		$output = '';

		$check_categories = isset( $_GET['product_categories'] ) ? explode( ',', esc_attr( $_GET['product_categories'] ) ) : '';

		foreach ( $categories as $category ) {
			$output .= '<li class="cat-item cat-item-' . $category->term_id;

			if ( ! empty( $check_categories ) ) {
				if ( in_array( $category->slug, $check_categories ) ) {
					$output .= ' current-cat';
				}
			}

			if ( $category->category_parent == 0 && $hierarchical ) {
				$output .= ' cat-parent';
			}

			/*if ( $list_args['current_category_ancestors'] && $list_args['current_category'] && in_array( $category->term_id, $list_args['current_category_ancestors'] ) ) {
				$output .= ' current-cat-parent';
			}*/

			$output .= '">';

			$output .= '<input type="checkbox" name="product_categories[]" value="' . esc_html( $category->slug ) . '"';

			if ( ! empty( $check_categories ) ) {
				if ( in_array( $category->slug, $check_categories ) ) {
					$output .= ' checked="checked"';
				}
			}

			$output .= '><label for="' . esc_html( $category->slug ) . '">' . esc_html( $category->name ) . '</label>';

			if ( $count ) {
				$output .= ' <span class="count">(' . $category->count . ')</span>';
			}

			$output .= '</li>';
		}

		echo $output;

		echo '</ul>';

		$this->widget_end( $args );
	} // END widget()

	/**
	 * Creates a multiselect field to list the product categories to select.
	 *
	 * @access public
	 * @param  $key
	 * @param  $value
	 * @param  $setting
	 * @param  $instance
	 */
	public function widget_form_multiselect_product_cats_field( $key, $value, $setting, $instance ) {
		$class = isset( $setting['class'] ) ? $setting['class'] : '';

		$the_terms = get_terms( 'product_cat', array(
			'parent'       => 0,
			'hierarchical' => true,
			'hide_empty'   => false,
		) );

		$term_options = NULL;

		foreach( $the_terms as $term_key => $term_value ) {
			$term_options[] = array( 'name' => $term_value->name, 'value' => $term_value->term_id );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( $key ); ?>"><?php echo $setting['label']; ?></label>
			<select class="widefat <?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo $this->get_field_name( $key ); ?>[]" multiple="multiple">
				<?php
				$include = explode(',', esc_attr( $instance['only_categories'] ) );
				foreach ( $term_options as $option_key => $option_value ) {
					$selected = in_array( $option_value['value'], $include ) ? ' selected="selected"' : '';
					?>
					<option value="<?php echo esc_attr( $option_value['value'] ); ?>"<?php echo $selected; ?>><?php echo esc_html( $option_value['name'] ); ?></option>
				<?php } ?>
			</select>
		</p>
	<?php
	} // END widget_form_multiselect_product_cats_field()

	/**
	 * Saves the array of selections from the multiple select field.
	 *
	 * @access public
	 * @param  $instance
	 * @param  $new_instance
	 * @param  $key
	 * @param  $setting
	 */
	public function save_product_cats_field( $instance, $new_instance, $key, $setting ) {
		if ( 'multiselect_categories' == $setting['type'] ) {
			$instance[ 'only_categories' ] = !empty( $new_instance[ 'only_categories' ] ) ? implode( ',', (array) $new_instance[ 'only_categories' ] ) : '';
		}

		return $instance;
	} // END save_product_cats_field()

}
