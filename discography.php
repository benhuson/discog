<?php

/*
Plugin Name: Discography
Plugin URI: https://github.com/benhuson/discography
Description: A management system to allow bands and musicians to store and display information about their albums and songs. Based on the <a href="http://wordpress.org/extend/plugins/discography/">Discography</a> plugin by Dan Coulter.
Author: Ben Huson
Version: 1.0.dev
Author URI: http://www.benhuson.co.uk
*/

/*
@todo Move some functionality to admin file
@todo Order categories
*/

// Plugin directory and URL paths
define( 'DISCOGRAPHY_SUBDIR', '/' . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) );
define( 'DISCOGRAPHY_URL', plugins_url( DISCOGRAPHY_SUBDIR ) );
define( 'DISCOGRAPHY_DIR', plugin_dir_path( __FILE__ ) );
define( 'DISCOGRAPHY_FILE', __FILE__ );

// Version
require_once( DISCOGRAPHY_DIR . 'version.php' );

class Discography {
	
	var $admin;
	var $db_schema;
	var $shortcodes;
	
	/**
	 * Constructor
	 */
	function Discography() {
		add_action( 'init', array( $this, 'setup_db' ), 2 );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'wp_loaded', array( $this, 'register_post_relationships' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts_and_styles' ) );
		add_action( 'discography_category_add_form_fields', array( $this, 'attachment_fields_to_add' ), 10, 2 );
		add_action( 'discography_category_edit_form_fields', array( $this, 'attachment_fields_to_edit' ), 10, 2 );
		add_action( 'edited_discography_category', array( $this, 'attachment_fields_to_save' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_filter( 'the_content', array( $this, 'overview_content' ) );
		add_filter( 'the_content', array( $this, 'album_content' ) );
		add_filter( 'the_content', array( $this, 'song_content' ) );
		add_filter( 'post_class', array( $this, 'post_class' ), 10, 3 );
		add_filter( 'http_request_args', array( $this, 'prevent_plugin_auto_update' ), 5, 2 );
		
		// Shortcodes
		require_once( DISCOGRAPHY_DIR . 'includes/shortcodes.php' );
		$this->shortcodes = new Discography_Shortcodes();
		
		// Admin
		if ( is_admin() ) {
			require_once( DISCOGRAPHY_DIR . 'admin/admin.php' );
			$this->admin = new Discography_Admin();
		}
	}
	
	/**
	 * P2P Is Active
	 * Checks the Posts 2 Posts Plugin is installed and active.
	 */
	function p2p_is_active() {
		if ( is_plugin_active( 'posts-to-posts/posts-to-posts.php' ) ) {
			return true;
		}
		return false;
	}
	
	/**
	 * P2P Is Installed
	 * Checks the Posts 2 Posts Plugin is installed.
	 */
	function p2p_is_installed() {
		$installed_plugin = get_plugins( '/posts-to-posts' );
		if ( ! empty( $installed_plugin ) ) {
			return true;
		}
		return false;
	}
	
	/**
	 * Enqueue Scripts and Styles
	 */
	function wp_enqueue_scripts_and_styles() {
		$discography_options = get_option( 'discography_options' );
		// Only load styles, javascript and Delicious player if active and on an album/song page
		if ( $this->is_discography_page() ) {
			if ( $discography_options['delicious_player'] == 1 ) {
				wp_enqueue_script( 'discography_playtagger', plugins_url( 'js/playtagger.js', __FILE__ ), array( 'jquery' ) );
			}
			wp_enqueue_style( 'discography_playtagger', plugins_url( 'css/discography.css', __FILE__ ) );
		}
	}
	
	/**
	 * Is Discography Page?
	 *
	 * @return bool Is discography page?
	 */
	function is_discography_page() {
		$discography_options = get_option( 'discography_options' );
		if ( ( is_single() && in_array( get_post_type(), array( 'discography-album', 'discography-song' ) ) )
			|| ( is_post_type_archive( 'discography-album' ) || is_post_type_archive( 'discography-song' ) )
			|| is_tax( 'discography_category' )
			|| is_page( $discography_options['page'] )
			) {
			return true;
		}
		return false;
	}
	
	/**
	 * Post Class
	 * Add legacy classes.
	 *
	 * @param array $classes Post classes.
	 * @param string $class Class.
	 * @param int $post_id Post ID.
	 * @return array Classes.
	 */
	function post_class( $classes, $class, $post_id ) {
		if ( is_single() && 'discography-song' == get_post_type() ) {
			$classes[] = 'song';
			$classes[] = 'song-page';
		}
		return $classes;
	}
	
	/**
	 * Player
	 *
	 * @param object $post Post object.
	 * @param bool $echo Echo?
	 * @return string Output.
	 */
	function player( $post, $echo = false ) {
		$output   = '';
		$discography_options = get_option( 'discography_options' );
		$details  = get_post_meta( $post->ID, '_discography_song_details', true );
		$purchase = get_post_meta( $post->ID, '_discography_song_purchase', true );
		if ( $details['allow_streaming'] == 1 && ! empty( $purchase['free_download_link'] ) ) {
			if ( $discography_options['delicious_player'] == 1 ) {
				$output = '<a class="delicious" href="' . $purchase['free_download_link'] . '"></a>';
			} else {
				// @todo Could the link just be done with urlencode() ?
				$output = '<object type="application/x-shockwave-flash" data="' . plugins_url( 'swf/player_mp3_maxi.swf' , __FILE__ ) . '" width="25" height="16">
						<param name="movie" value="' . plugins_url( 'swf/player_mp3_maxi.swf' , __FILE__ ) . '">
						<param name="FlashVars" value="mp3=' . str_replace( ':', '%3A', $purchase['free_download_link'] ) . '&amp;width=25&amp;height=16&amp;showslider=0">
						<param name="wmode" value="transparent">
					</object>';
			}
		}
		if ( $echo )
			echo $output;
		return $output;
	}
	
	/**
	 * Floor Price
	 *
	 * @param number $price Price.
	 * @return number Price.
	 */
	function floor_price( $price ) {
		return ( $price == floor( $price ) ) ? floor( $price ) : $price;
	}
	
	/**
	 * Overview Content
	 *
	 * @param string $content Album string.
	 * @return string Album string.
	 */
	function overview_content( $content ) {
		global $post;
		$discography_options = get_option( 'discography_options' );
		if ( is_page( $discography_options['page'] ) ) {
			
			// Categories
			$terms = get_terms( 'discography_category' );
			if ( count( $terms ) > 1 ) {
				foreach ( $terms as $term ) {
					$url = get_term_link( $term, 'discography_category' );
					$content .= '<h2>' . $term->name . '</h2>';
					
					// Category Albums
					$albums_query = new WP_Query( array(
						'post_type'            => 'discography-album',
						'discography_category' => $term->slug,
						'posts_per_page'       => 10
					) );
					if ( $albums_query->have_posts() ) { 
						$content .= '<ul>';
						while ( $albums_query->have_posts() ) { 
							$albums_query->the_post();
							$content .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
						}
						$content .= '</ul>';
						if ( $albums_query->post_count > 10 ) {
							$content .= '<p><a href="' . $url . '">' . __( 'View more albums...', 'discography' ) . '</a></p>';
						}
					}
					wp_reset_postdata();
				}
			}
			
			// Albums
			$albums_query_args = array(
				'post_type'      => 'discography-album',
				'posts_per_page' => 10
			);
			$term_ids = get_terms( 'discography_category', array( 'fields' => 'ids' ) );
			if ( count( $term_ids ) > 1 ) {
				$albums_query_args['tax_query'] = array(
					array(
						'taxonomy' => 'discography_category',
						'field'    => 'id',
						'terms'    => $term_ids,
						'operator' => 'NOT IN'
					)
				);
			}
			$albums_query = new WP_Query( $albums_query_args );
			if ( $albums_query->have_posts() ) {
				if ( count( $term_ids ) > 1 ) {
					$content .= '<h2>' . __( 'Other Albums', 'discography' ) . '</h2>';
				} else {
					$content .= '<h2>' . __( 'Albums', 'discography' ) . '</h2>';
				}
				$content .= '<ul>';
				while ( $albums_query->have_posts() ) { 
					$albums_query->the_post();
					$content .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
				}
				$content .= '</ul>';
				if ( $albums_query->post_count > 10 ) {
					$content .= '<p><a href="' . get_post_type_archive_link( 'discography-album' ) . '">' . __( 'View all albums...', 'discography' ) . '</a></p>';
				}
			}
			wp_reset_postdata();
			
			// Songs
			$songs_query = new WP_Query( array(
				'post_type'      => 'discography-song',
				'posts_per_page' => 10
			) );
			if ( $songs_query->have_posts() ) { 
				$content .= '<h2>' . __( 'Songs', 'discography' ) . '</h2>';
				$content .= '<table class="group album songs">
						<thead>
							<tr>
								<td class="song-title">' . __( 'Song', 'discography' ) . '</td>
								<td class="hear">' . __( 'Hear', 'discography' ) . '</td>
								<td class="free">' . __( 'Free', 'discography' ) . '</td>
								<td class="price">' . __( 'Price', 'discography' ) . '</td>
								<td class="buy">' . __( 'Buy', 'discography' ) . '</td>
							</tr>
						</thead>
						<tbody>';
				while ( $songs_query->have_posts() ) { 
					$songs_query->the_post();
					$details  = get_post_meta( get_the_ID(), '_discography_song_details', true );
					$purchase = get_post_meta( get_the_ID(), '_discography_song_purchase', true );
					$content .= '<tr class="song">
							<td class="song-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></td>
							<td class="hear icon">';
					if ( $details['allow_streaming'] == 1 && ! empty( $purchase['free_download_link'] ) ) {
						$content .= $this->player( $post );
					}
					$content .= '</td>
							<td class="free icon">';
					if ( $details['allow_download'] == 1 && ! empty( $purchase['free_download_link'] ) ) {
						$content .= '<a onclick="javascript:urchinTracker(\'/free/song/' . $post->post_name . '\');" href="' . $purchase['free_download_link'] . '"><img src="' . plugins_url( 'images/emoticon_smile.png' , __FILE__ ) . '"></a>';
					}
					$content .= '</td>
							<td class="price icon">';
					if ( ! empty( $purchase['price'] ) && $purchase['price'] != 0 ) {
						$content .= '$' . $this->floor_price( $purchase['price'] );
					}
					$content .= '</td>
							<td class="buy icon">';
					if ( ! empty( $purchase['purchase_download_link'] ) ) {
						$content .= '<a onclick="javascript:urchinTracker(\'/buy/song/' . $post->post_name . '\');" href="' . $purchase['purchase_download_link'] . '"><img src="' . plugins_url( 'images/cart_add.png' , __FILE__ ) . '" title="' . __( 'Buy', 'discography' ) . '" alt="' . __( 'Buy', 'discography' ) . '"></a>';
					}
					$content .= '</td>
						</tr>';
				}
				$content .= '
						</tbody>
					</table>';
				if ( $songs_query->post_count > 10 ) {
					$content .= '<p><a href="' . get_post_type_archive_link( 'discography-song' ) . '">' . __( 'View all songs...', 'discography' ) . '</a></p>';
				}
			}
			wp_reset_postdata();
			
		}
		return $content;
	}
	
	/**
	 * Album Content
	 *
	 * @param string $content Album string.
	 * @return string Album string.
	 */
	function album_content( $content ) {
		global $post;
		
		if ( ! is_single() || 'discography-album' != get_post_type() ) {
			return $content;
		}
		
		$details  = $this->get_album_details_meta( $post->ID );
		$purchase = $this->get_album_purchase_meta( $post->ID );
		
		if ( has_post_thumbnail() ) {
			$content = '<div class="albumArt">' . get_the_post_thumbnail( $post->ID, 'thumbnail', array( 'alt' => esc_attr( $post->post_title ) ) ) . '</div>' . $content;
		}
		
		$content .= '<table class="group album songs">
				<thead>
					<tr>
						<td class="song-title">' . __( 'Song', 'discography' ) . '</td>
						<td class="hear">' . __( 'Hear', 'discography' ) . '</td>
						<td class="free">' . __( 'Free', 'discography' ) . '</td>
						<td class="price">' . __( 'Price', 'discography' ) . '</td>
						<td class="buy">' . __( 'Buy', 'discography' ) . '</td>
					</tr>
				</thead>
				<tbody>';
		if ( ! empty( $purchase['purchase_link'] ) && ! empty( $purchase['price'] ) && $purchase['price'] != 0 ) {
			$content .= '<tr class="buy-info">
						<td colspan="3" class="buy-intro">' . __( 'Buy the CD (physical media)', 'discography' ) . '</td>
						<td class="price icon">$' . $this->floor_price( $purchase['price'] ) . '</td>
						<td class="buy icon"><a onclick="javascript:urchinTracker(\'/buy/group-physical/' . $post->post_name . '\');" href="' . $purchase['purchase_link'] . '"><img src="' . plugins_url( 'images/cart_add.png' , __FILE__ ) . '" title="' . __( 'Buy', 'discography' ) . '" alt="' . __( 'Buy', 'discography' ) . '"></a></td>
					</tr>';
		}
		if ( ! empty( $purchase['purchase_download_link'] ) && ! empty( $purchase['download_price'] ) && $purchase['download_price'] != 0 ) {
			$content .= '<tr>
						<td colspan="3" class="buy-intro">' . __( 'Buy the whole album (download)', 'discography' ) . '</td>
						<td class="price icon">$' . $this->floor_price( $purchase['download_price'] ) . '</td>
						<td class="buy icon"><a onclick="javascript:urchinTracker(\'/buy/group-download/' . $post->post_name . '\');" href="' . $purchase['purchase_download_link'] . '"><img src="' . plugins_url( 'images/cart_add.png' , __FILE__ ) . '" title="' . __( 'Buy', 'discography' ) . '" alt="' . __( 'Buy', 'discography' ) . '"></a></td>
					</tr>';
		}
		if ( ! empty( $purchase['free_download_link'] ) ) {
			$content .= '<tr>
						<td colspan="2" class="buy-intro">' . __( 'Download the full album', 'discography' ) . '</td>
						<td class="buy icon"><a onclick="javascript:urchinTracker(\'/free/group/' . $post->post_name . '\');" href="' . $purchase['free_download_link'] . '"><img src="' . plugins_url( 'images/emoticon_smile.png' , __FILE__ ) . '" title="' . __( 'Download', 'discography' ) . '" alt="' . __( 'Download', 'discography' ) . '"></a></td>
						<td></td>
						<td></td>
					</tr>';
		}
		if ( function_exists( 'p2p_type' ) ) {
			$connected = p2p_type( 'discography_album' )->get_connected( $post );
			if ( $connected->have_posts() ) :
				foreach ( $connected->posts as $connect ) {
					$details  = get_post_meta( $connect->ID, '_discography_song_details', true );
					$purchase = get_post_meta( $connect->ID, '_discography_song_purchase', true );
					$content .= '<tr class="song">
							<td class="song-title"><a href="' . get_permalink( $connect->ID ) . '">' . get_the_title( $connect->ID ) . '</a></td>
							<td class="hear icon">';
					if ( $details['allow_streaming'] == 1 && ! empty( $purchase['free_download_link'] ) ) {
						$content .= $this->player( $connect );
					}
					$content .= '</td>
							<td class="free icon">';
					if ( $details['allow_download'] == 1 && ! empty( $purchase['free_download_link'] ) ) {
						$content .= '<a onclick="javascript:urchinTracker(\'/free/song/' . $connect->post_name . '\');" href="' . $purchase['free_download_link'] . '"><img src="' . plugins_url( 'images/emoticon_smile.png' , __FILE__ ) . '"></a>';
					}
					$content .= '</td>
							<td class="price icon">';
					if ( ! empty( $purchase['price'] ) && $purchase['price'] != 0 ) {
						$content .= '$' . $this->floor_price( $purchase['price'] );
					}
					$content .= '</td>
							<td class="buy icon">';
					if ( ! empty( $purchase['purchase_download_link'] ) ) {
						$content .= '<a onclick="javascript:urchinTracker(\'/buy/song/' . $connect->post_name . '\');" href="' . $purchase['purchase_download_link'] . '"><img src="' . plugins_url( 'images/cart_add.png' , __FILE__ ) . '" title="' . __( 'Buy', 'discography' ) . '" alt="' . __( 'Buy', 'discography' ) . '"></a>';
					}
					$content .= '</td>
						</tr>';
				}
			endif;
		}
		$content .= '
				</tbody>
			</table>';		
		return $content;
	}
	
	/**
	 * Song Content
	 *
	 * @param string $content Content string.
	 * @return string Content string.
	 */
	function song_content( $content ) {
		global $post;
		
		if ( ! is_single() || 'discography-song' != get_post_type() ) {
			return $content;
		}
		
		$details  = get_post_meta( $post->ID, '_discography_song_details', true );
		$purchase = get_post_meta( $post->ID, '_discography_song_purchase', true );
		$lyrics   = get_post_meta( $post->ID, '_discography_song_lyrics', true );
		
		// Headers
		$headers = array();
		if ( ! empty( $details['allow_streaming'] ) )
			$headers[] = '<div class="hear action">' . $this->player( $post ) . ' ' . __( 'Listen to the song', 'discography' ) . '</div>';
		if ( ! empty( $purchase['purchase_download_link'] ) )
			$headers[] = '<div class="buy action"><a onclick="javascript:urchinTracker(\'/buy/song/' . $post->post_name . '\');" href="' . $purchase['purchase_download_link'] . '"><img alt="" src="' . plugins_url( '/images/cart_add.png', __FILE__ ) . '"> ' . __( 'Buy the song', 'discography' ) . '</a></div>';
		if ( ! empty( $purchase['free_download_link'] ) )
			$headers[] = '<div class="download action"><a onclick="javascript:urchinTracker(\'/free/song/' . $post->post_name . '\');" href="' . $purchase['free_download_link'] . '"><img alt="" src="' . plugins_url( '/images/emoticon_smile.png', __FILE__ ) . '"> ' . __( 'Download the mp3', 'discography' ) . '</a></div>';
		if ( function_exists( 'p2p_type' ) ) {
			$connected = p2p_type( 'discography_album' )->get_connected( $post );
			if ( $connected->have_posts() ) :
				$albums = array();
				foreach ( $connected->posts as $connect ) {
					if ( $connected->post_count == 1 ) {
						$albums[] = '<a href="' . get_permalink( $connect->ID ) . '">' . __( 'Hear more music', 'discography' ) . '</a>';
						break;
					} else {
						$albums[] = '<a href="' . get_permalink( $connect->ID ) . '">' . get_the_title( $connect->ID ) . '</a>';
					}
				}
				$headers[] = '<div class="music action"><img alt="" src="' . plugins_url( '/images/music.png', __FILE__ ) . '"> ' . implode( ', ', $albums ) . '</div>';
			endif;
		}
		$header = '<div id="song-actions">' . implode( '', $headers ) . '</div>';
		
		// Song Info
		$song_infos = array();
		if ( ! empty( $details['recording_date'] ) )
			$song_infos[] = '<div class="info recordingDate">' . __( 'Recorded on', 'discography' ) . ' ' . strftime( '%D', strtotime( $details['recording_date'] ) ) . '</div>';
		if ( ! empty( $details['recording_artist'] ) )
			$song_infos[] = '<div class="info recordingArtist">' . __( 'Recorded by', 'discography' ) . ' ' . $details['recording_artist'] . '</div>';
		if ( ! empty( $details['composer'] ) )
			$song_infos[] = '<div class="info composer">' . __( 'Written by', 'discography' ) . ' ' . $details['composer'] . '</div>';
		$song_info ='<div id="song-info">' . implode( '', $song_infos ) . '</div>';
		
		// Lyrics
		$footer = '';
		if ( ! empty( $lyrics ) ) {
			$footer = '<h3>' . __( 'Lyrics', 'discography' ) . '</h3>
				<div class="lyrics">' . nl2br( $lyrics ) . '</div>';
		}
		
		return $header . $song_info . '<div class="description">' . $content . '</div>' . $footer;
	}
	
	/**
	 * Setup DB
	 */
	function setup_db() {
		global $wpdb;
		$wpdb->discography_categorymeta = $wpdb->prefix . 'discography_categorymeta';
	}
	
	/**
	 * Get Slug Prefix
	 */
	function get_slug_prefix() {
		$slug = 'discography';
		$page = $this->get_discography_options();
		$page_id = absint( $page['page'] );
		if ( $page_id > 0 ) {
			$link = get_permalink( $page_id );
			$slug = trim( substr( $link, strlen( home_url( '/' ) ) ), '/' );
		}
		return $slug;
	}
	
	/**
	 * Register Post Types
	 */
	function register_post_types() {
		$slug = $this->get_slug_prefix();
		// Categories
		$labels = array(
			'name'              => _x( 'Categories', 'general name', 'discography' ),
			'singular_name'     => _x( 'Category', 'singular name', 'discography' ),
			'search_items'      => __( 'Search Categories', 'discography' ),
			'all_items'         => __( 'All Categories', 'discography' ),
			'parent_item'       => __( 'Parent Category', 'discography' ),
			'parent_item_colon' => __( 'Parent Category:', 'discography' ),
			'edit_item'         => __( 'Edit Category', 'discography' ), 
			'update_item'       => __( 'Update Category', 'discography' ),
			'add_new_item'      => __( 'Add New Category', 'discography' ),
			'new_item_name'     => __( 'New Category Name', 'discography' ),
			'menu_name'         => __( 'Categories', 'discography' )
		); 	
		register_taxonomy( 'discography_category', array( 'discography-album' ), array(
			'hierarchical' => true,
			'labels'       => $labels,
			'show_ui'      => true,
			'query_var'    => true,
			'rewrite'      => array(
				'slug'         => $slug . '/album-category',
				'with_front'   => false,
				'hierarchical' => true
			)
		) );
		
		// Groups / Albums
		$labels = array(
			'name'               => _x( 'Albums', 'general name', 'discography' ),
			'singular_name'      => _x( 'Album', 'singular name', 'discography' ),
			'add_new'            => _x( 'Add New', 'album', 'discography' ),
			'add_new_item'       => __( 'Add New Album', 'discography' ),
			'edit_item'          => __( 'Edit Album', 'discography' ),
			'new_item'           => __( 'New Album', 'discography' ),
			'all_items'          => __( 'All Albums', 'discography' ),
			'view_item'          => __( 'View Album', 'discography' ),
			'search_items'       => __( 'Search Albums', 'discography' ),
			'not_found'          => __( 'No albums found', 'discography' ),
			'not_found_in_trash' => __( 'No albums found in Trash', 'discography' ),
			'parent_item_colon'  => '',
			'menu_name'          => 'Discography'
		);
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true, 
			'show_in_menu'       => true, 
			'query_var'          => true,
			'rewrite'            => true,
			'capability_type'    => 'post',
			'has_archive'        => $slug . '/albums',
			'rewrite'            => array(
				'slug'       => $slug . '/albums',
				'with_front' => false,
			),
			'menu_icon'          => plugins_url( 'images/icons/icon16.png', __FILE__ ),
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
		); 
		register_post_type( 'discography-album', $args );
		
		// Songs
		$labels = array(
			'name'               => _x( 'Songs', 'general name' ),
			'singular_name'      => _x( 'Song', 'singular name' ),
			'add_new'            => _x( 'Add New', 'song' ),
			'add_new_item'       => __( 'Add New Song' ),
			'edit_item'          => __( 'Edit Song' ),
			'new_item'           => __( 'New Song' ),
			'all_items'          => __( 'All Songs' ),
			'view_item'          => __( 'View Song' ),
			'search_items'       => __( 'Search Songs' ),
			'not_found'          => __( 'No songs found' ),
			'not_found_in_trash' => __( 'No songs found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => 'Songs'
		);
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true, 
			'show_in_menu'       => 'edit.php?post_type=discography-album', 
			'query_var'          => true,
			'rewrite'            => true,
			'capability_type'    => 'post',
			'has_archive'        => $slug . '/songs', 
			'rewrite'            => array(
				'slug'       => $slug . '/songs',
				'with_front' => false,
			),
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author', 'excerpt', 'comments' )
		); 
		register_post_type( 'discography-song', $args );
	}
	
	/**
	 * Register Post Relationships
	 */
	function register_post_relationships() {
		if ( function_exists( 'p2p_register_connection_type' ) ) {
			p2p_register_connection_type( array( 
				'name'     => 'discography_album',
				'from'     => 'discography-song',
				'to'       => 'discography-album',
				'sortable' => 'any',
				'title'    => array(
					'from' => 'Albums',
					'to'   => 'Songs'
				)
			) );
		}
	}
	
	/**
	 * Get Discography Category Options
	 */
	function get_discography_category_options( $term ) {
		$options = array(
			'group_order'   => get_metadata( $term->taxonomy, $term->term_id, 'group_order', true ),
			'group_sort_by' => get_metadata( $term->taxonomy, $term->term_id, 'group_sort_by', true ),
			'group_sort'    => get_metadata( $term->taxonomy, $term->term_id, 'group_sort', true )
		);
		$options['group_order'] = absint( $options['group_order'] );
		if ( empty( $options['group_sort_by'] ) )
			$options['group_sort_by'] = 'release_date';
		if ( empty( $options['group_sort'] ) )
			$options['group_sort'] = 'DESC';
		return $options;
	}
	
	/**
	 * Add Category Form
	 *
	 * @param string $taxonomy Taxonomy.
	 */
	function attachment_fields_to_add( $taxonomy ) {
		$options = $this->get_discography_category_options();
		?>
		<div class="form-field">
			<label for="discography_category_group_order"><?php _e( 'Order' ); ?></label>
			<input name="discography_category[group_order]" type="text" size="4" id="discography_category_group_order" value="0" style="width:auto;" />
		</div>
		<div class="form-field">
			<label for="discography_category_group_sort_by"><?php _e( 'Sort albums in this category by' ); ?></label>
			<select name="discography_category[group_sort_by]" id="discography_category_group_sort_by">
				<option value="title">Alphabetical</option>
				<option value="release_date">Release Date</option>
				<option value="id">ID</option>
				<option value="order">Custom</option>
			</select>
		</div>
		<div class="form-field">
			<label for="discography_category_group_sort"><?php _e( 'Ordering Direction' ); ?></label>
			<select name="discography_category[group_sort]" id="discography_category_group_sort">
				<option value="ASC">Ascending</option>
				<option value="DESC">Descending</option>
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
		$options = $this->get_discography_category_options( $term );
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="discography_category_group_order"><?php _e( 'Order' ); ?></label>
			</th>
			<td>
				<input name="discography_category[group_order]" type="text" size="4" id="discography_category_group_order" value="<?php echo $options['group_order']; ?>" style="width:auto;" />
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="discography_category_group_sort_by"><?php _e( 'Sort albums in this category by' ); ?></label>
			</th>
			<td>
				<select name="discography_category[group_sort_by]" id="discography_category_group_sort_by">
					<option value="title" <?php selected( 'title', $options['group_sort_by'] ); ?>>Alphabetical</option>
					<option value="release_date" <?php selected( 'release_date', $options['group_sort_by'] ); ?>>Release Date</option>
					<option value="id" <?php selected( 'id', $options['group_sort_by'] ); ?>>ID</option>
					<option value="order" <?php selected( 'order', $options['group_sort_by'] ); ?>>Custom</option>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="discography_category_group_sort"><?php _e( 'Ordering Direction' ); ?></label>
			</th>
			<td>
				<select name="discography_category[group_sort]" id="discography_category_group_sort">
					<option value="ASC" <?php selected( 'ASC', $options['group_sort'] ); ?>>Ascending</option>
					<option value="DESC" <?php selected( 'DESC', $options['group_sort'] ); ?>>Descending</option>
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
		if ( ! $term_id )
			return;
		
		// @todo Add in nonce verification
		if ( empty( $_POST ) || ! isset( $_POST['taxonomy'] ) )
			return;
		
		$term = get_term( $term_id, 'discography_category' );
		$options = wp_parse_args( $_POST['discography_category'], $this->get_discography_category_options( $term ) );
		
		update_metadata( $_POST['taxonomy'], $term_id, 'group_order', $options['group_order'] );
		update_metadata( $_POST['taxonomy'], $term_id, 'group_sort_by', $options['group_sort_by'] );
		update_metadata( $_POST['taxonomy'], $term_id, 'group_sort', $options['group_sort'] );
	}
	
	/**
	 * Add Meta Boxes
	 */
	function add_meta_boxes() {
		if ( function_exists( 'add_meta_box' ) ) {
			add_meta_box( 'discography_album', 'Album Details', array( $this, 'album_details_meta_box_inner' ), 'discography-album', 'normal', 'core' );
			add_meta_box( 'discography_album_purchase', 'Purchase Details', array( $this, 'album_purchase_meta_box_inner' ), 'discography-album', 'normal', 'core' );
			add_meta_box( 'discography_song', 'Song Details', array( $this, 'song_details_meta_box_inner' ), 'discography-song', 'normal', 'core' );
			add_meta_box( 'discography_song_purchase', 'Purchase Details', array( $this, 'song_purchase_meta_box_inner' ), 'discography-song', 'normal', 'core' );
			add_meta_box( 'discography_song_lyrics', 'Lyrics', array( $this, 'song_lyrics_meta_box_inner' ), 'discography-song', 'normal', 'core' );
			if ( ! $this->p2p_is_installed() || ! $this->p2p_is_active() ) {
				add_meta_box( 'discography_install_p2p', 'Songs', array( $this, 'install_p2p_meta_box_inner' ), 'discography-album', 'side' );
				add_meta_box( 'discography_install_p2p', 'Albums', array( $this, 'install_p2p_meta_box_inner' ), 'discography-song', 'side' );
			}
		}
	}
	
	/**
	 * Install Posts 2 Posts Meta Box
	 */
	function install_p2p_meta_box_inner() {
		global $post;
		echo '<p>' . $this->admin->p2p_install_message() . '</p>';
	}
	
	/**
	 * Album Details Meta Box
	 */
	function album_details_meta_box_inner() {
		global $post;
		$details = $this->get_album_details_meta( $post->ID );
		
		// Use nonce for verification
		echo '<input type="hidden" name="album_details_noncename" id="album_details_noncename" value="' . wp_create_nonce( plugin_basename( __FILE__ ) ) . '" />';
		
		// The actual fields for data entry
		echo '<table class="form-table" style="width: 100%;" cellspacing="2" cellpadding="5"><tbody>';
		echo '<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_details_artist">Album Artist</label></th>
				<td><input type="text" name="discography_album_details[artist]" id="discography_album_details_artist" size="50" value="' . $details['artist'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_details_is_album">Is this an album?</label></th>
				<td><select name="discography_album_details[is_album]" id="discography_album_details_is_album">
					<option value="1" ' . selected( $details['is_album'], '1', false ) . '>Yes</option>
					<option value="0" ' . selected( $details['is_album'], '0', false ) . '>No</option>
				</select></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_details_show_on_pages">Show this on song pages?</label></th>
				<td><select name="discography_album_details[show_on_pages]" id="discography_album_details_show_on_pages">
					<option value="1" ' . selected( $details['show_on_pages'], '1', false ) . '>Yes</option>
					<option value="0" ' . selected( $details['show_on_pages'], '0', false ) . '>No</option>
				</select></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_details_release_date">Release Date</label></th>
				<td><input type="text" name="discography_album_details[release_date]" id="discography_album_details_release_date" size="10" value="' . $details['release_date'] . '" style="width: 25%"></td>
			</tr>';
		echo '</tbody></table>';
	}
	
	/**
	 * Album Purchase Meta Box
	 */
	function album_purchase_meta_box_inner() {
		global $post;
		$details = $this->get_album_purchase_meta( $post->ID );
		
		// Use nonce for verification
		echo '<input type="hidden" name="album_purchase_noncename" id="album_purchase_noncename" value="' . wp_create_nonce( plugin_basename( __FILE__ ) ) . '" />';
		
		// The actual fields for data entry
		echo '<table class="form-table" style="width: 100%;" cellspacing="2" cellpadding="5"><tbody>';
		echo '<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_purchase_price">Physical Copy Price</label></th>
				<td><input type="text" name="discography_album_purchase[price]" id="discography_album_purchase_price" size="10" value="' . $details['price'] . '" style="width: 25%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_purchase_purchase_link">Purchase (physical copy) Link</label></th>
				<td><input type="text" name="discography_album_purchase[purchase_link]" id="discography_album_purchase_purchase_link" size="50" value="' . $details['purchase_link'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_purchase_download_price">Download Price</label></th>
				<td><input type="text" name="discography_album_purchase[download_price]" id="discography_album_purchase_download_price" size="10" value="' . $details['download_price'] . '" style="width: 25%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_purchase_purchase_download_link">Purchase (physical copy) Link</label></th>
				<td><input type="text" name="discography_album_purchase[purchase_download_link]" id="discography_album_purchase_purchase_download_link" size="50" value="' . $details['purchase_download_link'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_album_purchase_free_download_link">Free Download Link</label></th>
				<td><input type="text" name="discography_album_purchase[free_download_link]" id="discography_album_purchase_free_download_link" size="50" value="' . $details['free_download_link'] . '" style="width: 95%"></td>
			</tr>';
		echo '</tbody></table>';
	}
	
	/**
	 * Song Details Meta Box
	 */
	function song_details_meta_box_inner() {
		global $post;
		$details = get_post_meta( $post->ID, '_discography_song_details', true );
		
		// Use nonce for verification
		echo '<input type="hidden" name="song_details_noncename" id="song_details_noncename" value="' . wp_create_nonce( plugin_basename( __FILE__ ) ) . '" />';
		
		// The actual fields for data entry
		echo '<table class="form-table" style="width: 100%;" cellspacing="2" cellpadding="5"><tbody>';
		echo '<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_recording_artist">Recording Artist</label></th>
				<td><input type="text" name="discography_song_details[recording_artist]" id="discography_song_details_recording_artist" size="50" value="' . $details['recording_artist'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_recording_date">Recording Date</label></th>
				<td><input type="text" name="discography_song_details[recording_date]" id="discography_song_details_recording_date" size="10" value="' . $details['recording_date'] . '" style="width: 25%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_composer">Composer</label></th>
				<td><input type="text" name="discography_song_details[composer]" id="discography_song_details_composer" size="50" value="' . $details['composer'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_track_length">Track Length</label></th>
				<td><input type="text" name="discography_song_details[track_length]" id="discography_song_details_track_length" size="10" value="' . $details['track_length'] . '" style="width: 25%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_streaming">Allow streaming?</label></th>
				<td><select name="discography_song_details[allow_streaming]" id="discography_song_details_streaming">
					<option value="1" ' . selected( $details['allow_streaming'], '1', false ) . '>Yes</option>
					<option value="0" ' . selected( $details['allow_streaming'], '0', false ) . '>No</option>
				</select></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_details_allow_download">Allow download?</label></th>
				<td><select name="discography_song_details[allow_download]" id="discography_song_details_allow_download">
					<option value="1" ' . selected( $details['allow_download'], '1', false ) . '>Yes</option>
					<option value="0" ' . selected( $details['allow_download'], '0', false ) . '>No</option>
				</select></td>
			</tr>';
		echo '</tbody></table>';
	}
	
	/**
	 * Song Purchase Meta Box
	 */
	function song_purchase_meta_box_inner() {
		global $post;
		$purchase = get_post_meta( $post->ID, '_discography_song_purchase', true );
		
		// Use nonce for verification
		echo '<input type="hidden" name="song_purchase_noncename" id="song_purchase_noncename" value="' . wp_create_nonce( plugin_basename( __FILE__ ) ) . '" />';
		
		// The actual fields for data entry
		echo '<table class="form-table" style="width: 100%;" cellspacing="2" cellpadding="5"><tbody>';
		echo '<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_purchase_price">Price</label></th>
				<td><input type="text" name="discography_song_purchase[price]" id="discography_song_purchase_price" size="10" value="' . $purchase['price'] . '" style="width: 25%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_purchase_purchase_download_link">Purchase Download Link</label></th>
				<td><input type="text" name="discography_song_purchase[purchase_download_link]" id="discography_song_purchase_purchase_download_link" size="50" value="' . $purchase['purchase_download_link'] . '" style="width: 95%"></td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row"><label for="discography_song_purchase_free_download_link">Free Download/Streaming Link</label></th>
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
		echo '<input type="hidden" name="song_lyrics_noncename" id="song_lyrics_noncename" value="' . wp_create_nonce( plugin_basename( __FILE__ ) ) . '" />';
		
		// The actual fields for data entry
		echo '<label class="screen-reader-text" for="discography_song_lyrics">Lyrics</label>';
		echo '<textarea rows="15" cols="40" name="discography_song_lyrics" tabindex="6" id="discography_song_lyrics" style="margin: 0px; width: 98%;">' . $lyrics . '</textarea>';
	}
	
	function walk_nav_menu_tree( $items, $depth, $r ) {
		$walker = ( empty($r->walker) ) ? new Walker_Nav_Menu : $r->walker;
		$args = array( $items, $depth, $r );
		
		return call_user_func_array( array(&$walker, 'walk'), $args );
	}
	
	/**
	 * Filter Discography Song/Album Meta Data
	 *
	 * @param int $object_id Album ID.
	 * @param string Meta value.
	 */
	function get_discography_songalbum_metadata( $object_id ) {
		$value = get_metadata( 'discography_songalbum', $object_id );
		uasort( $value, array( $this, 'sort_songalbum_metadata' ) );
		return $value;
	}
	
	/**
	 * Sort Song/Album Meta Data
	 */
	function sort_songalbum_metadata( $a, $b ) {
		if ( $a[0] == $b[0] ) {
			return 0;
		}
		return ( $a[0] < $b[0] ) ? -1 : 1;
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
			if ( wp_verify_nonce( $_POST['album_details_noncename'], plugin_basename( __FILE__ ) ) ) {
				if ( isset( $_POST['discography_album_details'] ) ) {
					$details = wp_parse_args( $_POST['discography_album_details'], $Discography->get_album_details_meta( $post_id ) );
					update_post_meta( $post_id, '_discography_album_details', $details );
				}
			}
			// Save Album Purchase Details
			if ( wp_verify_nonce( $_POST['album_purchase_noncename'], plugin_basename( __FILE__ ) ) ) {
				if ( isset( $_POST['discography_album_purchase'] ) ) {
					$purchase = wp_parse_args( $_POST['discography_album_purchase'], $Discography->get_album_purchase_meta( $post_id ) );
					update_post_meta( $post_id, '_discography_album_purchase', $purchase );
				}
			}
		} elseif ( 'discography-song' == $_POST['post_type'] && current_user_can( 'edit_page', $post_id ) ) {
			// Save Song Details
			if ( wp_verify_nonce( $_POST['song_details_noncename'], plugin_basename( __FILE__ ) ) ) {
				if ( isset( $_POST['discography_song_details'] ) ) {
					$details = wp_parse_args( $_POST['discography_song_details'], $Discography->get_song_details_meta( $post_id ) );
					update_post_meta( $post_id, '_discography_song_details', $details );
				}
			}
			// Save Song Purchase Details
			if ( wp_verify_nonce( $_POST['song_purchase_noncename'], plugin_basename( __FILE__ ) ) ) {
				if ( isset( $_POST['discography_song_purchase'] ) ) {
					$purchase = wp_parse_args( $_POST['discography_song_purchase'], $Discography->get_song_purchase_meta( $post_id ) );
					update_post_meta( $post_id, '_discography_song_purchase', $purchase );
				}
			}
			// Save Song Lyrics
			if ( wp_verify_nonce( $_POST['song_lyrics_noncename'], plugin_basename( __FILE__ ) ) ) {
				if ( isset( $_POST['discography_song_lyrics'] ) ) {
					update_post_meta( $post_id, '_discography_song_lyrics', wp_kses( $_POST['discography_song_lyrics'], array() ) );
				}
			}
		}
		return;
	}
	
	/**
	 * Get Metadata by Key
	 *
	 * @param string $meta_type Meta Type.
	 * @param string $meta_key Meta Key.
	 * @param bool $single Optional.
	 */
	function get_metadata_by_key( $meta_type, $meta_key, $single = false ) {
		global $wpdb;
	
		if ( ! $meta_type )
			return false;

		if ( ! $table = _get_meta_table( $meta_type ) )
			return false;
		
		if ( $single ) {
			$meta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE meta_key = %s LIMIT 1", $meta_key ) );
		} else {
			$meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE meta_key = %s", $meta_key ) );
		}
		return $meta;
	}
	
	/**
	 * Get Album Details Meta
	 *
	 * @param int $post_id Post ID.
	 * @return array Album Details Meta.
	 */
	function get_album_details_meta( $post_id ) {
		$details = wp_parse_args( get_post_meta( $post_id, '_discography_album_details', true ), array(
			'artist'        => '',
			'is_album'      => 0,
			'show_on_pages' => 0,
			'release_date'  => ''
		) );
		return $details;
	}
	
	/**
	 * Get Album Purchase Meta
	 *
	 * @param int $post_id Post ID.
	 * @return array Album Purchase Meta.
	 */
	function get_album_purchase_meta( $post_id ) {
		$purchase = wp_parse_args( get_post_meta( $post_id, '_discography_album_purchase', true ), array(
			'price'                  => '',
			'purchase_link'          => '',
			'download_price'         => '',
			'purchase_download_link' => '',
			'free_download_link'     => ''
		) );
		return $purchase;
	}
	
	/**
	 * Get Song Details Meta
	 *
	 * @param int $post_id Post ID.
	 * @return array Song Details Meta.
	 */
	function get_song_details_meta( $post_id ) {
		$details = wp_parse_args( get_post_meta( $post_id, '_discography_song_details', true ), array(
			'recording_artist' => '',
			'recording_date'   => '',
			'allow_streaming'  => 0,
			'allow_download'   => 0
		) );
		return $details;
	}
	
	/**
	 * Get Song Purchase Meta
	 *
	 * @param int $post_id Post ID.
	 * @return array Song Purchase Meta.
	 */
	function get_song_purchase_meta( $post_id ) {
		$purchase = wp_parse_args( get_post_meta( $post_id, '_discography_song_purchase', true ), array(
			'price'                  => '',
			'composer'               => '',
			'track_length'           => '',
			'purchase_download_link' => '',
			'free_download_link'     => ''
		) );
		return $purchase;
	}
	
	/**
	 * Get Discography Options
	 */
	function get_discography_options() {
		$options = wp_parse_args( get_option( 'discography_options' ), array(
			'page'                => 0,
			'song_price'          => '',
			'song_open_comments'  => 'closed',
			'song_open_pingbacks' => 'closed',
			'delicious_player'    => 0,
			'group_price'         => '',
			'artist'              => '',
			'group_sort_by'       => 'release_date',
			'group_sort'          => 'DESC',
			'use_categories'      => 1,
			'category_sort_by'    => 'order',
			'category_sort'       => 'ASC'
		) );
		return $options;
	}
	
	/**
	 * Count Album Songs
	 *
	 * @todo Count album songs.
	 *
	 * @param int $album Album ID.
	 * @return int Song count.
	 */
	function count_album_songs( $album ) {
		if ( is_numeric( $album ) && function_exists( 'p2p_type' ) ) {
			$related = p2p_type( 'discography_album' )->get_connected( $album );
			return $related->post_count;
		}
		return '';
	}
	
	/**
	 * Register Activation Hook
	 */
	function register_activation_hook() {
		include_once( DISCOGRAPHY_DIR . 'includes/db-schema.php' );
		$this->db_schema = new Discography_DB_Schema();
	}
	
	/**
	 * Prevent Plugin Auto Update
	 */
	function prevent_plugin_auto_update( $r, $url ) {
		if ( 0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check' ) )
			return $r; // Not a plugin update request. Bail immediately.
		$plugins = unserialize( $r['body']['plugins'] );
		unset( $plugins->plugins[ plugin_basename( __FILE__ ) ] );
		unset( $plugins->active[ array_search( plugin_basename( __FILE__ ), $plugins->active ) ] );
		$r['body']['plugins'] = serialize( $plugins );
		return $r;
	}
	
}

global $Discography;
$Discography = new Discography();
register_activation_hook( __FILE__, array( $Discography, 'register_activation_hook' ) );
