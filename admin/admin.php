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
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_and_styles' ) );
		add_action( 'admin_head', array( $this, 'admin_head_post' ) );
		add_action( 'admin_menu', array( $this, 'add_discography_options_page' ) ); 
		add_action( 'admin_menu', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'discography_category_add_form_fields', array( $this, 'attachment_fields_to_add' ), 10, 2 );
		add_action( 'discography_category_edit_form_fields', array( $this, 'attachment_fields_to_edit' ), 10, 2 );
		add_action( 'created_discography_category', array( $this, 'attachment_fields_to_save' ), 10, 2 );   
		add_action( 'edited_discography_category', array( $this, 'attachment_fields_to_save' ), 10, 2 );
		add_filter( 'manage_edit-discography-album_columns', array( $this, 'manage_discography_album_columns' ) );
		add_action( 'manage_discography-album_posts_custom_column', array( $this, 'show_discography_album_columns' ) );
		add_filter( 'manage_edit-discography-song_columns', array( $this, 'manage_discography_song_columns' ) );
		add_action( 'manage_discography-song_posts_custom_column', array( $this, 'show_discography_song_columns' ) );
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
	 * Admin Enqueue Scripts and Styles
	 *
	 * @param string $hook Page hook name.
	 */
	function admin_enqueue_scripts_and_styles( $hook ) {
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) && in_array( get_post_type(), array( 'discography-album', 'discography-song' ) ) ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'discography_playtagger', DISCOGRAPHY_URL . 'css/jquery-ui/jquery-ui-1.8.20.custom.css' );
		}
		if ( $hook == 'edit.php' && isset( $_GET['post_type'] ) && 'discography-song' == $_GET['post_type'] ) {
			$discography_options = get_option( 'discography_options' );
			if ( $discography_options['delicious_player'] == 1 ) {
				wp_enqueue_script( 'discography_playtagger', DISCOGRAPHY_URL . '/js/playtagger.js', array( 'jquery' ) );
			}
			wp_enqueue_style( 'discography_playtagger', DISCOGRAPHY_URL . '/css/discography.css' );
		}
	}
	
	/**
	 * Add Meta Boxes
	 */
	function add_meta_boxes() {
		global $Discography;
		if ( function_exists( 'add_meta_box' ) ) {
			add_meta_box( 'discography_album', __( 'Album Details', 'discography' ), array( $this, 'album_details_meta_box_inner' ), 'discography-album', 'normal', 'core' );
			add_meta_box( 'discography_album_purchase', __( 'Purchase Details', 'discography' ), array( $this, 'album_purchase_meta_box_inner' ), 'discography-album', 'normal', 'core' );
			add_meta_box( 'discography_song', __( 'Song Details', 'discography' ), array( $this, 'song_details_meta_box_inner' ), 'discography-song', 'normal', 'core' );
			add_meta_box( 'discography_song_purchase', __( 'Purchase Details', 'discography' ), array( $this, 'song_purchase_meta_box_inner' ), 'discography-song', 'normal', 'core' );
			add_meta_box( 'discography_song_lyrics', __( 'Lyrics', 'discography' ), array( $this, 'song_lyrics_meta_box_inner' ), 'discography-song', 'normal', 'core' );
			if ( ! $Discography->p2p_is_installed() || ! $Discography->p2p_is_active() ) {
				add_meta_box( 'discography_install_p2p', __( 'Songs', 'discography' ), array( $this, 'install_p2p_meta_box_inner' ), 'discography-album', 'side' );
				add_meta_box( 'discography_install_p2p', __( 'Albums', 'discography' ), array( $this, 'install_p2p_meta_box_inner' ), 'discography-song', 'side' );
			}
		}
	}
	
	/**
	 * Album Details Meta Box
	 */
	function album_details_meta_box_inner() {
		global $Discography, $post;
		$options = get_option( 'discography_options' );
		$details = $Discography->get_discography_album_meta_details( $post->ID );
		if ( empty( $details['artist'] ) && $post->post_status == 'auto-draft' ) {
			$details['artist'] = $options['artist'];
		}
		
		// Use nonce for verification
		echo '<input type="hidden" name="album_details_noncename" id="album_details_noncename" value="' . wp_create_nonce( plugin_basename( DISCOGRAPHY_FILE ) ) . '" />';
		
		// The actual fields for data entry
		echo '<table class="form-table" style="width: 100%;" cellspacing="2" cellpadding="5"><tbody>';
		echo '<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_details_artist">' . __( 'Album Artist', 'discography' ) . '</label></th>
				<td><input type="text" name="discography_album_details[artist]" id="discography_album_details_artist" size="50" value="' . $details['artist'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_details_is_album">' . __( 'Is this an album?', 'discography' ) . '</label></th>
				<td><select name="discography_album_details[is_album]" id="discography_album_details_is_album">
					<option value="1" ' . selected( $details['is_album'], '1', false ) . '>' . __( 'Yes', 'discography' ) . '</option>
					<option value="0" ' . selected( $details['is_album'], '0', false ) . '>' . __( 'No', 'discography' ) . '</option>
				</select></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_details_show_on_pages">' . __( 'Show this on song pages?', 'discography' ) . '</label></th>
				<td><select name="discography_album_details[show_on_pages]" id="discography_album_details_show_on_pages">
					<option value="1" ' . selected( $details['show_on_pages'], '1', false ) . '>' . __( 'Yes', 'discography' ) . '</option>
					<option value="0" ' . selected( $details['show_on_pages'], '0', false ) . '>' . __( 'No', 'discography' ) . '</option>
				</select></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_details_release_date">' . __( 'Release Date', 'discography' ) . '</label></th>
				<td><input type="text" name="discography_album_details[release_date]" id="discography_album_details_release_date" size="10" value="' . $details['release_date'] . '" style="width: 25%"></td>
			</tr>';
		echo '</tbody></table>';
	}
	
	/**
	 * Album Purchase Meta Box
	 */
	function album_purchase_meta_box_inner() {
		global $Discography, $post;
		$options = get_option( 'discography_options' );
		$currency_symbol = ! empty( $options['currency_symbol'] ) ? $options['currency_symbol'] . ' ' : '';
		$details = $Discography->get_discography_album_meta_purchase( $post->ID );
		if ( empty( $details['price'] ) && $post->post_status == 'auto-draft' ) {
			$details['price'] = $options['group_price'];
		}
		if ( empty( $details['download_price'] ) && $post->post_status == 'auto-draft' ) {
			$details['download_price'] = $options['group_price'];
		}
		
		// Use nonce for verification
		echo '<input type="hidden" name="album_purchase_noncename" id="album_purchase_noncename" value="' . wp_create_nonce( plugin_basename( DISCOGRAPHY_FILE ) ) . '" />';
		
		// The actual fields for data entry
		echo '<table class="form-table" style="width: 100%;" cellspacing="2" cellpadding="5"><tbody>';
		echo '<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_purchase_price">' . __( 'Physical Copy Price', 'discography' ) . '</label></th>
				<td>' . $currency_symbol . '<input type="text" name="discography_album_purchase[price]" id="discography_album_purchase_price" size="10" value="' . $details['price'] . '" style="width: 25%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_purchase_purchase_link">' . __( 'Purchase Physical Copy Link', 'discography' ) . '</label></th>
				<td><input type="text" name="discography_album_purchase[purchase_link]" id="discography_album_purchase_purchase_link" size="50" value="' . $details['purchase_link'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_purchase_download_price">' . __( 'Download Price', 'discography' ) . '</label></th>
				<td>' . $currency_symbol . '<input type="text" name="discography_album_purchase[download_price]" id="discography_album_purchase_download_price" size="10" value="' . $details['download_price'] . '" style="width: 25%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_purchase_purchase_download_link">' . __( 'Purchase Download Link', 'discography' ) . '</label></th>
				<td><input type="text" name="discography_album_purchase[purchase_download_link]" id="discography_album_purchase_purchase_download_link" size="50" value="' . $details['purchase_download_link'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_purchase_free_download_link">' . __( 'Free Download Link', 'discography' ) . '</label></th>
				<td><input type="text" name="discography_album_purchase[free_download_link]" id="discography_album_purchase_free_download_link" size="50" value="' . $details['free_download_link'] . '" style="width: 95%"></td>
			</tr>';
		echo '</tbody></table>';
	}
	
	/**
	 * Song Details Meta Box
	 */
	function song_details_meta_box_inner() {
		global $Discography, $post;
		$details = $Discography->get_discography_song_meta_details( $post->ID );
		
		// Use nonce for verification
		echo '<input type="hidden" name="song_details_noncename" id="song_details_noncename" value="' . wp_create_nonce( plugin_basename( DISCOGRAPHY_FILE ) ) . '" />';
		
		// The actual fields for data entry
		echo '<table class="form-table" style="width: 100%;" cellspacing="2" cellpadding="5"><tbody>';
		echo '<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_recording_artist">' . __( 'Recording Artist', 'discography' ) . '</label></th>
				<td><input type="text" name="discography_song_details[recording_artist]" id="discography_song_details_recording_artist" size="50" value="' . $details['recording_artist'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_recording_date">' . __( 'Recording Date', 'discography' ) . '</label></th>
				<td><input type="text" name="discography_song_details[recording_date]" id="discography_song_details_recording_date" size="10" value="' . $details['recording_date'] . '" style="width: 25%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_composer">' . __( 'Composer', 'discography' ) . '</label></th>
				<td><input type="text" name="discography_song_details[composer]" id="discography_song_details_composer" size="50" value="' . $details['composer'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_track_length">' . __( 'Track Length', 'discography' ) . '</label></th>
				<td><input type="text" name="discography_song_details[track_length]" id="discography_song_details_track_length" size="10" value="' . $details['track_length'] . '" style="width: 25%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_streaming">' . __( 'Allow streaming?', 'discography' ) . '</label></th>
				<td><select name="discography_song_details[allow_streaming]" id="discography_song_details_streaming">
					<option value="1" ' . selected( $details['allow_streaming'], '1', false ) . '>' . __( 'Yes', 'discography' ) . '</option>
					<option value="0" ' . selected( $details['allow_streaming'], '0', false ) . '>' . __( 'No', 'discography' ) . '</option>
				</select></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_allow_download">' . __( 'Allow download?', 'discography' ) . '</label></th>
				<td><select name="discography_song_details[allow_download]" id="discography_song_details_allow_download">
					<option value="1" ' . selected( $details['allow_download'], '1', false ) . '>' . __( 'Yes', 'discography' ) . '</option>
					<option value="0" ' . selected( $details['allow_download'], '0', false ) . '>' . __( 'No', 'discography' ) . '</option>
				</select></td>
			</tr>';
		echo '</tbody></table>';
	}
	
	/**
	 * Song Purchase Meta Box
	 */
	function song_purchase_meta_box_inner() {
		global $Discography, $post;
		$options = get_option( 'discography_options' );
		$currency_symbol = ! empty( $options['currency_symbol'] ) ? $options['currency_symbol'] . ' ' : '';
		$purchase = $Discography->get_discography_song_meta_purchase( $post->ID );
		if ( empty( $purchase['price'] ) && $post->post_status == 'auto-draft' ) {
			$purchase['price'] = $options['song_price'];
		}
		
		// Use nonce for verification
		echo '<input type="hidden" name="song_purchase_noncename" id="song_purchase_noncename" value="' . wp_create_nonce( plugin_basename( DISCOGRAPHY_FILE ) ) . '" />';
		
		// The actual fields for data entry
		echo '<table class="form-table" style="width: 100%;" cellspacing="2" cellpadding="5"><tbody>';
		echo '<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_purchase_price">' . __( 'Price', 'discography' ) . '</label></th>
				<td>' . $currency_symbol . '<input type="text" name="discography_song_purchase[price]" id="discography_song_purchase_price" size="10" value="' . $purchase['price'] . '" style="width: 25%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_purchase_purchase_download_link">' . __( 'Purchase Download Link', 'discography' ) . '</label></th>
				<td><input type="text" name="discography_song_purchase[purchase_download_link]" id="discography_song_purchase_purchase_download_link" size="50" value="' . $purchase['purchase_download_link'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_purchase_free_download_link">' . __( 'Free Download/Streaming Link', 'discography' ) . '</label></th>
				<td><input type="text" name="discography_song_purchase[free_download_link]" id="discography_song_purchase_free_download_link" size="50" value="' . $purchase['free_download_link'] . '" style="width: 95%"></td>
			</tr>';
		echo '</tbody></table>';
	}
	
	/**
	 * Song Lyrics Meta Box
	 */
	function song_lyrics_meta_box_inner() {
		global $post;
		$lyrics = get_post_meta( $post->ID, '_discography_song_lyrics', true );
		
		// Use nonce for verification
		echo '<input type="hidden" name="song_lyrics_noncename" id="song_lyrics_noncename" value="' . wp_create_nonce( plugin_basename( DISCOGRAPHY_FILE ) ) . '" />';
		
		// The actual fields for data entry
		echo '<label class="screen-reader-text" for="discography_song_lyrics">' . __( 'Lyrics', 'discography' ) . '</label>';
		echo '<textarea rows="15" cols="40" name="discography_song_lyrics" tabindex="6" id="discography_song_lyrics" style="margin: 0px; width: 98%;">' . $lyrics . '</textarea>';
	}
	
	/**
	 * Install Posts 2 Posts Meta Box
	 */
	function install_p2p_meta_box_inner() {
		global $post;
		echo '<p>' . $this->p2p_install_message() . '</p>';
	}

	/**
	 * Save Post
	 *
	 * @param int $post_id Post ID.
	 */
	function save_post( $post_id ) {
		global $Discography, $wpdb;
		
		// Verify if this is an auto save routine. 
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;
		
		if ( ! isset( $_POST['post_type'] ) )
			return;
		
		if ( 'discography-album' == $_POST['post_type'] && current_user_can( 'edit_page', $post_id ) ) {
			// Save Album Details
			if ( wp_verify_nonce( $_POST['album_details_noncename'], plugin_basename( DISCOGRAPHY_FILE ) ) ) {
				if ( isset( $_POST['discography_album_details'] ) ) {
					$details = wp_parse_args( $_POST['discography_album_details'], $Discography->get_discography_album_meta_details( $post_id ) );
					update_post_meta( $post_id, '_discography_album_details', $details );
				}
			}
			// Save Album Purchase Details
			if ( wp_verify_nonce( $_POST['album_purchase_noncename'], plugin_basename( DISCOGRAPHY_FILE ) ) ) {
				if ( isset( $_POST['discography_album_purchase'] ) ) {
					$purchase = wp_parse_args( $_POST['discography_album_purchase'], $Discography->get_discography_album_meta_purchase( $post_id ) );
					update_post_meta( $post_id, '_discography_album_purchase', $purchase );
				}
			}
		} elseif ( 'discography-song' == $_POST['post_type'] && current_user_can( 'edit_page', $post_id ) ) {
			// Save Song Details
			if ( wp_verify_nonce( $_POST['song_details_noncename'], plugin_basename( DISCOGRAPHY_FILE ) ) ) {
				if ( isset( $_POST['discography_song_details'] ) ) {
					$details = wp_parse_args( $_POST['discography_song_details'], $Discography->get_discography_song_meta_details( $post_id ) );
					update_post_meta( $post_id, '_discography_song_details', $details );
				}
			}
			// Save Song Purchase Details
			if ( wp_verify_nonce( $_POST['song_purchase_noncename'], plugin_basename( DISCOGRAPHY_FILE ) ) ) {
				if ( isset( $_POST['discography_song_purchase'] ) ) {
					$purchase = wp_parse_args( $_POST['discography_song_purchase'], $Discography->get_discography_song_meta_purchase( $post_id ) );
					update_post_meta( $post_id, '_discography_song_purchase', $purchase );
				}
			}
			// Save Song Lyrics
			if ( wp_verify_nonce( $_POST['song_lyrics_noncename'], plugin_basename( DISCOGRAPHY_FILE ) ) ) {
				if ( isset( $_POST['discography_song_lyrics'] ) ) {
					update_post_meta( $post_id, '_discography_song_lyrics', wp_kses( $_POST['discography_song_lyrics'], array( 'em' => array(), 'strong' => array(), 'small' => array() ) ) );
				}
			}
		}
		return;
	}
	
	/**
	 * Add Category Form
	 *
	 * @todo Check saving these values. Currently not working.
	 *
	 * @param string $taxonomy Taxonomy.
	 */
	function attachment_fields_to_add( $taxonomy ) {
		global $Discography;
		$options = $Discography->get_discography_category_options();
		?>
		<div class="form-field">
			<label for="discography_category_group_order"><?php _e( 'Order', 'discography' ); ?></label>
			<input name="discography_category[group_order]" type="text" size="4" id="discography_category_group_order" value="0" style="width:auto;" />
		</div>
		<div class="form-field">
			<label for="discography_category_group_sort_by"><?php _e( 'Sort albums in this category by', 'discography' ); ?></label>
			<select name="discography_category[group_sort_by]" id="discography_category_group_sort_by">
				<option value="title"><?php _e( 'Alphabetical', 'discography' ); ?></option>
				<option value="release_date"><?php _e( 'Release Date', 'discography' ); ?></option>
				<option value="id"><?php _e( 'ID', 'discography' ); ?></option>
				<option value="order"><?php _e( 'Custom', 'discography' ); ?></option>
			</select>
		</div>
		<div class="form-field">
			<label for="discography_category_group_sort"><?php _e( 'Ordering Direction', 'discography' ); ?></label>
			<select name="discography_category[group_sort]" id="discography_category_group_sort">
				<option value="ASC"><?php _e( 'Ascending', 'discography' ); ?></option>
				<option value="DESC"><?php _e( 'Descending', 'discography' ); ?></option>
			</select>
		</div>
		<?php
	}
	
	/**
	 * Edit Category Form
	 *
	 * @param object $term Term object.
	 * @param string $taxonomy Taxonomy.
	 */
	function attachment_fields_to_edit( $term, $taxonomy ) {
		global $Discography;
		$options = $Discography->get_discography_category_options( $term );
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="discography_category_group_order"><?php _e( 'Order', 'discography' ); ?></label>
			</th>
			<td>
				<input name="discography_category[group_order]" type="text" size="4" id="discography_category_group_order" value="<?php echo $options['group_order']; ?>" style="width:auto;" />
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="discography_category_group_sort_by"><?php _e( 'Sort albums in this category by', 'discography' ); ?></label>
			</th>
			<td>
				<select name="discography_category[group_sort_by]" id="discography_category_group_sort_by">
					<option value="title" <?php selected( 'title', $options['group_sort_by'] ); ?>><?php _e( 'Alphabetical', 'discography' ); ?></option>
					<option value="release_date" <?php selected( 'release_date', $options['group_sort_by'] ); ?>><?php _e( 'Release Date', 'discography' ); ?></option>
					<option value="id" <?php selected( 'id', $options['group_sort_by'] ); ?>><?php _e( 'ID', 'discography' ); ?></option>
					<option value="order" <?php selected( 'order', $options['group_sort_by'] ); ?>><?php _e( 'Custom', 'discography' ); ?></option>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="discography_category_group_sort"><?php _e( 'Ordering Direction', 'discography' ); ?></label>
			</th>
			<td>
				<select name="discography_category[group_sort]" id="discography_category_group_sort">
					<option value="ASC" <?php selected( 'ASC', $options['group_sort'] ); ?>><?php _e( 'Ascending', 'discography' ); ?></option>
					<option value="DESC" <?php selected( 'DESC', $options['group_sort'] ); ?>><?php _e( 'Descending', 'discography' ); ?></option>
				</select>
			</td>
		</tr>
		<?php
	}
	
	/**
	 * Save Category Form
	 *
	 * @param object $post Post.
	 * @param object $attachment Attachment.
	 * @return object Post.
	 */
	function attachment_fields_to_save( $term_id, $tt_id ) {
		global $Discography;
		if ( ! $term_id )
			return;
		
		// @todo Add in nonce verification
		if ( empty( $_POST ) || ! isset( $_POST['taxonomy'] ) )
			return;
		
		$term = get_term( $term_id, 'discography_category' );
		$options = wp_parse_args( $_POST['discography_category'], $Discography->get_discography_category_options( $term ) );
		
		update_metadata( $_POST['taxonomy'], $term_id, 'group_order', $options['group_order'] );
		update_metadata( $_POST['taxonomy'], $term_id, 'group_sort_by', $options['group_sort_by'] );
		update_metadata( $_POST['taxonomy'], $term_id, 'group_sort', $options['group_sort'] );
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
	 * Add categories and song count to manage albums page
	 */
	function manage_discography_album_columns( $columns ) {
		$new_columns = array();
		unset( $columns['author'] );
		foreach ( $columns as $key => $val ) {
			if ( $key == 'date' ) {
				$new_columns['author'] = __( 'Author', 'discography' );
			}
			$new_columns[$key] = $val;
			if ( $key == 'title' ) {
				$new_columns['discography_category'] = __( 'Categories', 'discography' );
				if ( function_exists( 'p2p_type' ) ) {
					$new_columns['discography-song'] = __( 'Songs', 'discography' );
				}
			}
		}
		return $new_columns;
	}
	
	/**
	 * Show categories and song count on manage albums page
	 */
	function show_discography_album_columns( $name ) {
		global $Discography, $post;
		switch ( $name ) {
			case 'discography_category':
				$terms = get_the_terms( $post->ID, 'discography_category' );
				if ( is_wp_error( $terms ) )
					break;
				if ( empty( $terms ) )
					break;
				
				$links = array();
				foreach ( $terms as $term ) {
					$link = get_edit_term_link( $term, 'discography_category', $post->post_type );
					if ( is_wp_error( $link ) )
						continue;
					$links[] = '<a href="' . esc_url( $link ) . '" rel="tag">' . $term->name . '</a>';
				}
				if ( count( $links ) > 0 ) {
					echo implode( ', ', $links );
				}
				break;
			case 'discography-song':
				if ( function_exists( 'p2p_type' ) ) {
					echo $Discography->count_album_songs( $post->ID );
				}
				break;
		}
	}
	
	/**
	 * Add albums to manage songs page
	 */
	function manage_discography_song_columns( $columns ) {
		$new_columns = array();
		unset( $columns['author'] );
		foreach ( $columns as $key => $val ) {
			if ( $key == 'date' ) {
				$new_columns['author'] = __( 'Author', 'discography' );
			}
			$new_columns[$key] = $val;
			if ( $key == 'title' ) {
				if ( function_exists( 'p2p_type' ) ) {
					$new_columns['discography-album'] = __( 'Albums', 'discography' );
					$new_columns['discography_download'] = __( 'Download', 'discography' );
					$new_columns['discography_streaming'] = __( 'Streaming', 'discography' );
				}
			}
		}
		return $new_columns;
	}
	
	/**
	 * Show albums on manage songs page
	 */
	function show_discography_song_columns( $name ) {
		global $Discography, $post;
		$details = $Discography->get_discography_song_meta_details( $post->ID );
		$purchase = $Discography->get_discography_song_meta_purchase( $post->ID );
		switch ( $name ) {
			case 'discography-album':
				if ( function_exists( 'p2p_type' ) ) {
					$connected = p2p_type( 'discography_album' )->get_connected( $post );
					if ( $connected->have_posts() ) :
						$albums = array();
						foreach ( $connected->posts as $connect ) {
							$albums[] = '<a href="' . get_edit_post_link( $connect->ID ) . '">' . get_the_title( $connect->ID ) . '</a>';
						}
						echo implode( ', ', $albums );
					endif;
				}
				break;
			case 'discography_download':
				if ( $details['allow_download'] == 1 && ! empty( $purchase['purchase_download_link'] ) )
					echo '<a href="' . $purchase['purchase_download_link'] . '">' . __( 'Yes', 'discography' ) . '</a>';
				break;
			case 'discography_streaming':
				if ( $details['allow_streaming'] == 1 )
					echo $Discography->player( $post );
				break;
		}
	}
	
}

?>