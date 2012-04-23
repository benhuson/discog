<?php

define('TABLE_CATS', $wpdb->prefix . 'discography_categories');
define('TABLE_GROUPS', $wpdb->prefix . 'discography_groups');
define('TABLE_SONGS', $wpdb->prefix . 'discography_songs');
define('TABLE_SONG_GROUP', $wpdb->prefix . 'discography_song_group');

class dtcDisc {
	function generateSlug($x) {
		$x = strtolower($x);
		$x = str_replace(' ', '-', $x);
		$x = preg_replace('#[^a-z\-0-9]#', '', $x);
		return $x;
	}
	
	function listItem($id, $title, $type, $buttons = array(), $echo = true) {
		$folder = get_bloginfo('wpurl') . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/';;
		$out .= '<li id="' . $type . '-' . $id . '" class="' . $type . ' item">';
		$out .= '<div class="main">';
		$out .= '<span class="title">' . $title . '</span>';
		$out .= '<span class="buttons">';
		foreach ( $buttons as $key => $value ) :
			$out .= '&nbsp;';
			$out .= '<img src="' . $folder . 'images/' . $key . '.png" alt="' . $value . '" title="' . $value . '" class="clickable ' . dtc_discography_utilities::generateSlug($value) . '"/>';
		endforeach;
		$out .= '</span>';
		$out .= '</div>';
		$out .= '<div class="edit panel"></div>';
		$out .= '</li>';
		if ( $echo ) echo $out;
		else return $out;
	}
	
	function escapeForInput($x) {
		if ( '0000-00-00' == $x ) {
			$x = '';
		}
		echo htmlspecialchars($x);
	}
	
	function getOptions() {
		$options = get_option('discography');

		$default_options = array(
			'deliciousPlayer' => 0,
		);

		return array_merge($default_options, $options);
	}
}

class dtc_discography_utilities extends dtcDisc {

}

class dtc_baseAR {
	var $wpdb, $_rows, $_row, $_new = true, $_table, $_count;
	
	function dtc_baseAR($id = null){
		global $wpdb;
		$this->wpdb = $wpdb;
		if ( $id !== null ) {
			$this->get($id);
		}
	}
	
	function fetch() {
		$this->_row = each($this->_rows);
		if ( $this->_row ) {
			$this->load();
			return true;
		} else {
			return false;
		}
	}
	
	function generate_slug() {
		$first = true;
		$name = $this->_name;
		do {
			if ( $first ) {
				$this->slug = dtc_discography_utilities::generateSlug($this->$name);
				$first = false;
			} else {
				ereg('[0-9]*$', $this->slug, $match);
				$match = ((int) $match[0]) + 1;
				if ( 1 == $match ) $match++;
				$this->slug = preg_replace('/[0-9]*$/', '', $this->slug);
				$this->slug .= $match;
			}
			
		} while ( $this->wpdb->get_var('SELECT COUNT(*) FROM `' . $this->_table . '` WHERE slug = "' . $this->slug . '"') );
	}

	
	function get($id) {
		$this->search('`id` = ' . (int) $id);
		return $this->fetch();
	}
	



	
	function load() {
		foreach ( $this->_fields as $f ) {
			$this->$f = stripslashes($this->_row['value']->$f);
		}
	}
	
	function search($where = null, $order = null) {
		$this->_new = false;
		$this->_rows = $this->wpdb->get_results('SELECT * FROM `' . $this->_table . '` ' . ($where !== null ? 'WHERE ' . $where : '') . ($order !== null ? ' ORDER BY ' . $order : ''));
		$this->_count = count($this->_rows);
		return count($this->_rows);
	}
	
