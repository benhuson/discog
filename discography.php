<?php
/*
Plugin Name: Discography
Plugin URI: http://blogsforbands.com
Description: A full management system to allow bands and musicians to store and display information about their albums and songs.
Author: Dan Coulter
Version: 0.1.7
Author URI: http://dancoulter.com
*/

require_once 'version.php';

if ( isset($_GET['batman']) ) {
	update_option("discography_db_version", $_GET['batman']);
}

if ( defined('ABSPATH') ) :
	class dtc_discography {
		function add_admin_page() {
			global $wp_version;
			$admin_location = 'add_object_page';
			if ( $wp_version < 2.7 ) $admin_location = 'add_management_page';
			else $admin_location = 'add_object_page';
			$admin_location(
				'Discography',
				'Discography',
				'level_7', 
				'discography', 
				array("dtc_discography", "generate_admin_page")
			);
		}

		
		function admin_css() {
			ereg('/wp\-content/plugins/(.*)/discography\.php', __FILE__, $folder);
			$folder = get_bloginfo('wpurl') . '/wp-content/plugins/' . array_pop($folder) . '/';
			?>
				<link type="text/css" rel="stylesheet" href="<?php echo $folder; ?>jquery-calendar.css" />
				<style type="text/css">
					.todo { display: none; }
					
					a img {
						border: 0px;
					}
				
					#loading {text-align: center}
					
					ul#music-menu {
						margin: 0px 5%;
						padding: 0px;
					}
					
					li.selected {font-weight: bold}
					
					ul#music-menu li {
						display:inline;
						list-style-image:none;
						list-style-position:outside;
						list-style-type:none;
						text-align:center;
						white-space:nowrap;
						padding-right: 1em;
						cursor: pointer;
					}
					
					div#music-wrapper.wrap {
						margin-top: 0px;
					}
					
					div.music-page {
						display: none;
					}
					
				
					.drag, .dragSong {
						cursor: move;
					}
					
					.icon {
						position: relative;
						top: 3px;
					}
					
					.clickable {
						cursor: pointer;
					}
					
					.no-margin {
						margin: 0px;
					}
					
					.done {
						text-decoration: line-through;
					}
					
					input.price {
						width: 50px;
					}


					/**** Cool interface thingie ****/
						ul.items {
							padding: 0px;
						}

						li.item {
							padding: 0px;
							list-style-image:none;
							list-style-position:outside;
							list-style-type:none;
						}

						li.item div {
							position: relative;
							border: 1px solid #AAAAAA;
							line-height: 2em;
							padding: 4px 1em;
							position: relative;
						}
					
						li.item .drag {
							position: relative;
							top: 3px;
							padding-right: 5px;
						}
					
						li.item div span.buttons {
							position: absolute;
							right: 1em;
							top: 9px;
							line-height: 1em;
							height: 16px;
						}
										
						li.item div.edit.panel {
							border-top: 0px;
							display: none;
						}
					
						li.item div.panel div {
							border: 0px;
							line-height: 1.5;
						}					



					/**** Styles for Groups page ****/
					
						#new-group {
							padding-top: 7px;
						}
					
						#new-group div {
							margin-bottom: 7px;
						}
						
						form input.wide {
							width: 300px;
						}
					
					/**** Styles for Categories page ****/
						li.category div {
							position: relative;
						}
						
						li.category div span.buttons {
							position: absolute;
							right: 1em;
							top: 9px;
							line-height: 1em;
							height: 16px;
						}

						ul#music-categories {
							padding: 0px;
						}
						
						li.category {
							padding: 0px;
							list-style-image:none;
							list-style-position:outside;
							list-style-type:none;
						}
						
						li.category div {
							border: 1px solid #AAAAAA;
							line-height: 2em;
							padding: 4px 1em;
							position: relative;
						}
						
						ul#music-categories li .drag {
							position: relative;
							top: 3px;
							padding-right: 5px;
						}
						
						li.category div.edit.panel {
							border-top: 0px;
							display: none;
						}
						
						li.category div.panel div {
							border: 0px;
							line-height: 1.5;
						}

						
					/**** Styles for Feedback page ****/
						#feedback form {
							padding-left: 2em;
						}
						
						#feedback textarea {
							width: 500px;
							height: 300px;
						}
						
						#feedback form div {
							margin-bottom: 7px;
						}


					/**** Styles for Songs page ****/
						#songs tr, #songs td {
							position: relative;
						}
						
						#songs tr.panel {
							display: none;
						}
						
						#songs .widefat {
							bordercollapse: collapse;
						}
						
						td.panel div {
							position: relative;
						}
						
						#songs td.panel li {
							list-style-image:none;
							list-style-position:outside;
							list-style-type:none;
						}
						
						#songs td.panel form div {
							line-height: 2em;
						}
						
						#songs td.panel form input,
						#songs td.panel form textarea,
						#songs td.panel form select {
							background-color: white;
						}
				</style>
			<?php
		}
		
		function display_js() {
			$options = get_option('discography');
			if ( isset($options['deliciousPlayer']) && $options['deliciousPlayer'] == 1 ) {
				$folder = get_bloginfo('wpurl') . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/';
				?>
					<script type="text/javascript" src="<?php echo $folder ?>js/playtagger.js"></script>
				<?php
			}
		}
		
		function display_css() {
			?>
				<style type="text/css">
					table.group {
						border-collapse: collapse;
						width: 100%; 
						border: 1px solid black;
					}
					
					table.group thead {
						font-size: 8pt;
						border-bottom: 1px solid black;
					}
					
					table.group thead td {
						font-size: 8pt;
						border-right: 1px solid black;
						border-left: 1px solid black;
						text-align: center;
						padding: 0px 2px;
					}						
					
					table.group thead td.song-title {
						text-align: left;
					}
					
					table.group tbody tr {
						border-bottom: 1px dotted #AAA;
					}
					
					table.group td.icon {
						width: 18px;
						text-align: center;
					}
					
					table.group td.buy-intro {
						font-size: 8pt;
						text-align: right;
						font-style: italic;
					}
					
					h3.group-title{
						margin-top: 1em;
					}
					
					div.albumArt {
						float: right;
						padding: 0px 0px 7px 7px;
					}
					
					div.hear { line-height: 20px; }
					
					div.hear object { position: relative; top: 3px;}
					
					div#song-actions, div#song-info { padding-left: 1em; margin-bottom: 1em; }

				</style>
			<?php
		}

		function generate_admin_page() {
			require_once 'classes.php';
			ereg('/wp\-content/plugins/(.*)/discography\.php', __FILE__, $folder);
			$folder = get_bloginfo('wpurl') . '/wp-content/plugins/' . array_pop($folder) . '/';
			$pages = array(
				'Songs' => 'songs',
				'Groups/Albums' => 'groups',
				'Categories' => 'categories',
				'Settings' => 'settings',
				'Feedback/Bugs' => 'feedback',
				'Credits' => 'credits',
			);
			$options = get_option('discography');

			?>
				<script type="text/javascript">
					var ajaxTarget = "<?php echo $folder; ?>";
					var nonce = "<?php echo wp_create_nonce(); ?>";
					var pageTarget = "";
				</script>
				<div class="wrap">
					<h2>Discography</h2>
					<ul id="music-menu">
						<?php if ( !$options || empty($options['parent']) ) : ?>
							<li id="settings-tab">Settings</li>
						<?php else: ?>
							<?php foreach ( $pages as $displayName => $pageName ) : ?>
								<li id="<?php echo $pageName; ?>-tab"><?php echo $displayName; ?></li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ul>
					<div id="music-wrapper" class="wrap">
						<div id="loading">
							Loading...<br />
							<img src="<?php echo $folder; ?>images/ajax-loader.gif" alt="" />
						</div>
						<?php foreach ( $pages as $pageName ) : ?>
							<div id="<?php echo $pageName; ?>" class="music-page"></div>
						<?php endforeach; ?>
					</div>
				</div>
				<script type="text/javascript">
					if ( $ == undefined ) {
						$ = function(id) {
							return document.getElementById(id);
						}
					}
					jQuery(document).ready(function(){
						music_page_load(jQuery("#music-menu li").eq(0).attr("id").split("-")[0]);
					});
					
					jQuery("#music-menu li").click(function(){
						music_page_load(this.id.split("-")[0]);
					});
					
					music_page_load = function(page) {
						try{pageDestroy();}catch(e){};
						jQuery("#loading").show();
						jQuery(".music-page:visible").hide();
						jQuery("#music-menu li.selected").removeClass("selected");
						jQuery("#" + page + "-tab").addClass("selected");
						pageTarget = ajaxTarget + page +'.ajax.php';
						jQuery("#" + page).load(pageTarget, {
							action:"load", 
							"page":page,
							"nonce":nonce
						}, function(){
							jQuery("#loading").hide();
							jQuery(".music-page:visible").hide();
							jQuery("#" + page).show();
						});
					}
					
					listItem = function(id, title, type, buttons) {
						var template = '<?php dtc_discography_utilities::listItem('{%id}', '{%title}', '{%type}'); ?>';
						var buttonText = "";
						var slug = "";
						for ( i = 0; i < buttons.length; i++ ) {
							slug = buttons[i][1].replace(/[^a-z0-9_ ]/gi, '').replace(/ /g, '-').toLowerCase();
							buttonText += '&nbsp;<img src="<?php echo $folder ?>images/' + buttons[i][0] + '.png" alt="' + buttons[i][1] + '" title="' + buttons[i][1] + '" class="clickable ' + slug + '"/>';
						}
						var out = template.replace(/{%id}/g, id).replace(/{%title}/g, title).replace(/{%type}/g, type).replace(/<span class="buttons"><\/span>/gi, '<span class="buttons">' + buttonText + '</span>');
						return out;
					}

					
					itemLoading = function(item, style) {
						if ( style == undefined ) {
							style = "width: 95%; padding-right: 26px;"; 
						}
						jQuery(item).prepend('<div class="loader" style="border: 0px; position: absolute; top: 0px; text-align: center; ' + style + '"><img style="padding-top: 5px;" src="<?php echo $folder; ?>images/ajax-loader.gif" />&nbsp;</div>')
					}
				</script>
			<?php
		}
		
		function display($x){
			global $wpdb, $post;
			$options = get_option("discography");
			$out = $x;
			require_once 'classes.php';
			
			if ( $post->ID == $options['parent'] ) {
				ob_flush();
				ereg('/wp\-content/plugins/(.*)/discography\.php', __FILE__, $folder);
				$folder = get_bloginfo('wpurl') . '/wp-content/plugins/' . array_pop($folder) . '/';
				
				$g = new group();
				if ( $options['useCategories'] ) {
					$g->search('categoryID = -1', $options['groupSortColumn'] . ' ' . $options['groupSortDirection']);
				} else {
					$g->search(null, $options['groupSortColumn'] . ' ' . $options['groupSortDirection']);
				}
				$s = new song();

				echo $out;
				while ( $g->fetch() ) :
					if ( $g->listOnSongs )
						$g->display_songs_list();
				endwhile;
				
				if ( $options['useCategories'] ) {
					$c = new category();
					$c->search(null, '`' . $options['categorySortColumn'] . '` ' . $options['categorySortOrder']);
					while ( $c->fetch() ) {
						$g = $c->get_groups();
						if ( $g->_count ) {
							?>
								<h2><?php echo $c->name; ?></h2>
							<?php
							while ( $g->fetch() ) :
								if ( $g->listOnSongs )
									$g->display_songs_list();
							endwhile;
							
						}
					}
				}
				
				do_action('discography_song_list_extra');
				
				$out = ob_get_clean();
			} else {
				$s = new song();
				if ( $s->get_by_post($post->ID) ) {
					ob_flush();
					ereg('/wp\-content/plugins/(.*)/discography\.php', __FILE__, $folder);
					$folder = get_bloginfo('wpurl') . '/wp-content/plugins/' . array_pop($folder) . '/';
					?>
						<div class="song song-page" id="song-<?php echo $s->id; ?>">
							<div id="song-actions">
								<?php if ( $s->canStream() ) : ?>
									<div class="hear action"><?php $s->player(); ?> Listen to the song</div>
								<?php endif; ?>
								<?php if ( $s->canBuy() ) : ?>
									<div class="buy action"><a onclick="javascript:urchinTracker('/buy/song/<?php echo $s->slug; ?>');" href="<?php echo $s->purchaseDownloadLink; ?>"><img alt="" src="<?php echo $folder; ?>images/cart_add.png" /> Buy the song</a></div>
								<?php endif; ?>
								<?php if ( $s->canDownload() ) : ?>
									<div class="download action"><a onclick="javascript:urchinTracker('/free/song/<?php echo $s->slug; ?>');" href="<?php echo $s->freeDownloadLink; ?>"><img alt="" src="<?php echo $folder; ?>images/emoticon_smile.png" /> Download the mp3</a></div>
								<?php endif; ?>
								<div class="music action"><a href="<?php echo get_permalink($options['parent']); ?>"><img alt="" src="<?php echo $folder; ?>images/music.png" /> Hear more music</a></div>
							</div>
							<div id="song-info">
								<?php if ( !empty($s->recordingDate) && '0000-00-00' != $s->recordingDate ) : ?>
									<div class="info recordingDate">Recorded on <?php echo strftime('%D', strtotime($s->recordingDate)); ?></div>
								<?php endif; ?>
								<?php if ( !empty($s->recordingArtist) ) : ?>
									<div class="info recordingArtist">Recorded by: <?php echo $s->recordingArtist; ?></div>
								<?php endif; ?>
								<?php if ( !empty($s->composer) ) : ?>
									<div class="info composer">Written by: <?php echo $s->composer; ?></div>
								<?php endif; ?>
							</div>
							<div class="description">
								<?php echo nl2br($s->description); ?>
							</div>
							<?php if ( !empty($s->lyrics) ) : ?>
								<h3>Lyrics</h3>
								<div class="lyrics">
									<?php echo nl2br($s->lyrics); ?>
								</div>
							<?php endif; ?>
						</div>
					<?php
					
					do_action('discography_song_page_extra');
					

					$out = '<div><div>' . $out . '</div><div>' . ob_get_clean() . '</div></div>';
				}
			}			
			return $out;
		}
		
		function upgrade() {
			global $wpdb;
			
			$wpdb->query('SHOW CHARACTER SET WHERE CHARSET = "utf8"');
			if ( $wpdb->num_rows ) {
				$charset = "utf8";
			} else {
				$charset = "latin1";
			}

			$queries = array(
				array( // 0
					'
						CREATE TABLE ' . $wpdb->prefix . 'discography_categories (
							`id` int(10) unsigned NOT NULL auto_increment,
							`name` varchar(255) NOT NULL,
							`slug` varchar(255) NOT NULL,
							`order` tinyint(3) unsigned NOT NULL,
							`orderColumn` varchar(255) NOT NULL,
							`orderDirection` varchar(255) NOT NULL,
							`notes` mediumtext NOT NULL,
							PRIMARY KEY  (id)
						) ENGINE=MyISAM  DEFAULT CHARSET=' . $charset . '
					', '
						CREATE TABLE ' . $wpdb->prefix . 'discography_groups (
							`id` int(10) unsigned NOT NULL auto_increment,
							`title` varchar(255) NOT NULL,
							`description` mediumtext NOT NULL,
							`releaseDate` date NOT NULL,
							`albumArtist` varchar(255) NOT NULL,
							`art` varchar(255) NOT NULL,
							`listOnSongs` tinyint(4) NOT NULL,
							`slug` varchar(255) NOT NULL,
							`categoryID` int(11) NOT NULL,
							`order` int(11) NOT NULL,
							`isAlbum` tinyint(4) NOT NULL,
							`purchaseLink` varchar(255) NOT NULL,
							`purchaseDownloadLink` varchar(255) NOT NULL,
							`freeDownloadLink` varchar(255) NOT NULL,
							PRIMARY KEY  (id)
						) ENGINE=MyISAM  DEFAULT CHARSET=' . $charset . '
					', '
						CREATE TABLE ' . $wpdb->prefix . 'discography_songs (
							`id` int(10) unsigned NOT NULL auto_increment,
							`title` varchar(255) NOT NULL,
							`recordingDate` date NOT NULL,
							`lyrics` mediumtext NOT NULL,
							`description` mediumtext NOT NULL,
							`composer` varchar(255) NOT NULL,
							`purchaseDownloadLink` varchar(255) NOT NULL,
							`freeDownloadLink` varchar(255) NOT NULL,
							`allowDownload` tinyint(4) NOT NULL,
							`allowStreaming` tinyint(4) NOT NULL,
							`slug` varchar(255) NOT NULL,
							`postID` bigint(20) NOT NULL,
							`recordingArtist` varchar(255) NOT NULL,
							`length` varchar(255) NOT NULL,
							PRIMARY KEY  (id)
						) ENGINE=MyISAM  DEFAULT CHARSET=' . $charset . '
					', '
						CREATE TABLE ' . $wpdb->prefix . 'discography_song_group (
							`songID` int(10) unsigned NOT NULL,
							`groupID` int(10) unsigned NOT NULL,
							`order` int(10) unsigned NOT NULL,
							PRIMARY KEY  (songID,groupID)
						) ENGINE=MyISAM DEFAULT CHARSET=' . $charset . '
					'
				), array( // 1
					'ALTER TABLE `' . $wpdb->prefix . 'discography_songs` ADD `price` FLOAT(6,2) UNSIGNED NOT NULL',
					'ALTER TABLE `' . $wpdb->prefix . 'discography_groups` ADD `price` FLOAT(6,2) UNSIGNED NOT NULL'
				), array( // 2
					'ALTER TABLE `' . $wpdb->prefix . 'discography_groups` CHANGE `price` `physicalPrice` FLOAT( 6, 2 ) UNSIGNED NOT NULL',
					'ALTER TABLE `' . $wpdb->prefix . 'discography_groups` ADD `downloadPrice` FLOAT( 6, 2 ) NOT NULL , ADD `albumArt` VARCHAR( 255 ) NOT NULL',
				),
			);
			
			for ( $i = (int) get_option('discography_db_version'); $i <= DTC_DISC_DB_VERSION; $i++) {
				foreach ( $queries[$i] as $q ) {
					$wpdb->query($q);
				}
			}
			
			update_option("discography_db_version", DTC_DISC_DB_VERSION);
		}
		
		
		function hide_pages($pages) {
			global $count;
			global $wpdb;
			$songs = $wpdb->get_col('SELECT postID FROM ' . $wpdb->prefix . 'discography_songs');
			if ( !empty($songs) ) {
				foreach ( $pages as $key => $page ) {
					if ( in_array($page->ID, $songs) ) {
						unset($pages[$key]);
					}
				}
				$pages = array_merge($pages);
			}
			return $pages;
		}
	}
	
	
	ereg('/wp\-content/plugins/(.*)/discography\.php', __FILE__, $folder);
	$folder = get_bloginfo('wpurl') . '/wp-content/plugins/' . array_pop($folder) . '/';

	add_action('admin_menu', array('dtc_discography', 'add_admin_page'));

	add_filter('the_content', array('dtc_discography', 'display'));

	add_filter('get_pages', array('dtc_discography', 'hide_pages'));
	if ( 'edit-pages.php' == substr($_SERVER['PHP_SELF'], -14) ) {
		$count = 1;
		add_filter('the_posts', array('dtc_discography', 'hide_pages'));
	}
	
	if ( get_option('discography_db_version') != DTC_DISC_DB_VERSION ) {
		dtc_discography::upgrade();
	} else {
	}

	if ( $_GET['page'] == 'discography' ) :
		wp_deregister_script('jquery');
		wp_deregister_script('interface');
		wp_deregister_script('jquery-form');
		wp_enqueue_script('jquery', $folder . 'js/jquery.js', array(), '1.2.1');
		wp_enqueue_script('jquery-form', $folder . 'js/jquery.form.js', array(), '2.01');
		wp_enqueue_script('interface-partial', $folder . 'js/interface.js', array(), '1.2-partial');
		wp_enqueue_script('jquery-calendar', $folder . 'js/jquery-calendar.js', array(), '2.7');

		add_action('admin_head', array('dtc_discography', 'admin_css'));
	endif;
	
	add_action('wp_head', array('dtc_discography', 'display_css'));
	add_action('wp_head', array('dtc_discography', 'display_js'));
else:
	require_once 'ajaxSetup.php';
endif;

?>
