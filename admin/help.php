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
			<p>By default, this page will automatically add a categorised list of your albums and recent songs below your page content. You can remove this content by including the following code in your theme\'s functions.php file:</p>
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
		echo '<p>Coming soon...</p>';
		echo '<h3>Allow comments on songs</h3>';
		echo '<p>Coming soon...</p>';
		echo '<h3>Allow &quot;pingbacks&quot; on songs</h3>';
		echo '<p>Coming soon...</p>';
		echo '<h3>Use the lightweight (but less secure) Delicious music player</h3>';
		echo '<p>Coming soon...</p>';
	}
	
	function albums_groups_help_tab() {
		echo '<h3>Default Album/Group Price</h3>';
		echo '<p>Coming soon...</p>';
		echo '<h3>Default &quot;album artist&quot;</h3>';
		echo '<p>Coming soon...</p>';
		echo '<h3>Uncategorized album/group ordering</h3>';
		echo '<p>Coming soon...</p>';
		echo '<h3>Uncategorized album/group order direction</h3>';
		echo '<p>Coming soon...</p>';
	}
	
	function categories_help_tab() {
		echo '<h3>Use Categories</h3>';
		echo '<p>Coming soon...</p>';
		echo '<h3>Category ordering</h3>';
		echo '<p>Coming soon...</p>';
		echo '<h3>Category order direction</h3>';
		echo '<p>Coming soon...</p>';
	}
	
}

?>