<?php
/**
 * Reorder posts
 * 
 * @package    WordPress
 * @subpackage Metronet Reorder Posts plugin
 */


/**
 * Reorder posts
 * Adds drag and drop editor for reordering WordPress posts
 * 
 * Based on work by Scott Basgaard and Ronald Huereca
 * 
 * To use this class, simply instantiate it using an argument to set the post type as follows:
 * new Reorder( array( 'post_type' => 'post', 'order'=> 'ASC' ) );
 * 
 * @copyright Copyright (c), Metronet
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryan@metronet.no>
 * @since 1.0
 */
class Reorder {

	/**
	 * @var $post_type 
	 * @desc Post type to be reordered
	 * @access private
	 */
	private $post_type;

	/**
	 * @var $direction 
	 * @desc ASC or DESC
	 * @access private
	 */
	private $direction;

	/**
	 * @var $heading 
	 * @desc Admin page heading
	 * @access private
	 */
	private $heading;

	/**
	 * @var $initial 
	 * @desc HTML outputted at end of admin page
	 * @access private
	 */
	private $initial;

	/**
	 * @var $final 
	 * @desc HTML outputted at end of admin page
	 * @access private
	 */
	private $final;

	/**
	 * @var $post_statush
	 * @desc The post status of posts to be reordered
	 * @access private
	 */
	private $post_status;

	/**
	 * @var $menu_label 
	 * @desc Admin page menu label
	 * @access private
	 */
	private $menu_label;
	
	/**
	 * @var $icon
	 * @desc Admin page icon
	 * @access private
	 */
	private $icon;

	/**
	 * Class constructor
	 * 
	 * Sets definitions
	 * Adds methods to appropriate hooks
	 * 
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 * @param array $args    If not set, then uses $defaults instead
	 */
	public function __construct( $args = array() ) {

		// Parse arguments
		$defaults = array(
			'post_type'   => 'post',                     // Setting the post type to be reordered
			'order'       => 'ASC',                      // Setting the order of the posts
			'heading'     => __( 'Reorder', 'reorder' ), // Default text for heading
			'initial'     => '',                         // Initial text displayed before sorting code
			'final'       => '',                         // Initial text displayed before sorting code
			'post_status' => 'publish',                  // Post status of posts to be reordered
		);
		extract( wp_parse_args( $args, $defaults ) );

		// Set variables
		$this->post_type   = $post_type;
		$this->order       = $order;
		$this->heading     = $heading;
		$this->initial     = $initial;
		$this->final       = $final;
		$this->menu_label  = $menu_label;
		$this->icon        = $icon;
		$this->post_status = $post_status;

		// Add actions
		add_action( 'wp_ajax_post_sort',   array( $this, 'save_post_order'  ) );
		add_action( 'admin_print_styles',  array( $this, 'print_styles'     ) );
		add_action( 'admin_print_scripts', array( $this, 'print_scripts'    ) );
		add_action( 'admin_menu',          array( $this, 'enable_post_sort' ), 10, 'page' );
		add_action( 'admin_print_styles',  array( $this, 'create_nonce'     ) );
	}

	/**
	 * Creating the nonce value used within sort.js
	 *
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 */
	public function create_nonce() {
		echo "<script>sortnonce = '" .  wp_create_nonce( 'sortnonce' ) . "';</script>";
	}

	/**
	 * Saving the post oder for later use
	 *
	 * @author Ryan Hellyer <ryan@metronet.no> and Ronald Huereca <ronald@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 * @global object $wpdb  The primary global database object used internally by WordPress
	 */
	public function save_post_order() {
		global $wpdb;

		// Verify nonce value, for security purposes
		wp_verify_nonce( json_encode( array( $_POST['nonce'] ) ), 'sortnonce' );

		// Split post output
		$order = explode( ',', $_POST['order'] );

		// Loop through blocks and stash in DB accordingly
		$counter = count( $order );
		foreach ( $order as $post_id ) {
			$wpdb->update(
				$wpdb->posts,
				array( 'menu_order' => $counter ),
				array( 'ID'         => $post_id )
			);
			$counter = $counter - 1;
		}

		die( 1 );
	}

	/**
	 * Print styles to admin page
	 *
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 * @global string $pagenow Used internally by WordPress to designate what the current page is in the admin panel
	 */
	public function print_styles() {
		global $pagenow;

		$pages = array( 'edit.php' );

		if ( in_array( $pagenow, $pages ) )
			wp_enqueue_style( 'reorderpages_style', REORDER_URL . '/admin.css' );

	}

	/**
	 * Print scripts to admin page
	 *
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 * @global string $pagenow Used internally by WordPress to designate what the current page is in the admin panel
	 */
	public function print_scripts() {
		global $pagenow;

		$pages = array( 'edit.php' );
		if ( in_array( $pagenow, $pages ) ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'levert_posts', REORDER_URL . '/scripts/sort.js' );
		}
	}

	/**
	 * Add submenu
	 *
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 */
	public function enable_post_sort() {
		$post_type = $this->post_type;
		if ( 'post' != $post_type ) {
			add_submenu_page(
				'edit.php?post_type=' . $post_type, // Parent slug
				$this->heading,                     // Page title (unneeded since specified directly)
				$this->menu_label,                  // Menu title
				'edit_posts',                       // Capability
				'reorder-' . $post_type,            // Menu slug
				array( $this, 'sort_posts' )        // Callback function
			);
		}
		else {
			add_posts_page(
				$this->heading,                     // Page title (unneeded since specified directly)
				$this->menu_label,                  // Menu title
				'edit_posts',                       // Capability
				'reorder-posts',                    // Menu slug
				array( $this, 'sort_posts' )        // Callback function
			);
		}
	}

	/**
	 * HTML output
	 *
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 * @global string $post_type
	 */
	public function sort_posts() {
		$posts = new WP_Query(
			array(
				'post_type'      => $this->post_type,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => $this->order,
				'post_status'    => $this->post_status,
			)
		);
		?>
		<style type="text/css">
		#icon-reorder-posts {
			background:url(<?php echo $this->icon; ?>) no-repeat;
		}
		</style>
		<div class="wrap">
			<?php screen_icon( 'reorder-posts' ); ?>
			<h2>
				<?php echo $this->heading; ?>
				<img src="<?php echo admin_url( 'images/loading.gif' ); ?>" id="loading-animation" />
			</h2>
			<div id="reorder-error"></div>
			<?php echo $this->initial; ?>
			<ul id="post-list"><?php

			// Looping through all the posts
			while ( $posts->have_posts() ) {
				$posts->the_post();
				?><li id="<?php the_id(); ?>"><?php the_title(); ?></li><?php
			}
			?>

			</ul><?php
			echo $this->final; ?>
		</div><?php
	}

}