	function save() {
		$first = true;
		$name = $this->_name;
		$this->generate_slug();
		if ( $this->_new ) {
			$fields = $this->_fields;
			$values = array();
			unset($fields[0]);
			
			$this->pre_insert();

			foreach ( $fields as $f ) {
				$values[$f] = '"' . $this->wpdb->escape(stripslashes($this->$f)) . '"';
			}
			
			if ( !$this->wpdb->query('INSERT INTO `' . $this->_table . '` (`' . implode('`, `', $fields) . '`) VALUES (' . implode(', ', $values) . ')') ) {
				return false;
			}
			$this->id = $this->wpdb->insert_id;

			$this->post_insert();
			$this->get((int) $this->id);

			return true;
		} else {
			$fields = $this->_fields;
			$values = array();
			unset($fields[0]);
			
			$this->pre_update();
			
			foreach ( $fields as $f ) {
				$values[$f] = '`' . $f . '` = "' . $this->wpdb->escape(stripslashes($this->$f)) . '"';
			}
			$result = (bool) $this->wpdb->query('UPDATE `' . $this->_table . '` SET ' . implode(', ', $values) . ' WHERE `id` = ' . (int) $this->id);
			
			if ( $result ) $this->post_update();
			
			$this->get((int) $this->id);
			
			return $result;
		}
	}
	
	function pre_insert(){}
	function post_insert(){}
	function pre_update(){}
	function post_update(){}
	
	function delete() {
		if ( !empty($this->id) ) {
			return (bool) $this->wpdb->query('DELETE FROM `' . $this->_table . '` WHERE `id` = ' . (int) $this->id);
		}
	}
	
	function toJSON($single = true) {
		if ( $single ) {
			$fields = array();
			foreach ( $this->_fields as $f ) {
				$fields[] = '"' . $f . '":"' . addslashes($this->$f) . '"';
			}
			return '{' . implode(', ', $fields) . '}';
		}
	}
}

class category extends dtc_baseAR {
	var $_table = TABLE_CATS, $_name = 'name';
	var $id, $name = '', $slug = '', $order = '', $orderColumn = 'releaseDate', $orderDirection = 'DESC', $notes = '';
	var $_fields = array('id', 'name', 'slug', 'order', 'orderColumn', 'orderDirection', 'notes');

	function delete() {
		if ( !empty($this->id) ) {
			$g = new group();
			$g->search('categoryID = ' . $this->id);
			while ( $g->fetch() ) {
				$g->categoryID = -1;
				$g->save();
			}
			parent::delete();
		}
	}
	
	function get_groups() {
		$g = new group();
		$g->search('categoryID = ' . (int) $this->id, '`' . $this->orderColumn . '` ' . $this->orderDirection);
		return $g;
	}
}

class group extends dtc_baseAR {
	var $_table = TABLE_GROUPS, $_name = 'title';
	var $id, $title, $description, $releaseDate, $albumArtist, $art, $listOnSongs, $slug, $categoryID = -1, 
		$order, $isAlbum, $purchaseLink, $purchaseDownloadLink, $freeDownloadLink, $physicalPrice, 
		$downloadPrice, $albumArt;
	var $_fields = array(
		'id',
		'title',
		'description',
		'releaseDate',
		'albumArtist',
		'art',
		'listOnSongs',
		'slug',
		'categoryID',
		'order',
		'isAlbum',
		'purchaseLink',
		'purchaseDownloadLink',
		'freeDownloadLink',
		'physicalPrice',
		'downloadPrice',
		'albumArt',
	);
	
	function delete() {
		if ( !empty($this->id) ) {
			$this->wpdb->query('DELETE FROM `' . TABLE_SONG_GROUP . '` WHERE `groupID` = ' . (int) $this->id);
			parent::delete();
		}
	}
	
	function sortSongs($ids) {
		
	}

