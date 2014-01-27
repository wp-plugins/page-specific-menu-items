<?php 
/**
 * Plugin Name: Page Specific Menu Items
 * Plugin URI: http://www.wordpress.org/plugins
 * Description: This plugin allows you to select menu items page wise.
 * Version: 1.0
 * Author: Dharma Poudel (@rogercomred)
 * Author URI: https://www.twitter.com/rogercomred
 * Text Domain: ps-menu-items
 * Domain Path: /l10n
 */

if(!class_exists('Page_Specific_Menu_Items')) {

	class Page_Specific_Menu_Items{
	
		/**
		 * some private variables
		**/
		private $textdomain = 'ps-menu-items';				// Textdomain
		private $metabox_htmlID = 'ps_menu_items';			// HTML ID attribute of the metabox
		private $nonce = 'ps-menu-items';					// Name of Nonce 
		private $ps_defaults = array(						// Default setting values
							'post_type'=> array('page'),
							'menu_name' => 'primary'  );
		
		
		/**
		 * Constructor (backward compatible)
		**/
		function Page_Specific_Menu_Items() {
		
			self::__construct();
			
		} 
		
		
		/**
		 * Constructor
		 */
		function __construct() {
		
			//initialize
			$this->ps_defaults = array_merge($this->ps_defaults, get_option( 'ps_menuitems' ));
			
			
			if(is_admin()) {	// Admin
				add_action( 'admin_init', array( $this, 'ps_init' ));
				add_action( 'admin_init', array( $this, 'ps_page_init' ));
				add_action( 'admin_init', array( $this, 'ps_add_meta_box' ));
				add_action( 'admin_menu', array( $this, 'ps_add_page' ) );
				add_action( 'save_post', array( $this, 'ps_save_menuitems') );
				
			}else {	// Frontend
			
				add_action( 'wp_head', array($this, 'ps_hide_menuitems'));
				add_filter( 'wp_nav_menu_objects', array($this, 'ps_add_menu_class'));
				
			}
			
		}
		
		/**
		 * localization
		**/
		function ps_init() {
		
			if(function_exists('load_plugin_textdomain')) {
				load_plugin_textdomain($this->textdomain, false, dirname(plugin_basename( __FILE__ )) . '/l10n/');
			}
			
		}
		
		
		
		/**
		 * adds plugin options page
		**/
		public function ps_add_page() {
			add_options_page(
				'Settings Admin', 
				'PS MenuItems', 
				'manage_options', 
				'ps-setting-admin', 
				array( $this, 'ps_create_admin_page' )
			);
		}
		
		
		
		/**
		 * prints html for plugin options page
		**/
		public function ps_create_admin_page() {
			?>
			<div class="wrap">
				<?php screen_icon(); ?>
				<h2>Post Specific Menu Items Settings</h2>           
				<form method="post" action="options.php">
				<?php
					// This prints out all hidden setting fields
					settings_fields( 'ps_menuitems_group' );   
					do_settings_sections( 'ps-setting-admin' );
					submit_button();
				?>
				</form>
			</div>
			<?php
		}
		
		
		
		/**
		 * registers an adds the settings
		**/
		function ps_page_init() {
			
			register_setting( 'ps_menuitems_group',  'ps_menuitems' );

			add_settings_section(
				'ps-settings', // ID
				' ', // Title
				array( $this, 'print_section_text' ), // Callback
				'ps-setting-admin' // Page
			);  

			add_settings_field(
				'ps_select_menu', // ID
				'Select Menu', // Title 
				array( $this, 'ps_select_menu_cb' ), // Callback
				'ps-setting-admin', // Page
				'ps-settings' // Section           
			); 
			
		}
		
		
		/** 
		 * Prints the Section text
		**/
		public function print_section_text() {
			print 'Select which menu you want to use for :';
		}
		
		
		/**
		 * Prints the menu select box 
		**/	
		public function ps_select_menu_cb() {
		
			$all_menus = get_registered_nav_menus();
			if($all_menus){
				echo "<select id='ps_select_menu' name='ps_menuitems[menu_name]' >";
				foreach($all_menus as $location => $description){
					$selected = ($location == $this->ps_defaults['menu_name'])? 'selected="selected"' : '' ;
					printf('<option value="%s"  %s >%s</option>', $location, $selected, $description);
				}
				echo "</select>";
			}
		}
		
		
		/**
		 * Adds meta box on page screen
		**/
		function ps_add_meta_box(){
		
			foreach( $this->ps_defaults['post_type'] as $post_type ) {
				add_meta_box(
					$this->metabox_htmlID,								// HTML id  attribute of the edit screen section
					__('Page Specific Menu Items', $this->textdomain),	// title of the edit screen section
					array( $this, 'ps_display_menu_items' ), 			//callback function that prints html
					$post_type, 										// post type on which to show edit screen
					'side', 											// context - part of page where to show the edit screen
					'high'												// priority where the boxes should show
				);
			}
			
		}
		
		
		/**
		 * Prints html for meta box
		**/
		function ps_display_menu_items(){
		
			global $post;
			$locations = get_nav_menu_locations();
			// verify using nonce
			wp_nonce_field(plugin_basename( __FILE__ ), $this->nonce);
			
			if ( isset( $locations[ $this->ps_defaults['menu_name']] ) ) {
			
				$currentpage_items =get_post_meta($post->ID, $this->textdomain.'_currentpage_items', true);

				$menu_items = wp_get_nav_menu_items($locations[ $this->ps_defaults['menu_name'] ]);
				
				_e("<p><strong>Select menu items to show in this page.</strong></p>", $this->textdomain);
				
				$menu_list = '<ul id="menu-' . $this->ps_defaults['menu_name'] . '">';
				foreach ( (array) $menu_items as $key => $menu_item ) {
				
					$checked = (!empty($currentpage_items) && $currentpage_items[0]!='' && in_array($menu_item->ID, $currentpage_items)) ? 'checked="checked"' :  '';
					$menu_list .= '<li><input type="checkbox" style="margin:1px 5px 0;" '.$checked.' name="currentpage_items[]" value="'.$menu_item->ID.'" /><a href="' . $menu_item->url . '">' . $menu_item->title . '</a></li>';
					
				}
				$menu_list .= '</ul>';
				
			} else {
			
				$menu_list = __('<ul><li>Menu is not defined.</li></ul>', $this->textdomain);
				
			}
			
			echo $menu_list;
			echo '<input type="hidden" value="" name="currentpage_items[]" />';
			
		}
		
		
		/**
		 * saves post specific menu items when updating
		**/
		function ps_save_menuitems(){
		
			global $post;
			
			if($post){
				if( !current_user_can('edit_page', $post->ID)) { return; }
				if(!wp_verify_nonce($_REQUEST[$this->nonce], plugin_basename(__FILE__))) { return; }
				
				if ( isset($_POST['currentpage_items'])) {
					//foreach ($_POST['currentpage_items'] as $key => $value) {
					//delete_post_meta($post->ID, $this->textdomain.'_'.$key, $value);
					update_post_meta($post->ID, $this->textdomain.'_currentpage_items', $_POST['currentpage_items']);
					//}
				}
			}
			
		}
		
		
		/**
		 * adds styles to the head of the page in frontend
		**/
		function ps_hide_menuitems(){
		
			echo '<style type="text/css" media="screen">';
			echo '.menu-item.hide_this_item{ display:none; }';
			echo '</style>';
			
		}
		
		
		/**
		 * adds 'hide_this_item' class to each checked menu item
		**/
		function ps_add_menu_class( $items ) {
		
			$currentpage_items = get_post_meta(get_queried_object_id(), $this->textdomain.'_currentpage_items', true);
			if (!empty($currentpage_items) && $currentpage_items[0] !=''){
				foreach ( $items as $item ) {
					if ( !in_array( $item->ID, $currentpage_items ) ) {
						$item->classes[] = 'hide_this_item'; 
					}
				}
			}
			return $items; 
			
		}
		
	}
	
	/**
	 * initiates  the class 'Page_Specific_Menu_Items'
	**/
	new Page_Specific_Menu_Items();
	
}