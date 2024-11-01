<?php
/*
Plugin Name: WP Uninstaller by Azed
Author: Azed - Cyril Chaniaud
Plugin URI: http://www.azed-dev.com/wordpress/wp-unistaller-by-azed/
Description: This plugin allow you to delete your wordpress installation safely.
Version: 0.3.1
Author URI: http://www.azed-dev.com/
License: GPL3
*/


if( !class_exists('AzedWordpressUninstaller') ){

	class AzedWordpressUninstaller{

		const textDomain = 'wpuninstallerbyazed';
	  /**
		* Construct the plugin object
		*/

		public function __construct()
		{
			add_action('plugins_loaded', array(&$this,'plugin_init'));
			add_action('admin_menu', array(&$this, 'azedWordpressUninstaller_menu'));
		} // END public function __construct

		public static function init(){
			if( is_user_logged_in () ){
				$azedWordpressUninstallerPlugin = new AzedWordpressUninstaller();
				load_plugin_textdomain( SELF :: textDomain, false, dirname(plugin_basename(__FILE__)).'/languages/' );
			}
		}

		public function azedWordpressUninstaller_menu()
		{
			if( current_user_can('administrator') ){
				$pageID = add_options_page ( __('Uninstall Wordpress', SELF :: textDomain), __('Uninstall Wordpress', SELF :: textDomain), 'read', 'AzedWordpressUninstaller', array(&$this,'uninstall_wordpress') );
	   		add_action( 'admin_print_styles-' . $pageID, array(&$this,'admin_custom_css') );
			}
		} // END azedWordpressUninstaller_menu function for adding submenu item

		public function admin_custom_css(){
			wp_enqueue_style( 'AzedWordpressUninstaller_CSS', plugins_url( 'css/styles.css', __FILE__ ));
		}

		private function rmDir($path_d , $deleteFolder = true){
			if( current_user_can('administrator') ){
				$path_d = rtrim( $path_d , '/' );
				if(file_exists($path_d)){
					if ($handle_d = opendir($path_d)) {
						while (false !== ($file = readdir($handle_d))) {
							if($file!= '.' && $file != '..'){
								if(is_dir($path_d.'/'.$file)){
									$this -> rmDir($path_d.'/'.$file);
								}
								else{
									unlink($path_d.'/'.$file);
								}
							}
						}
						closedir($handle_d);
					}
					if( $deleteFolder )
					rmdir($path_d);
				}
			}
		}

		public function uninstall_wordpress()
		{
			if( current_user_can('administrator') ){
				global $wpdb;
				$currentUser = wp_get_current_user();
				$success = false;
				echo '<div class="azedWrapper">';
				echo '<h1>'.__('Wordpress Uninstaller by Azed', SELF :: textDomain).'</h1>';
				echo '</div>';
					echo '<div class="azedWrapper">';
					if( isset( $_POST['lastListTablesToDelete'] ) ){
						if( wp_verify_nonce( $_POST['azed_n'], 'deleteTables' ) ){
							$listaTablesToDelete = explode( ' | ' , $_POST['lastListTablesToDelete'] );
							foreach( $listaTablesToDelete as $id => $tableName ){
								$pos = strpos( $tableName , $wpdb -> prefix );
								if( $pos !== false && $pos == 0 )
								$wpdb -> get_results("DROP TABLE IF EXISTS ".sanitize_text_field( $tableName ).";");
							}
							$this -> rmdir( get_home_path() , $_POST['azeddeletefolder'] == 'yes' );

							echo '<p class="throwMessage success">'.__( 'Your Wordpress Intallation has been succefully deleted !', SELF :: textDomain ).'</p>';
						}
						else{
							echo '<p class="throwMessage error">'.__( 'You can\'t complete this action. Please try again.', SELF :: textDomain ).'</p>';
						}

					}
					elseif( isset( $_POST['tryUninstallSettings'] ) && $_POST['tryUninstallSettings'] == 'letsgo' ){
						if( is_array( $_POST['uninstallTable'] ) ){
							$listaTablesToDelete = implode( ' | ' , $_POST['uninstallTable'] );
							echo '<p class="throwMessage warning">'.__( 'Are you sure you want to delete those tables and uninstall your Wordpress ?' , SELF :: textDomain ).'</p>';
							echo '<p class="throwMessage warning">'.$listaTablesToDelete.'</p>';
							echo '<form action="" method="post"/><input type="hidden" name="lastListTablesToDelete" value="'.esc_html($listaTablesToDelete).'"/>';
							wp_nonce_field( 'deleteTables' , 'azed_n' );
							echo '<p><input type="checkbox" name="azeddeletefolder" value="yes" id="azeddeletefolder" /><label for="azeddeletefolder">'.sprintf(__('Do you want to delete the folder "%s" located in "%s" ?' , SELF :: textDomain ) , basename( get_home_path() ) , dirname( get_home_path() ) ).'</label></p>';
							submit_button( __( 'Yes, i\'m sure !' , SELF :: textDomain ) );
							echo '</form>';
						}
					}
					else{
						echo '<h2>'.__( 'Select tables to delete :' , SELF :: textDomain ).'</h2>';
						echo '<form action="" method="post">';
						echo '<input type="hidden" name="tryUninstallSettings" value="letsgo"/>';
						echo '<div class="azedRow">';

						$rows = $wpdb -> get_results("SHOW TABLES LIKE '{$wpdb->prefix}%'" , ARRAY_N );
						foreach ( $rows as $tableSelect )
						{
							$tableSelectName = $tableSelect[0];
							echo '<div class="fleft-2 mh50"><p class="azedWrapper"><input type="checkbox" name="uninstallTable[]" id="uninstallTable_'.esc_html($tableSelectName).'" '.
							'value="'.esc_html($tableSelectName).'" checked="checked"/>'.
							'<label for="uninstallTable_'.esc_html($tableSelectName).'">'.esc_html($tableSelectName).'</label></p></div>';
						}
						echo '</div>';
						submit_button( __( 'Delete those tables' , SELF :: textDomain ) );
						echo '</form>';
					}
					echo '</div>';
				}
				else{
					echo '<p class="throwMessage error">'.__( 'You don\'t have access to this plugin !.', SELF :: textDomain ).'</p>';
				}
		} // END uninstall_wordpress for display and exec the form

		function plugin_init() {
		} // END init function for loading textDomain
	}
}
add_action('init',array('AzedWordpressUninstaller', 'init'));

?>
