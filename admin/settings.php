<?php

class Discography_Settings {
	
	/**
	 * Constructor
	 */
	function Discography_Settings() {
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		$this->settings_api();
	}
	
	/**
	 * Settings API
	 */
	function settings_api() {
		
		// Sections
		add_settings_section( 'general', __( 'General Options', 'discography' ), array( $this, 'section_general' ), 'discography' );
		add_settings_section( 'songs', __( 'Songs', 'discography' ), array( $this, 'section_songs' ), 'discography' );
		add_settings_section( 'albums', __( 'Albums/Groups', 'discography' ), array( $this, 'section_albums' ), 'discography' );
		add_settings_section( 'categories', __( 'Categories', 'discography' ), array( $this, 'section_categories' ), 'discography' );
		
		// Fields
		add_settings_field( 'discography_options_page', __( 'Select a page for your discography', 'discography' ), array( $this, 'page_field' ), 'discography', 'general' );
		add_settings_field( 'discography_options_song_price', __( 'Default Song Price', 'discography' ), array( $this, 'song_price_field' ), 'discography', 'songs' );
		add_settings_field( 'discography_options_song_open_comments', __( 'Allow comments on songs', 'discography' ), array( $this, 'song_open_comments_field' ), 'discography', 'songs' );
		add_settings_field( 'discography_options_song_open_pingbacks', __( 'Allow "pingbacks" on songs', 'discography' ), array( $this, 'song_open_pingbacks_field' ), 'discography', 'songs' );
		add_settings_field( 'discography_options_delicious_player', __( 'Use the lightweight (but less secure) Delicious music player', 'discography' ), array( $this, 'delicious_player_field' ), 'discography', 'songs' );
		add_settings_field( 'discography_options_group_price', __( 'Default Album/Group Price', 'discography' ), array( $this, 'group_price_field' ), 'discography', 'albums' );
		add_settings_field( 'discography_options_artist', __( 'Default "album artist"', 'discography' ), array( $this, 'artist_field' ), 'discography', 'albums' );
		add_settings_field( 'discography_options_group_sort_by', __( 'Uncategorized album/group ordering', 'discography' ), array( $this, 'group_sort_by_field' ), 'discography', 'albums' );
		add_settings_field( 'discography_options_group_sort', __( 'Uncategorized album/group order direction', 'discography' ), array( $this, 'group_sort_field' ), 'discography', 'albums' );
		add_settings_field( 'discography_options_use_categories', __( 'Use Categories', 'discography' ), array( $this, 'use_categories_field' ), 'discography', 'categories' );
		add_settings_field( 'discography_options_category_sort_by', __( 'Category ordering', 'discography' ), array( $this, 'category_sort_by_field' ), 'discography', 'categories' );
		add_settings_field( 'discography_options_category_sort', __( 'Category order direction', 'discography' ), array( $this, 'category_sort_field' ), 'discography', 'categories' );
		
		// Settings
 		register_setting( 'discography_options', 'discography_options', array( $this, 'sanitize_discography_options' ) );
	}
	
	/**
	 * Sanitize Discography Options
	 *
	 * @param array $options Options array.
	 * @return array Options array.
	 */
	function sanitize_discography_options( $options ) {
		return $options;
	}
	
	/**
	 * Section: General
	 */
	function section_general() {
		//echo '<p></p>';
	}
	
	/**
	 * Section: Songs
	 */
	function section_songs() {
		//echo '<p></p>';
	}
	
	/**
	 * Section: Albums
	 */
	function section_albums() {
		//echo '<p></p>';
	}
	
	/**
	 * Section: Categories
	 */
	function section_categories() {
		//echo '<p></p>';
	}
	
