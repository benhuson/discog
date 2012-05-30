<?php

class Discography_Shortcodes {
	
	/**
	 * Shortcode class constructor.
	 * Sets up shortcodes.
	 */
	function Discography_Shortcodes() {
		add_shortcode( 'discography_songs', array( $this, 'discography_songs' ) );
	}
	
	/**
	 * [discography_songs]
	 */
	function discography_songs( $atts, $content = '' ) {
		global $post;
		$content = '';
		$atts = shortcode_atts( array(
			'before' => '',
			'after'  => ''
		), $atts );
		
		if ( 'discography-album' == get_post_type( $post ) ) {
			if ( function_exists( 'p2p_type' ) ) {
				$connected = p2p_type( 'discography_album' )->get_connected( $post );
				if ( $connected->have_posts() ) :
					$content .= '<ol>';
					foreach ( $connected->posts as $connect ) {
						$content .= '<li><a href="' . get_permalink( $connect->ID ) . '">' . get_the_title( $connect->ID ) . '</a></li>';
					}
					$content .= '</ol>';
				endif;
			}
		}
		
		if ( ! empty( $content ) ) {
			$content = $atts['before'] . $content . $atts['after'];
		}
		
		return $content;
	}
	
}

?>