	function display_songs_list() {
		$folder = get_bloginfo('wpurl') . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/';

		$s = new song();
		$s->getFromGroup($this->id);
		?>
			<?php if ( !empty($this->albumArt) ) : ?>
				<div class="albumArt"><img src="<?php echo $this->albumArt; ?>" alt="Album Art" /></div>
			<?php endif; ?>
			<h3 class="group-title"><?php echo $this->title; ?></h3>
			<p><?php echo nl2br($this->description); ?></p>
			<div style="clear: both;">&nbsp;</div>
			<table style="" class="group album songs" >
				<thead>
					<tr>
						<td class="song-title">Song</td>
						<td class="hear">Hear</td>
						<td class="free">Free</td>
						<td class="price">Price</td>
						<td class="buy">Buy</td>
					</tr>
				</thead>
				<tbody>
					<?php if ( !empty($this->purchaseLink) && !empty($this->physicalPrice) && $this->physicalPrice != 0 ) : ?>
						<tr class="buy-info">
							<td colspan="3" class="buy-intro">Buy the CD (physical media)</td>
							<td class="price icon">
								$<?php echo ( $this->price == floor($this->physicalPrice) ) ? floor($this->physicalPrice) : $this->physicalPrice; ?>
							</td>
							<td class="buy icon"><a onclick="javascript:urchinTracker('/buy/group-physical/<?php echo $this->slug; ?>');" href="<?php echo $this->purchaseLink; ?>"><img src="<?php echo $folder; ?>images/cart_add.png" title="Buy" alt="Buy" /></a></td>
						</tr>
					<?php endif; ?>
					<?php if ( !empty($this->purchaseDownloadLink) && !empty($this->downloadPrice) && $this->downloadPrice != 0 ) : ?>
						<tr>
							<td colspan="3" class="buy-intro">Buy the whole album (download)</td>
							<td class="price icon">
								$<?php echo ( $this->price == floor($this->downloadPrice) ) ? floor($this->downloadPrice) : $this->downloadPrice; ?>
							</td>
							<td class="buy icon"><a onclick="javascript:urchinTracker('/buy/group-download/<?php echo $this->slug; ?>');" href="<?php echo $this->purchaseDownloadLink; ?>"><img src="<?php echo $folder; ?>images/cart_add.png" title="Buy" alt="Buy" /></a></td>
						</tr>
					<?php endif; ?>
					<?php if ( !empty($this->freeDownloadLink) ) : ?>
						<tr>
							<td colspan="2" class="buy-intro">Download the full album</td>
							<td class="buy icon"><a onclick="javascript:urchinTracker('/free/group/<?php echo $this->slug; ?>');" href="<?php echo $this->freeDownloadLink; ?>"><img src="<?php echo $folder; ?>images/emoticon_smile.png" title="Download" alt="Download" /></a></td>
							<td></td>
							<td></td>
						</tr>
					<?php endif; ?>
					<?php while ( $s->fetch() ) : ?>
						<tr class="song">
							<td class="song-title">
								<div>
									<a href="<?php echo $s->get_link(); ?>"><?php echo $s->title; ?></a>
								</div>
							</td>
							<td class="hear icon">
								<?php if ( $s->allowStreaming && !empty($s->freeDownloadLink) )	$s->player(); ?>
							</td>
							<td class="free icon">
								<?php if ( $s->allowDownload && !empty($s->freeDownloadLink) ) : ?>
									<a onclick="javascript:urchinTracker('/free/song/<?php echo $s->slug; ?>');" href="<?php echo $s->freeDownloadLink; ?>"><img src="<?php echo $folder; ?>images/emoticon_smile.png" /></a>
								<?php endif; ?>
							</td>
							<td class="price icon">
								<?php if ( !empty( $s->price ) && $s->price != 0 ) : ?>
									$<?php echo ( $s->price == floor($s->price) ) ? floor($s->price) : $s->price; ?>
								<?php endif; ?>
							</td>
							<td class="buy icon">
								<?php if ( !empty($s->purchaseDownloadLink) ) : ?>
									<a onclick="javascript:urchinTracker('/buy/song/<?php echo $s->slug; ?>');" href="<?php echo $s->purchaseDownloadLink; ?>"><img src="<?php echo $folder; ?>images/cart_add.png" title="Buy" alt="Buy" /></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		<?php 
	}
}

class song extends dtc_baseAR {
	var $_table = TABLE_SONGS, $_name = 'title';
	var $id, $title, $recordingDate, $lyrics, $description, $composer, $purchaseDownloadLink, $freeDownloadLink, $allowDownload, $allowStreaming, $slug, $postID, $recordingArtist, $length, $price;
	var $_fields = array(
		'id',
		'title',
		'recordingDate',
		'lyrics',
		'description',
		'composer',
		'purchaseDownloadLink',
		'freeDownloadLink',
		'allowDownload',
		'allowStreaming',
		'slug',
		'postID',
		'recordingArtist',
		'length',
		'price',
	);
	
