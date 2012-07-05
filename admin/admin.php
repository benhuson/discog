<?php

class Discography_Admin {
	
	var $options_page;
	var $settings;
	var $help;
	
	/**
	 * Constructor
	 */
	function Discography_Admin() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_discography_scripts' ) );
		add_action( 'admin_head', array( $this, 'admin_head_post' ) );
		add_action( 'admin_menu', array( $this, 'add_discography_options_page' ) ); 
	}
	
	/**
	 * P2P Install Message
	 * Displays a message on the settings page if the user
	 * needs to install or activate the Posts 2 posts plugin.
	 */
	function p2p_install_message() {
		global $Discography;
		$plugin_file = 'posts-to-posts/posts-to-posts.php';
		if ( $Discography->p2p_is_installed() ) {
			if ( ! $Discography->p2p_is_active() ) {
				$install_msg = '<a href="' . wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin_file . '&amp;plugin_status=all&amp;paged=1&amp;s=posts-to-posts', 'activate-plugin_' . $plugin_file ) . '">' . __( 'activate the Posts 2 Posts plugin', 'discography' ) . '</a>';
				return sprintf( __( 'In order to associate songs with albums, please %s.', 'discography' ), $install_msg );
			}
		} else {
			$install_msg = __( 'install the Posts 2 Posts plugin', 'discography' );
			if ( current_user_can( 'install_plugins' ) ) {
				$install_msg = '<a href="' . wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=posts-to-posts' ), 'install-plugin_posts-to-posts' ) . '">' . $install_msg . '</a>';
			}
			return sprintf( __( 'In order to associate songs with albums, please %s.', 'discography' ), $install_msg );
		}
		return '';
	}
	
	/**
	 * P2P Install Admin Message
	 * Displays a message on the settings page if the user
	 * needs to install or activate the Posts 2 posts plugin.
	 */
	function p2p_install_admin_message() {
		$msg = $this->p2p_install_message();
		if ( ! empty( $msg ) ) {
			echo '<div id="message" class="updated" style="margin:15px 0;"><p>' . $msg . '</p></div>';
		}
	}
	
	/**
	 * Admin Init
	 */
	function admin_init() {
		$this->include_admin_files();
		
		// Register Settings
		if ( function_exists( 'register_setting' ) ) {
			register_setting( 'discography-options', 'wp_geo_options', '' );
		}
		
		$this->settings = new Discography_Settings();
		$this->help = new Discography_Help();
	}
	
	/**
	 * Admin Head
	 * Activate DatePicker JS on admin pages.
	 *
	 * @todo Only do this on pages where it's required.
	 */
	function admin_head_post() {
		echo '
			<script>
			jQuery(function() {
				if (jQuery( "#discography_song_details_recording_date" ).length > 0) {
					jQuery( "#discography_song_details_recording_date" ).datepicker();
				}
				if (jQuery( "#discography_album_details_release_date" ).length > 0) {
					jQuery( "#discography_album_details_release_date" ).datepicker();
				}
			});
			</script>
			';
	}
	
	/**
	 * Include Admin Files
	 */
	function include_admin_files() {
		include_once( DISCOGRAPHY_DIR . 'admin/settings.php' );
		include_once( DISCOGRAPHY_DIR . 'admin/help.php' );
	}
	
	/**
	 * Enqueue Discography Scripts
	 *
	 * @param string $hook Page hook name.
	 */
	function admin_enqueue_discography_scripts( $hook ) {
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) && in_array( get_post_type(), array( 'discography-album', 'discography-song' ) ) ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'discography_playtagger', DISCOGRAPHY_URL . 'css/jquery-ui/jquery-ui-1.8.20.custom.css' );
		}
	}
	
	/**
	 * Add Options Page
	 * Adds Discography settings page menu item.
	 */
	function add_discography_options_page() {
		if ( function_exists( 'add_options_page' ) ) {
			$this->options_page = add_options_page( __( 'Discography', 'discography' ), __( 'Discography', 'discography' ), 'manage_options', DISCOGRAPHY_FILE, array( $this, 'options_page' ) );
		}
	}
	
	/**
	 * Options Page
	 * Outputs the options page.
	 */
	function options_page() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		
		echo '<div class="wrap">';
		echo '<div id="icon-themes" class="icon32" style="background-image:url(' . DISCOGRAPHY_URL . 'images/icons/icon32.png);"><br /></div>';
		echo '<h2>' . __( 'Discography Settings', 'discography' ) . '</h2>';
		$this->p2p_install_admin_message();
		echo '<form action="options.php" method="post">';
		settings_fields( 'discography_options' );
		do_settings_sections( 'discography' );
		echo '<p class="submit">
				<input type="submit" name="submit" id="submit" class="button-primary" value="' . __( 'Save Changes', 'discography' ) . '">
				<input type="reset" name="reset" id="reset" class="button" value="' . __( 'Reset Options', 'discography' ) . '">
			</p>';
		echo '</form>';
	}

	/**
	 * Manage Discography Album Columns
	 *
	 * @param array $columns Columns.
	 * @return array Columns.
	 */
	function manage_edit_discography_album_columns( $columns ) {
		unset( $columns['author'] );
		unset( $columns['date'] );
		$new_columns = array();
		foreach ( $columns as $key => $val ) {
			$new_columns[$key] = $val;
			if ( $key == 'title' ) {
				$new_columns['discography_category'] = __( 'Categories', 'discography' );
				if ( function_exists( 'p2p_type' ) ) {
					$new_columns['discography_songs'] = __( 'Songs', 'discography' );
				}
			}
		}
		return $new_columns;
	}
	
	/**
	 * Manage Discography Song Columns
	 *
	 * @param array $columns Columns.
	 * @return array Columns.
	 */
	function manage_edit_discography_song_columns( $columns ) {
		unset( $columns['author'] );
		unset( $columns['date'] );
		$new_columns = array();
		foreach ( $columns as $key => $val ) {
			$new_columns[$key] = $val;
			if ( $key == 'title' ) {
				if ( function_exists( 'p2p_type' ) ) {
					$new_columns['discography_album'] = __( 'Albums', 'discography' );
				}
				$new_columns['discography_download'] = __( 'Download', 'discography' );
				$new_columns['discography_streaming'] = __( 'Streaming', 'discography' );
			}
		}
		return $new_columns;
	}
	
	/**
	 * Manage Post Column Content
	 *
	 * @param string $name Column name.
	 */
	function manage_posts_custom_column( $name ) {
		global $Discography, $post;
		$details = $Discography->get_discography_song_meta_details( $post->ID );
		switch ( $name ) {
			case 'discography_category':
				$output = array();
				$terms = wp_get_object_terms( $post->ID, 'discography_category' );
				foreach ( $terms as $term ) {
					$output[] = '<a href="' . get_edit_term_link( $term->term_id, 'discography_category', 'discography-album' ) . '">' . $term->name . '</a>';
				}
				echo implode( ', ', $output );
				break;
			case 'discography_songs':
				echo $Discography->count_album_songs( $post->ID );
				break;
			case 'discography_download':
				if ( $details['allow_download'] == 1 )
					echo __( 'Yes', 'discography' );
				break;
			case 'discography_streaming':
				if ( $details['allow_streaming'] == 1 )
					echo __( 'Yes', 'discography' );
				break;
			case 'discography_album':
				if ( function_exists( 'p2p_type' ) ) {
					$connected = p2p_type( 'discography_album' )->get_connected( $post );
					if ( $connected->have_posts() ) :
						$count = 0;
						foreach ( $connected->posts as $connect ) {
							if ( $count > 0 ) {
								echo ', ';
							}
							edit_post_link( get_the_title( $connect->ID ), '', '', $connect->ID );
							$count++;
						}
					endif;
				}
				break;
		}
	}
	
}

?>