	/**
	 * Page Field
	 */
	function page_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		wp_dropdown_pages( array(
			'show_option_none' => __( '— Select —', 'discography' ),
			'name'             => 'discography_options[page]',
			'id'               => 'discography_options_page',
			'selected'         => $options['page']
		) );
	}
	
	/**
	 * Song Price Field
	 */
	function song_price_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		echo '<input type="text" name="discography_options[song_price]" id="discography_options_song_price" size="8" value="' . $options['song_price'] . '" autocomplete="off">';
	}
	
	/**
	 * Song Open Comments Field
	 */
	function song_open_comments_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		echo $this->yes_no_field( array(
			'name'     => 'discography_options[song_open_comments]',
			'id'       => 'discography_options_song_open_comments',
			'selected' => $options['song_open_comments'],
			'no'       => __( 'Closed', 'discography' ),
			'yes'      => __( 'Open', 'discography' ),
			'no_val'   => 'closed',
			'yes_val'  => 'open'
		) );
	}
	
	/**
	 * Song Open Pingbacks Field
	 */
	function song_open_pingbacks_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		echo $this->yes_no_field( array(
			'name'     => 'discography_options[song_open_pingbacks]',
			'id'       => 'discography_options_song_open_pingbacks',
			'selected' => $options['song_open_pingbacks'],
			'no'       => __( 'Closed', 'discography' ),
			'yes'      => __( 'Open', 'discography' ),
			'no_val'   => 'closed',
			'yes_val'  => 'open'
		) );
	}
	
	/**
	 * Delicious Player Field
	 */
	function delicious_player_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		echo $this->yes_no_field( array(
			'name'     => 'discography_options[delicious_player]',
			'id'       => 'discography_options_delicious_player',
			'selected' => $options['delicious_player']
		) );
	}
	
	/**
	 * Group Price Field
	 */
	function group_price_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		echo '<input type="text" name="discography_options[group_price]" id="discography_options_group_price" size="8" value="' . $options['group_price'] . '" autocomplete="off">';
	}
	
	/**
	 * Artist Field
	 */
	function artist_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		echo '<input type="text" name="discography_options[artist]" id="discography_options_artist" class="regular-text" value="' . $options['artist'] . '" autocomplete="off">';
	}
	
	/**
	 * Group Sort By Field
	 */
	function group_sort_by_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		echo '<select id="discography_options_group_sort_by" name="discography_options[group_sort_by]">
				<option value="release_date" ' . selected( 'release_date', $options['group_sort_by'], false ) . '>' . __( 'Release Date', 'discography' ) . '</option>
				<option value="order" ' . selected( 'order', $options['group_sort_by'], false ) . '>' . __( 'Custom', 'discography' ) . '</option>
				<option value="title" ' . selected( 'title', $options['group_sort_by'], false ) . '>' . __( 'Alphabetical', 'discography' ) . '</option>
				<option value="id" ' . selected( 'id', $options['group_sort_by'], false ) . '>' . __( 'Category ID', 'discography' ) . '</option>
			</select>';
	}
	
	/**
	 * Group Sort Field
	 */
	function group_sort_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		echo '<select id="discography_options_group_sort" name="discography_options[group_sort]">
				<option value="ASC" ' . selected( 'ASC', $options['group_sort'], false ) . '>' . __( 'Ascending', 'discography' ) . '</option>
				<option value="DESC" ' . selected( 'DESC', $options['group_sort'], false ) . '>' . __( 'Descending', 'discography' ) . '</option>
			</select>';
	}
	
	/**
	 * Use Categories Field
	 */
	function use_categories_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		echo $this->yes_no_field( array(
			'name'     => 'discography_options[use_categories]',
			'id'       => 'discography_options_use_categories',
			'selected' => $options['use_categories']
		) );
	}
	
	/**
	 * Category Sort By Field
	 */
	function category_sort_by_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		echo '<select id="discography_options_category_sort_by" name="discography_options[category_sort_by]">
				<option value="order" ' . selected( 'order', $options['category_sort_by'], false ) . '>' . __( 'Custom', 'discography' ) . '</option>
				<option value="title" ' . selected( 'title', $options['category_sort_by'], false ) . '>' . __( 'Alphabetical', 'discography' ) . '</option>
				<option value="id" ' . selected( 'id', $options['category_sort_by'], false ) . '>' . __( 'Category ID', 'discography' ) . '</option>
			</select>';
	}
	
	/**
	 * Category Sort Field
	 */
	function category_sort_field() {
		global $Discography;
		$options = $Discography->get_discography_options();
		echo '<select id="discography_options_category_sort" name="discography_options[category_sort]">
				<option value="ASC" ' . selected( 'ASC', $options['category_sort'], false ) . '>' . __( 'Ascending', 'discography' ) . '</option>
				<option value="DESC" ' . selected( 'DESC', $options['category_sort'], false ) . '>' . __( 'Descending', 'discography' ) . '</option>
			</select>';
	}
	
	/**
	 * Yes / No Field
	 */
	function yes_no_field( $args = null ) {
		$args = wp_parse_args( $args, array(
			'name'     => 'yesno',
			'id'       => '',
			'selected' => '',
			'no'       => __( 'No', 'discography' ),
			'yes'      => __( 'Yes', 'discography' ),
			'no_val'   => 0,
			'yes_val'  => 1
		) );
		if ( empty( $args['id'] ) )
			$args['id'] = $args['name'];
		return '<select id="' . $args['id'] . '" name="' . $args['name'] . '">
				<option value="' . $args['no_val'] . '" ' . selected( $args['no_val'], $args['selected'], false ) . '>' . $args['no'] . '</option>
				<option value="' . $args['yes_val'] . '" ' . selected( $args['yes_val'], $args['selected'], false ) . '>' . $args['yes'] . '</option>
			</select>';
	}
	
	/**
	 * Add a 'Settings' option to the entry on the plugins page.
	 *
	 * @param array $links The array of links displayed by the plugins page.
	 * @param string $file The current plugin being filtered.
	 * @return array The array of links.
	 */
	function plugin_action_links( $links, $file ) {
		if ( $file == 'discography/discography.php' ) {
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=discography/discography.php' ) . '">' . __( 'Settings', 'discography' ) . '</a>';
			if ( ! in_array( $settings_link, $links ) )
				array_unshift( $links, $settings_link );
		}
		return $links;
	}
	
}

?>