	function addToGroup($groupID) {
		if ( !empty($this->id) && !empty($groupID) ) {
			$order = $this->wpdb->get_var('SELECT MAX(`order`) FROM `' . TABLE_SONG_GROUP . '` WHERE groupID = ' . (int) $groupID) + 1;
			$this->wpdb->query('INSERT INTO `' . TABLE_SONG_GROUP . '` VALUES (' . (int) $this->id . ', ' . (int) $groupID . ', ' . $order . ')');
		}
	}
	
	function canBuy() {
		return (!empty($this->purchaseDownloadLink));
	}

	function canDownload() {
		return ($this->allowDownload && !empty($this->freeDownloadLink));
	}

	function canStream() {
		return ($this->allowStreaming && !empty($this->freeDownloadLink));
	}
	
	function delete() {
		if ( !empty($this->id) ) {
			wp_delete_post($this->postID);
			$this->wpdb->query('DELETE FROM `' . TABLE_SONG_GROUP . '` WHERE `songID` = ' . (int) $this->id);
			parent::delete();
		}
	}
	
	function display() {
	
	}
	
	function generate_slug(){
		// Stub to disable internal slug generation.
	}
	
	function getFromGroup($groupID) {
		$query = '
			SELECT 
				* 
			FROM 
				`' . TABLE_SONG_GROUP . '` sg, 
				`' . TABLE_SONGS . '` s
			WHERE 
				s.id = sg.songID AND
				groupID = ' . $groupID . '
			ORDER BY
				`order`
		';

		$this->_rows = $this->wpdb->get_results($query);
		$this->_count = count($this->_rows);
		return count($this->_rows);
	}
	
	function get_by_post($postID) {
		$this->search('`postID` = ' . (int) $postID);
		return $this->fetch();
	}
	
	function get_link() {
		return get_permalink($this->postID);
	}
	
	function player($echo = true) {
		$folder = get_bloginfo('wpurl') . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/';
		$options = dtcDisc::getOptions();
		if ( $options['deliciousPlayer'] == 1 ) {
			$out = '<a class="delicious" href="'. $this->freeDownloadLink . '"></a>';
		} else {
			$out = '<object type="application/x-shockwave-flash" data="' . $folder . 'player_mp3_maxi.swf" width="25" height="16">';
			$out .= '<param name="movie" value="' . $folder . 'player_mp3_maxi.swf" />';
			$out .= '<param name="FlashVars" value="mp3=' . str_replace(':', '%3A', $this->freeDownloadLink) . '&width=25&height=16&showslider=0" />';
			$out .= '<param name="wmode" value="transparent"/>';
			$out .= '</object>';
		}
		if ( $echo ) echo $out;
		return $out;
	}		
	
	function pre_insert() {
		global $options;
		$user = wp_get_current_user();
		$id = wp_insert_post(array(
			'post_author'		=> $user->ID,
			'post_title'		=> $this->wpdb->escape(stripslashes($this->title)),
			'post_status'		=> 'publish',
			'post_type' 		=> 'page',
			'comment_status'	=> $options['openComments'],
			'ping_status'		=> $options['openPingbacks'],
			'post_parent'		=> $options['parent'],
		));
		$this->postID = $id;
		$post = get_post($id);
		$this->slug = $post->post_name;
	}

	function pre_update() {
		global $options;
		$post = get_post($this->postID);
		if ( $post->post_title != $this->title ) {
			$post->post_title = stripslashes($this->title);
			$post->post_name = '';
			
			wp_update_post($post);
			
			$post = get_post($this->postID);
			$this->slug = $post->post_name;
		}
	}
	
	
	
	
	function get_groups() {
		$g = new group();
		$g->search('
			id in (
				SELECT groupID FROM ' . TABLE_SONG_GROUP . ' WHERE songID = ' . (int) $this->id . '
			)
		', 'title');
		return $g;
	}
	
	
	function removeFromGroup($groupID) {
		if ( !empty($this->id) && !empty($groupID) ) {
			$query = 'DELETE FROM `' . TABLE_SONG_GROUP . '` WHERE `songID` = ' . (int) $this->id . ' AND `groupID` = ' . (int) $groupID;
			return $this->wpdb->query($query);
		}
	}

}

?>
