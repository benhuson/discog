<?php

class Discography_Help {
	
	/**
	 * Constructor
	 */
	function Discography_Help() {
		global $Discography;
		add_action( "load-{$Discography->admin->options_page}", array( $this, 'add_help_tabs' ) );
	}
	
	/**
	 * Add Help Tabs
	 */
	function add_help_tabs() {
		$screen = get_current_screen();
		$screen->add_help_tab( array( 
			'id'       => 'general_options',
			'title'    => __( 'General Options', 'discography' ),
			'callback' => array( $this, 'general_options_help_tab' )
		) );
		$screen->add_help_tab( array( 
			'id'       => 'songs',
			'title'    => __( 'Songs', 'discography' ),
			'callback' => array( $this, 'songs_help_tab' )
		) );
		$screen->add_help_tab( array( 
			'id'       => 'albums_groups',
			'title'    => __( 'Albums/Groups', 'discography' ),
			'callback' => array( $this, 'albums_groups_help_tab' )
		) );
		$screen->add_help_tab( array( 
			'id'       => 'categories',
			'title'    => __( 'Categories', 'discography' ),
			'callback' => array( $this, 'categories_help_tab' )
		) );
	}
	
	function general_options_help_tab() {
		echo __( '<h3>Select a base page for your discography</h3>
			<p>URLs for albums and songs will be created as follows:</p>
			<ul>
				<li>/base-page/albums/album-name</li>
				<li>/base-page/songs/song-name</li>
			</ul>
			<p>By default, this page will automatically add a categorised list of your albums and recent songs below your page content. You can remove this content by removing the content filter in your theme\'s functions.php file:</p>
<pre>
function my_discography_remove_content() {
	global $Discography;
	remove_filter( \'the_content\', array( $Discography, \'overview_content\' ) );
}
add_action( \'init\', \'my_discography_remove_content\' );
</pre>', 'discography');
	}
	
	function songs_help_tab() {
		echo '<h3>Default Song Price</h3>';
		echo '<p>When your add a new song the price field will be pre-populated with this value.</p>';
		echo '<h3>Comments &amp; Pingbacks</h3>';
		echo '<p>Enable/disable comments and pingbacks on songs</h3>';
		echo '<h3>Delicious music player</h3>';
		echo '<p>Use the lightweight (but less secure) Delicious music player. If set to no then use a Flash player instead.</p>';
	}
	
	function albums_groups_help_tab() {
		echo '<h3>Default Album/Group Price</h3>';
		echo '<p>When your add a new album the price field will be pre-populated with this value.</p>';
		echo '<h3>Default Album Artist</h3>';
		echo '<p>When creating a new album the artist field will be pre-populated with this value.</p>';
		echo '<h3>Ordering</h3>';
		echo '<p>Select how albums are ordered when displayed in a list.<br />Order by the release (publish) date, the album\'s custom order field value, alphabetically or by ID.</p>';
	}
	
	function categories_help_tab() {
		echo '<h3>Use Categories</h3>';
		echo '<p>Enable/disable categories.</p>';
		echo '<h3>Ordering</h3>';
		echo '<p>Select how categories are ordered when displayed in a list.<br />Order by the category\'s custom order field value, alphabetically or by category ID.</p>';
	}
	
}

?>