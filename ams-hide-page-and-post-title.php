<?php
/*
Plugin Name: AMS Hide Page and Post Title
Description: Allows authors to hide the title tag on single pages and posts via the edit post screen.
Author: Manoj Sathyavrathan
Version: 1.3
Text Domain: msv-ams-hide-page-and-post-title
*/

namespace MSV_AMSHPAPT_MAIN\HIDE_PAGE_POST_TITLE;

if( ! defined( 'ABSPATH' ) ) 
	exit();

// initialize plugin
if ( function_exists( 'add_action' ) && 
	function_exists( 'register_activation_hook' ) ) {
	add_action( 'plugins_loaded', 
	array( '\MSV_AMSHPAPT_MAIN\HIDE_PAGE_POST_TITLE\MSV_AMSHPAPT_HIDE_PAGE_AND_POST_TITLE',
	'get_msv_ams_hide_page_and_post_title' ) );
}

/**
 * main class for the plugin
 */
class MSV_AMSHPAPT_HIDE_PAGE_AND_POST_TITLE {
    // singleton class variable
	static private $msv_amshpapt_hide_title = NULL;
	
	private $ams_term = 'ams-hide-page-and-post-title';
	
	// singleton method
	public static function get_msv_ams_hide_page_and_post_title() {
		if ( NULL === self::$msv_amshpapt_hide_title ) {
			self::$msv_amshpapt_hide_title = new self;
		}
		return self::$msv_amshpapt_hide_title;
	}

    public function __construct() {
        $this->hooks();
    } // __construct()

	public function hooks() {
        add_action( 'add_meta_boxes', array( $this, 'ams_add_metabox_action' ) );
		add_action( 'save_post', array( $this, 'ams_save_post_action' ) );
		add_action( 'delete_post', array( $this, 'ams_delete_post_action' ) );
		
		add_filter( 'the_title', array($this, 'ams_wpse309151_title_update'), 10, 2 );
		add_filter( 'pre_wp_nav_menu', array($this, 'ams_wpse309151_remove_title_filter_nav_menu'), 10, 2 );
		add_filter( 'wp_nav_menu_items', array($this, 'ams_wpse309151_add_title_filter_non_menu'), 10, 2 );
    } // hooks()
	
	public function ams_add_metabox_action(){
		$posttypes = array( 'post', 'page' );
		foreach ( $posttypes as $posttype ){
			add_meta_box( $this->ams_term, 'Hide Title', array( $this, 'ams_create_metabox' ), $posttype, 'side', 'high' );
		}
	} // ams_add_metabox()
	
	public function ams_create_metabox( $post ){
		$value = get_post_meta( $post->ID, $this->ams_term, true );
		$checked = '';

		if( $value === 'on' ){ $checked = ' checked="checked"'; }
		
		wp_nonce_field( 'msv_ams_hpapt_action', 'msv_ams_hpapt_hide_field' );
		
		?>
		<label><input type="checkbox" name="<?php echo $this->ams_term; ?>" 
		<?php echo $checked; ?> />Hide The Title</label>
		<?php
	} // build_box()
	
	public function ams_save_post_action( $postID ){
		if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			|| !isset( $_POST[ 'msv_ams_hpapt_hide_field' ] )
			|| !wp_verify_nonce( $_POST[ 'msv_ams_hpapt_hide_field' ], 'msv_ams_hpapt_action' ) ) {
			return $postID;
		}

		$old_value = get_post_meta( $postID, $this->ams_term, true );
		$new_value = isset($_POST[ $this->ams_term ]) ? $_POST[ $this->ams_term ] : 'off' ;
		
		if( $old_value ){
			if ( is_null( $new_value ) ){
				delete_post_meta( $postID, $this->ams_term );
			} else {
				update_post_meta( $postID, $this->ams_term, $new_value, $old_value );
			}
		} elseif ( !is_null( $new_value ) ){
			add_post_meta( $postID, $this->ams_term, $new_value, true );
		}

		return $postID;

	} // on_save()
	
	public function ams_delete_post_action( $postID ){
		delete_post_meta( $postID, $this->ams_term );
		
		return $postID;
	} // on_delete()
	
		// Hide post & page title
	function ams_wpse309151_title_update( $title, $post_id ) {
		$post_type = get_post_type( $post_id );
		$value = get_post_meta( $post_id, $this->ams_term, true );
		if( !is_admin() && ( $post_type === 'post' || $post_type === 'page' ) ) {
			
			if ( 'on' == $value ){
				$title = '';
				return $title;
			}
			//return $title;
		}
		return $title;
	}
	

	function ams_wpse309151_remove_title_filter_nav_menu( $nav_menu, $args ) {
		// we are working with menu, so remove the title filter
		remove_filter( 'the_title', array($this, 'ams_wpse309151_title_update'), 10, 2 );
		return $nav_menu;
	}
	// this filter fires just before the nav menu item creation process
	

	function ams_wpse309151_add_title_filter_non_menu( $items, $args ) {
		// we are done working with menu, so add the title filter back
		add_filter( 'the_title', array($this, 'ams_wpse309151_title_update'), 10, 2 );
		return $items;
	}
	// this filter fires after nav menu item creation is done
	
	
}

?>