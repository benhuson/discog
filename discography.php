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
		add_filter( 'the_content', array( $this, 'overview_content' ) );
		add_filter( 'the_content', array( $this, 'album_content' ) );
		add_filter( 'the_content', array( $this, 'song_content' ) );
		add_filter( 'post_class', array( $this, 'post_class' ), 10, 3 );
		add_filter( 'http_request_args', array( $this, 'prevent_plugin_auto_update' ), 5, 2 );
		add_filter( 'discography_song_post_type_args', array( $this, 'discography_song_post_type_args' ), 5 );
		
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
		$details  = $this->get_discography_song_meta_details( $post->ID );
		$purchase = $this->get_discography_song_meta_purchase( $post->ID );
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
				$content .= '<table class="discography">
						<thead>
							<tr>
								<th class="song-title">' . __( 'Song', 'discography' ) . '</th>
								<th class="hear icon">' . __( 'Hear', 'discography' ) . '</th>
								<th class="free icon">' . __( 'Free', 'discography' ) . '</th>
								<th class="price icon">' . __( 'Price', 'discography' ) . '</th>
								<th class="buy icon">' . __( 'Buy', 'discography' ) . '</th>
							</tr>
						</thead>
						<tbody class="songs">';
				while ( $songs_query->have_posts() ) { 
					$songs_query->the_post();
					$details  = $this->get_discography_song_meta_details( get_the_ID() );
					$purchase = $this->get_discography_song_meta_purchase( get_the_ID() );
					$content .= '<tr class="song">
							<td class="song-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></td>
							<td class="hear icon">';
					if ( $details['allow_streaming'] == 1 && ! empty( $purchase['free_download_link'] ) ) {
						$content .= $this->player( $post );
					}
					$content .= '</td>
							<td class="free icon">';
					if ( $details['allow_download'] == 1 && ! empty( $purchase['free_download_link'] ) ) {
						$content .= '<a onclick="javascript:urchinTracker(\'/free/song/' . $post->post_name . '\');" href="' . $purchase['free_download_link'] . '"><img src="' . plugins_url( 'images/download.png' , __FILE__ ) . '"></a>';
					}
					$content .= '</td>
							<td class="price icon">';
					if ( ! empty( $purchase['price'] ) && $purchase['price'] != 0 ) {
						$content .= $discography_options['currency_symbol'] . $this->floor_price( $purchase['price'] );
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
		$discography_options = get_option( 'discography_options' );
		
		if ( ! is_single() || 'discography-album' != get_post_type() ) {
			return $content;
		}
		
		$details  = $this->get_discography_album_meta_details( $post->ID );
		$purchase = $this->get_discography_album_meta_purchase( $post->ID );
		
		if ( has_post_thumbnail() ) {
			$content = '<div class="discography-album-art">' . get_the_post_thumbnail( $post->ID, 'thumbnail', array( 'alt' => esc_attr( $post->post_title ) ) ) . '</div>' . $content;
		}
		
		$content .= '<table class="discography">
				<thead>
					<tr>
						<th class="song-title">' . __( 'Song', 'discography' ) . '</th>
						<th class="hear icon">' . __( 'Hear', 'discography' ) . '</th>
						<th class="free icon">' . __( 'Free', 'discography' ) . '</th>
						<th class="price icon">' . __( 'Price', 'discography' ) . '</th>
						<th class="buy icon">' . __( 'Buy', 'discography' ) . '</th>
					</tr>
				</thead>
				<tbody class="album">';
		if ( ! empty( $purchase['purchase_link'] ) && ! empty( $purchase['price'] ) && $purchase['price'] != 0 ) {
			$content .= '<tr class="buy-info">
						<td colspan="3" class="buy-intro">' . __( 'Buy the CD (physical media)', 'discography' ) . '</td>
						<td class="price icon">' . $discography_options['currency_symbol'] . $this->floor_price( $purchase['price'] ) . '</td>
						<td class="buy icon"><a onclick="javascript:urchinTracker(\'/buy/group-physical/' . $post->post_name . '\');" href="' . $purchase['purchase_link'] . '"><img src="' . plugins_url( 'images/cart_add.png' , __FILE__ ) . '" title="' . __( 'Buy', 'discography' ) . '" alt="' . __( 'Buy', 'discography' ) . '"></a></td>
					</tr>';
		}
		if ( ! empty( $purchase['purchase_download_link'] ) && ! empty( $purchase['download_price'] ) && $purchase['download_price'] != 0 ) {
			$content .= '<tr>
						<td colspan="3" class="buy-intro">' . __( 'Buy the whole album (download)', 'discography' ) . '</td>
						<td class="price icon">' . $discography_options['currency_symbol'] . $this->floor_price( $purchase['download_price'] ) . '</td>
						<td class="buy icon"><a onclick="javascript:urchinTracker(\'/buy/group-download/' . $post->post_name . '\');" href="' . $purchase['purchase_download_link'] . '"><img src="' . plugins_url( 'images/cart_add.png' , __FILE__ ) . '" title="' . __( 'Buy', 'discography' ) . '" alt="' . __( 'Buy', 'discography' ) . '"></a></td>
					</tr>';
		}
		if ( ! empty( $purchase['free_download_link'] ) ) {
			$content .= '<tr>
						<td colspan="2" class="buy-intro">' . __( 'Download the full album', 'discography' ) . '</td>
						<td class="buy icon"><a onclick="javascript:urchinTracker(\'/free/group/' . $post->post_name . '\');" href="' . $purchase['free_download_link'] . '"><img src="' . plugins_url( 'images/download.png' , __FILE__ ) . '" title="' . __( 'Download', 'discography' ) . '" alt="' . __( 'Download', 'discography' ) . '"></a></td>
						<td></td>
						<td></td>
					</tr>';
		}
		if ( function_exists( 'p2p_type' ) ) {
			$connected = p2p_type( 'discography_album' )->get_connected( $post );
			if ( $connected->have_posts() ) :
				$content .= '</tbody>
					<tbody class="songs">';
				foreach ( $connected->posts as $connect ) {
					$details  = $this->get_discography_song_meta_details( $connect->ID );
					$purchase = $this->get_discography_song_meta_purchase( $connect->ID );
					$content .= '<tr class="song">
							<td class="song-title"><a href="' . get_permalink( $connect->ID ) . '">' . get_the_title( $connect->ID ) . '</a></td>
							<td class="hear icon">';
					if ( $details['allow_streaming'] == 1 && ! empty( $purchase['free_download_link'] ) ) {
						$content .= $this->player( $connect );
					}
					$content .= '</td>
							<td class="free icon">';
					if ( $details['allow_download'] == 1 && ! empty( $purchase['free_download_link'] ) ) {
						$content .= '<a onclick="javascript:urchinTracker(\'/free/song/' . $connect->post_name . '\');" href="' . $purchase['free_download_link'] . '"><img src="' . plugins_url( 'images/download.png' , __FILE__ ) . '"></a>';
					}
					$content .= '</td>
							<td class="price icon">';
					if ( ! empty( $purchase['price'] ) && $purchase['price'] != 0 ) {
						$content .= $discography_options['currency_symbol'] . $this->floor_price( $purchase['price'] );
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
		$discography_options = get_option( 'discography_options' );
		
		if ( ! is_single() || 'discography-song' != get_post_type() ) {
			return $content;
		}
		
		$details  = $this->get_discography_song_meta_details( $post->ID );
		$purchase = $this->get_discography_song_meta_purchase( $post->ID );
		$lyrics   = get_post_meta( $post->ID, '_discography_song_lyrics', true );
		
		// Headers
		$headers = array();
		if ( ! empty( $details['allow_streaming'] ) )
			$headers[] = '<div class="hear action"><span class="icon">' . $this->player( $post ) . '</span> ' . __( 'Listen to the song', 'discography' ) . '</div>';
		if ( ! empty( $purchase['purchase_download_link'] ) ) {
			$price = ! empty( $purchase['price'] ) ? ' <span class="price">(' . $discography_options['currency_symbol'] . $this->floor_price( $purchase['price'] ) . ')</span>' : '';
			$headers[] = '<div class="buy action"><a onclick="javascript:urchinTracker(\'/buy/song/' . $post->post_name . '\');" href="' . $purchase['purchase_download_link'] . '"><span class="icon"><img alt="" src="' . plugins_url( '/images/cart_add.png', __FILE__ ) . '"></span> ' . __( 'Buy the song', 'discography' ) . '</a>' . $price . '</div>';
		}
		if ( ! empty( $purchase['free_download_link'] ) )
			$headers[] = '<div class="download action"><a onclick="javascript:urchinTracker(\'/free/song/' . $post->post_name . '\');" href="' . $purchase['free_download_link'] . '"><span class="icon"><img alt="" src="' . plugins_url( '/images/download.png', __FILE__ ) . '"></span> ' . __( 'Download the mp3', 'discography' ) . '</a></div>';
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
				$headers[] = '<div class="music action"><span class="icon"><img alt="" src="' . plugins_url( '/images/music.png', __FILE__ ) . '"></span> ' . implode( ', ', $albums ) . '</div>';
			endif;
		}
		$header = '<div class="discography-song-actions">' . implode( '', $headers ) . '</div>';
		
		// Song Info
		$song_infos = array();
		if ( ! empty( $details['recording_date'] ) )
			$song_infos[] = '<div class="info recording-date">' . __( 'Recorded on', 'discography' ) . ' ' . strftime( '%D', strtotime( $details['recording_date'] ) ) . '</div>';
		if ( ! empty( $details['recording_artist'] ) )
			$song_infos[] = '<div class="info recording-artist">' . __( 'Recorded by', 'discography' ) . ' ' . $details['recording_artist'] . '</div>';
		if ( ! empty( $details['composer'] ) )
			$song_infos[] = '<div class="info composer">' . __( 'Written by', 'discography' ) . ' ' . $details['composer'] . '</div>';
		$song_info ='<div class="discography-song-info">' . implode( '', $song_infos ) . '</div>';
		
		// Lyrics
		$footer = '';
		if ( ! empty( $lyrics ) ) {
			$footer = '<div class="discography-song-lyrics">
					<h3>' . __( 'Lyrics', 'discography' ) . '</h3>
					<div class="discography-lyrics">' . wptexturize( wpautop( $lyrics ) ) . '</div>
				</div>';
		}
		
		return $header . $song_info . '<div class="discography-description">' . $content . '</div>' . $footer;
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
			'add_new'            => _x( 'Add New Album', 'album', 'discography' ),
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
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' )
		);
		$args = apply_filters( 'discography_album_post_type_args', $args );
		register_post_type( 'discography-album', $args );
		
		// Songs
		$labels = array(
			'name'               => _x( 'Songs', 'general name', 'discography' ),
			'singular_name'      => _x( 'Song', 'singular name', 'discography' ),
			'add_new'            => _x( 'Add New', 'song', 'discography' ),
			'add_new_item'       => __( 'Add New Song', 'discography' ),
			'edit_item'          => __( 'Edit Song', 'discography' ),
			'new_item'           => __( 'New Song', 'discography' ),
			'all_items'          => __( 'All Songs', 'discography' ),
			'view_item'          => __( 'View Song', 'discography' ),
			'search_items'       => __( 'Search Songs', 'discography' ),
			'not_found'          => __( 'No songs found', 'discography' ),
			'not_found_in_trash' => __( 'No songs found in Trash', 'discography' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Songs', 'discography' )
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
			'supports'           => array( 'title', 'editor', 'author', 'excerpt' )
		);
		$args = apply_filters( 'discography_song_post_type_args', $args );
		register_post_type( 'discography-song', $args );
	}
	
	/**
	 * Discography Song Post Type Args
	 *
	 * @param $args array Post type args.
	 * @return array Post type args.
	 */
	function discography_song_post_type_args( $args ) {
		$options = $this->get_discography_options();
		if ( ! isset( $args['supports'] ) || ! is_array( $args['supports'] ) ) {
			$args['supports'] = array( 'title', 'editor' );
		}
		if ( 'open' == $options['song_open_comments'] ) {
			$args['supports'][] = 'comments';
		}
		if ( 'open' == $options['song_open_pingbacks'] ) {
			$args['supports'][] = 'trackbacks';
		}
		$args['supports'] = array_unique( $args['supports'] );
		return $args;
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
					'from' => __( 'Albums', 'discography' ),
					'to'   => __( 'Songs', 'discography' )
				)
			) );
		}
	}
	
	/**
	 * Get Discography Category Options
	 */
	function get_discography_category_options( $term = '' ) {
		$options = array(
			'group_order'   => '',
			'group_sort_by' => '',
			'group_sort'    => ''
		);
		if ( is_object( $term ) && term_exists( $term->slug, 'discography_category' ) ) {
			$options['group_order']   = get_metadata( $term->taxonomy, $term->term_id, 'group_order', true );
			$options['group_sort_by'] = get_metadata( $term->taxonomy, $term->term_id, 'group_sort_by', true );
			$options['group_sort']    = get_metadata( $term->taxonomy, $term->term_id, 'group_sort', true );
		}
		$options['group_order'] = absint( $options['group_order'] );
		if ( empty( $options['group_sort_by'] ) )
			$options['group_sort_by'] = 'release_date';
		if ( empty( $options['group_sort'] ) )
			$options['group_sort'] = 'DESC';
		return $options;
	}
	
	/**
	 * Get Song Meta Details
	 *
	 * @param $post_id int Post ID.
	 * @return array Meta value.
	 */
	function get_discography_song_meta_details( $post_id ) {
		$meta = get_post_meta( $post_id, '_discography_song_details', true );
		$meta = wp_parse_args( $meta, array(
			'recording_artist' => '',
			'recording_date'   => '',
			'composer'         => '',
			'track_length'     => '',
			'allow_streaming'  => 0,
			'allow_download'   => 0
		) );
		return $meta;
	}
	
	/**
	 * Get Song Meta Purchase
	 *
	 * @param $post_id int Post ID.
	 * @return array Meta value.
	 */
	function get_discography_song_meta_purchase( $post_id ) {
		$meta = get_post_meta( $post_id, '_discography_song_purchase', true );
		$meta = wp_parse_args( $meta, array(
			'price'                  => '',
			'purchase_download_link' => '',
			'free_download_link'     => ''
		) );
		return $meta;
	}
	
	/**
	 * Get Album Meta Details
	 *
	 * @param $post_id int Post ID.
	 * @return array Meta value.
	 */
	function get_discography_album_meta_details( $post_id ) {
		$meta = get_post_meta( $post_id, '_discography_album_details', true );
		$meta = wp_parse_args( $meta, array(
			'artist'        => '',
			'is_album'      => 0,
			'show_on_pages' => 0,
			'release_date'  => ''
		) );
		return $meta;
	}
	
	/**
	 * Get Album Meta Purchase
	 *
	 * @param $post_id int Post ID.
	 * @return array Meta value.
	 */
	function get_discography_album_meta_purchase( $post_id ) {
		$meta = get_post_meta( $post_id, '_discography_album_purchase', true );
		$meta = wp_parse_args( $meta, array(
			'price'                  => '',
			'purchase_link'          => '',
			'download_price'         => '',
			'purchase_download_link' => '',
			'free_download_link'     => ''
		) );
		return $meta;
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
	 * Get Discography Options
	 */
	function get_discography_options() {
		$options = wp_parse_args( get_option( 'discography_options' ), array(
			'page'                => 0,
			'currency_symbol'     => '$',
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
