<?php

require_once 'ajaxSetup.php';

switch ( $_POST['action'] ) :
	case 'load':
		?>
			<div>
				<span class="clickable add-trigger" id="group-add-trigger">
					<img class="icon" src="<?php echo $folder; ?>images/add.png" /> Add another album/group
				</span>
			</div>
			<div id="group-add-form" style="display: none;">
				<form id="new-group" method="post" class="group" action="<?php echo $folder; ?>groups.ajax.php">
					<div><label>Title: <input type="text" class="title wide" name="title" /></label></div>
					<div><label>Physical Copy Price: <input type="text" class="physicalPrice price" name="physicalPrice" value="<?php if ( isset($options['groupPrice']) ) echo $options['groupPrice']; ?>" /></label></div>
					<div><label>Download Price: <input type="text" class="downloadPrice price" name="downloadPrice" value="<?php if ( isset($options['groupPrice']) ) echo $options['groupPrice']; ?>" /></label></div>
					<div><label>Album Art (preferably thumbnail sized): <input type="text" class="wide albumArt" name="albumArt" /></label></div>
					<div><label>Album Artist: <input type="text" class="albumArtist wide" name="albumArtist" value="<?php if ( isset($options['artist']) ) echo $options['artist']; ?>" /></label></div>
					<div><label>Purchase (physical copy) Link: <input class="purchaseLink wide" name="purchaseLink" type="text" /></label></div>
					<div><label>Purchase Download Link: <input class="purchaseDownloadLink wide" name="purchaseDownloadLink" type="text" /></label></div>
					<div><label>Free Download Link: <input class="freeDownloadLink wide" name="freeDownloadLink" type="text" /></label></div>
					<div><label>Is this an album? <select name="isAlbum"><option value="1">Yes</option><option value="0">No</option></select></div>
					<div><label>Show this on song pages? <select name="listOnSongs"><option value="1">Yes</option><option value="0">No</option></select></div>
					<div><label>Release Date <input type="text" class="releaseDate" name="releaseDate" /></div>
					<div>Description/Other notes:<br /><textarea class="description" name="description" rows="7" cols="80"></textarea></div>
					<div>
						<input type="submit" name="" value="Add Album/Group" />
						<input type="reset" name="" value="Cancel" id="new-group-reset" />
					</div>
					<input type="hidden" name="action" value="add" />
					<input type="hidden" name="nonce" value="<?php echo $_POST['nonce']; ?>" />
				</form>
			</div>
			<div style="padding-top: 1em;">Drag groups to sort them or move them into another category using this icon: <img class="icon" src="<?php echo $folder; ?>images/shape_square.png" />  (I'll find something better later)</div>
			<?php if ( !isset($options['useCategories']) || $options['useCategories'] == 1 ) : ?>
				<h3>Uncategorized</h3>
				<ul id="uncategorized" class="uncategorized sort-<?php echo $options['groupSortColumn']; ?> groups items">
					<?php
						$g = new group();
						$g->search('categoryID = -1', '`' . $options['groupSortColumn'] . '` ' . $options['groupSortDirection']);
						while ( $g->fetch() ) :
							dtc_discography_utilities::listItem($g->id, $g->title, 'group', array('music_add' => 'Add a Song', 'music' => 'View Songs', 'page_white_edit' => 'Edit', 'delete' => 'Delete'));
						endwhile;
					?>
				</ul>
				<?php $c = new category(); $c->search(null, '`' . $options['categorySortColumn'] . '` ' . $options['categorySortDirection']); ?>
				<?php while ( $c->fetch() ) : ?>
					<h3><?php echo $c->name ?></h3>
					<ul id="category-<?php echo $c->id ?>" class="items category">
						<?php 
							$g->search('categoryID = ' . $c->id, '`' . $c->orderColumn . '` ' . $c->orderDirection);
							while ( $g->fetch() ) {
								dtc_discography_utilities::listItem($g->id, $g->title, 'group', array('music_add' => 'Add a Song', 'music' => 'View Songs', 'page_white_edit' => 'Edit', 'delete' => 'Delete'));
							}
						?>
					</ul>
				<?php endwhile; ?>
			<?php else : ?>
				<ul id="uncategorized" class="uncategorized sort-<?php echo $options['groupSortColumn']; ?> groups items">
					<?php
						$g = new group();
						$g->search(null, '`' . $options['groupSortColumn'] . '` ' . $options['groupSortDirection']);
						while ( $g->fetch() ) :
							dtc_discography_utilities::listItem($g->id, $g->title, 'group', array('music_add' => 'Add a Song', 'music' => 'View Songs', 'page_white_edit' => 'Edit', 'delete' => 'Delete'));
						endwhile;
					?>
				</ul>
			<?php endif; ?>
			<script type="text/javascript">
				pageDestroy = function() {
					jQuery('ul.sortable').SortableDestroy().removeClass("sortable");
				};
				
				jQuery("#new-group input.releaseDate").calendar({dateFormat:"YMD-"});

				setupEvents = function() {
					jQuery('.drag').remove();
					jQuery('ul.items li div.main').prepend('<img class="drag" alt="Drag" src="<?php echo $folder; ?>images/shape_square.png" />');
					
					//jQuery('ul.items').SortableDestroy();
					
					jQuery('ul.sortable').SortableDestroy().removeClass("sortable");

					jQuery('ul.items').Sortable({
						accept : 'group',
						opacity: 	0.5,
						fit :	false,
						axis: 'vertically',
						handle: 'img.drag',						
						onStop:function(){
							var id = jQuery(this).parents("ul").attr("id");
							x = jQuery.SortSerialize(id);
							jQuery.post(pageTarget, {
								action:"sort",
								groups:x.hash,
								id:id,
								"nonce":nonce
							});
							
						}
					});
					
					jQuery('ul.items').addClass("sortable");
					
					jQuery(".item .clickable").unbind("click");
					
					jQuery(".item img.delete").click(function() {
						if ( confirm('Are you sure you want to delete "' + jQuery(this).parents("div").children("span.title").html() + '"?') ) {
							item = jQuery(this).parents("li");
							item.hide(500, function(){jQuery(this).remove();});
							jQuery.post(pageTarget, {
								action:"delete",
								"nonce":nonce,
								id:item.attr("id").split("-")[1]
							});
						}
					});
					
					jQuery(".item img.edit").click(function() {
						var target = jQuery(this).parents("li").children(".edit.panel");
						itemLoading(jQuery(this).parents("li").find("div.main"));
						target.load(pageTarget, {
							id: jQuery(this).parents("li").attr("id").split("-")[1],
							action:"edit",
							nonce:nonce
						}, function() {
							target.filter(":hidden").slideDown(500);
							target.siblings(".main").find(".loader:visible").remove();
							target.find("input.title").focus();
						});
					});
					
					jQuery(".item img.view-songs").click(function(){
						var target = jQuery(this).parents("li").children(".edit.panel");
						itemLoading(jQuery(this).parents("li").find("div.main"));
						target.load(pageTarget, {
							id: jQuery(this).parents("li").attr("id").split("-")[1],
							action:"songs",
							nonce:nonce
						}, function() {
							target.filter(":hidden").slideDown(500);
							target.siblings(".main").find(".loader:visible").remove();
						});
					});
					
					jQuery(".item img.add-a-song").click(function(){
						var target = jQuery(this).parents("li").children(".edit.panel");
						itemLoading(jQuery(this).parents("li").find("div.main"));
						target.load(pageTarget, {
							id: jQuery(this).parents("li").attr("id").split("-")[1],
							action:"songForm",
							nonce:nonce
						}, function() {
							target.filter(":hidden").slideDown(500);
							target.siblings(".main").find(".loader:visible").remove();
						});
					});

				};
				
				setupEvents();
				
				//jQuery(".releaseDate").calendar({dateFormat:"YMD-"});
				
				jQuery("#new-group").ajaxForm({
					url:pageTarget,
					dataType: "json", 
					success:function(json){
						if ( json.success ) {
							var newItem = listItem(json.group.id, jQuery("#new-group .title").val(), 'group', [['music_add', 'Add a Song'],['music', 'View Songs'],['page_white_edit', 'Edit'],['delete', 'Delete']]);
							<?php if ( $options['groupSortColumn'] == 'order' || $options['groupSortColumn'] == 'id' || $options['groupSortColumn'] == 'releaseDate' ) : ?>
								jQuery('#uncategorized').<?php echo ($options['groupSortDirection'] == 'ASC' ? 'append' : 'prepend'); ?>(newItem);
							<?php endif; ?>
							<?php if ( $options['groupSortColumn'] == 'title' ) : ?>
								var items = jQuery('#uncategorized li');
								var newTitle = jQuery("#new-group .title").val();
								for ( i = 0; i < items.size(); i++ ) {
									if ( newTitle < items.eq(i).children().children('span.title').html() ) {
										items.eq(i).<?php echo ($options['groupSortDirection'] == 'ASC' ? 'before' : 'after'); ?>(newItem);
										break;
									}
									if ( (items.size() - 1) == i ) {
										items.eq(i).after(newItem);
									}
								}
							<?php endif; ?>
							
							<?php if ( $options['groupSortColumn'] == 'order' ) : ?>
								jQuery('#uncategorized').SortableAddItem('#group-' + json.id);
								jQuery('#group-' + json.id + ' div').prepend('<img class="drag" alt="Drag" src="<?php echo $folder; ?>images/shape_square.png" />');
							<?php endif; ?>
							jQuery("#new-group-reset").click();
							setupEvents();
						}
					}
					
				});


				
				jQuery("#group-add-trigger").click(function(){
					jQuery("#group-add-form:hidden").slideDown(500, function(){
						jQuery(this).find("input.title").focus();
					});
				});
				
				jQuery("#new-group-reset").click(function(){jQuery("#group-add-form:visible").slideUp(500);});
			</script>

			<div class="todo">
				<h3>Todo</h3>
				<ul class="todo">
					<li>uploads for mp3 files</li>
					<li>custom album data fields</li>
					<li>detect whether blog using permalinks</li>
				</ul>
			</div>
		<?php
		break;
	case 'add' :
		$g = new group();
		$args = $_POST;
		unset($args['nonce'], $args['action']);
		foreach ( $args as $key => $value ) :
			$g->$key = $value;
		endforeach;
		if ( $g->save() ) {
			?>{success:true,id:<?php echo $g->id; ?>,group:<?php echo $g->toJSON(); ?>}<?php
		} else {
			?>{success:false,msg:"Database error"}<?php
		}
		break;
	case 'sort' :
		parse_str($_POST['groups'], $groups);
		$g = new group();
		foreach ( $groups[$_POST['id']] as $key => $group ) {
			$g->get(substr($group, 6));
			if ( 'uncategorized' == $_POST['id'] ) {
				$g->categoryID = -1;
				$g->order = $key;
			} else {
				$g->categoryID = substr($_POST['id'], 9);
				$g->order = $key;
			}
			$g->save();
		}
	case 'delete' :
		$g = new group($_POST['id']);
		$g->delete();
		break;
	case 'edit' :
		$g = new group($_POST['id']);
		?>
			<form method="post" class="group" id="edit-<?php echo $_POST['id']; ?>">
				<div><label>Title: <input class="title wide" name="title" type="text" value="<?php echo dtcDisc::escapeForInput($g->title); ?>" /></label></div>
				<div><label>Physical Copy Price: <input type="text" class="price physicalPrice" name="physicalPrice" value="<?php echo $g->physicalPrice ?>" /></label></div>
				<div><label>Download Price: <input type="text" class="price downloadPrice" name="downloadPrice" value="<?php echo $g->downloadPrice ?>" /></label></div>
				<div><label>Album Art (preferably thumbnail sized): <input type="text" class="wide albumArt" name="albumArt" value="<?php echo $g->albumArt ?>" /></label></div>
				<div><label>Album Artist: <input class="albumArtist wide" name="albumArtist" value="<?php echo $g->albumArtist; ?>" type="text" /></label></div>
				<div><label>Purchase (physical copy) Link: <input class="purchaseLink wide" name="purchaseLink" value="<?php echo $g->purchaseLink; ?>" type="text" /></label></div>
				<div><label>Purchase Download Link: <input class="purchaseDownloadLink wide" name="purchaseDownloadLink" value="<?php echo $g->purchaseDownloadLink; ?>" type="text" /></label></div>
				<div><label>Free Download Link: <input class="freeDownloadLink wide" name="freeDownloadLink" value="<?php echo $g->freeDownloadLink; ?>" type="text" /></label></div>
				<div><label>
					Is this an album? 
					<select name="isAlbum">
						<option value="1" <?php if ( 1 == $g->isAlbum ) echo 'selected="selected"'; ?>>Yes</option>
						<option value="0" <?php if ( 0 == $g->isAlbum ) echo 'selected="selected"'; ?>>No</option>
					</select>
				</label></div>
				<div><label>
					Show this on song pages? 
					<select name="listOnSongs">
						<option value="1" <?php if ( 1 == $g->listOnSongs ) echo 'selected="selected"'; ?>>Yes</option>
						<option value="0" <?php if ( 0 == $g->listOnSongs ) echo 'selected="selected"'; ?>>No</option>
					</select>
				</label></div>

				<div><label>Release Date <input class="releaseDate" name="releaseDate" type="text" value="<?php echo $g->releaseDate; ?>" /></label></div>
				<div>Description/Other notes:<br><textarea class="description" name="description" rows="7" cols="80"><?php echo $g->description; ?></textarea></div>
				<div>
					<input class="save" value="Save Changes" type="submit" />
					<input class="reset" value="Cancel" type="reset" />
				</div>
				<input name="action" value="save" type="hidden" />
				<input name="id" value="<?php echo $_POST['id']; ?>" type="hidden" />

				<input name="nonce" value="<?php echo $_POST['nonce']; ?>" type="hidden" />
			</form>
			
			<script type="text/javascript">
				form = jQuery("#edit-<?php echo $_POST['id']; ?>");
				
				form.ajaxForm({
					url:pageTarget,
					dataType:"json",
					success:function(json){
						if ( json.success ) {
							jQuery("#group-" + json.group.id + " span.title").html(json.group.title);
							jQuery("#group-" + json.group.id + " .panel:visible").slideUp(500);
						}
						
					}
				});
				
				form.children().children("input.reset").click(function(){
					jQuery(this).parents(".panel:visible").slideUp(500);
				});
				
				form.find("input.releaseDate").calendar({dateFormat:"YMD-"});

			</script>
		<?php
		break;
	case 'save' :
		$args = $_POST;
		$id = $_POST['id'];
		unset($args['nonce'], $args['action'], $args['id']);
		$g = new group($id);
		foreach ( $args as $key => $value ) {
			$g->$key = $value;
		}
		if ( $g->save() ) {
			?>{success:true, group:<?php echo $g->toJSON(); ?>}<?php
		} else {
			?>{success:false, msg:"Database Error"}<?php
		}

		break;
	case 'songForm':
		//$songs = $wpdb->get_col('SELECT songID FROM ' . TABLE_SONG_GROUP . ' WHERE groupID = ' . (int) $_POST['id']);
		$s = new song();
		$s->search('
			id not in (
				SELECT songID FROM ' . TABLE_SONG_GROUP . ' WHERE groupID = ' . (int) $_POST['id'] . '
			)
		', 'title');
		?>
			<h4 class="no-margin">Select an existing song</h4>
			<form method="post" class="song new" id="existing-song-<?php echo $_POST['id']; ?>">
				<input name="new" value="0" type="hidden" />
				<input name="action" value="addSong" type="hidden" />
				<input name="groupID" value="<?php echo $_POST['id']; ?>" type="hidden" />
				<input name="nonce" value="<?php echo $_POST['nonce']; ?>" type="hidden" />
				
				<div>
					Select a song: 
					<select name="songID">
						<?php while ( $s->fetch() ) : ?>
							<option value="<?php echo $s->id; ?>"><?php echo $s->title; ?></option>
						<?php endwhile; ?>
					</select>
				</div>
				<div>
					<input name="submit" class="save" value="Add Song" type="submit" />
					<input name="submit" class="save" value="Add Song and Add Another" type="submit" />
					<input class="reset" value="Cancel" type="reset" />
				</div>
			</form>
			<h4 class="no-margin">Add a new song</h4>
			<form enctype="multipart/form-data" method="post" class="song new" id="new-song-<?php echo $_POST['id']; ?>">
				<div><label>Title: <input class="title wide" name="title" type="text" /></label></div>
				<div><label>Price: <input type="text" class="price" name="price" value="<?php if ( isset($options['songPrice']) ) echo $options['songPrice']; ?>" /></label></div>
				<div><label>Recording Artist: <input class="recordingArtist wide" name="recordingArtist" type="text" /></label></div>
				<div><label>Composer: <input class="composer wide" name="composer" type="text" /></label></div>
				<div><label>Track Length: <input class="length" name="length" type="text" /></label></div>
				<div><label>Purchase Download Link: <input class="purchaseDownloadLink wide" name="purchaseDownloadLink" type="text" /></label></div>
				<div><label>Free Download/Streaming Link: <input class="freeDownloadLink wide" name="freeDownloadLink" type="text" /></label></div>
				<!-- <div><label>Upload File (link will be auto-generated): <input class="fileUpload wide" name="fileUpload1" type="file" /></label></div> -->
				<div><label>
					Allow streaming? 
					<select name="allowStreaming">
						<option value="1">Yes</option>
						<option value="0">No</option>
					</select>
				</label></div>
				<div><label>
					Allow download? 
					<select name="allowDownload">
						<option value="1">Yes</option>
						<option value="0">No</option>
					</select>
				</label></div>

				<div><label>Recording Date <input class="recordingDate" name="recordingDate" type="text"  /></label></div>
				<div>Lyrics:<br><textarea class="lyrics" name="lyrics" rows="7" cols="80"></textarea></div>
				<div>Description/Other notes:<br><textarea class="description" name="description" rows="7" cols="80"></textarea></div>
				<div>
					<input name="submit" class="save" value="Add Song" type="submit" />
					<input name="submit" class="save" value="Add Song and Add Another" type="submit" />
					<input class="reset" value="Cancel" type="reset" />
				</div>
				<input name="new" value="1" type="hidden" />
				<input name="action" value="addSong" type="hidden" />
				<input name="groupID" value="<?php echo $_POST['id']; ?>" type="hidden" />

				<input name="nonce" value="<?php echo $_POST['nonce']; ?>" type="hidden" />
			</form>
			
			<script type="text/javascript">
				jQuery("form.song input.reset").click(function(){
					jQuery(this).parents(".panel").slideUp(500);
				});
				jQuery("#new-song-<?php echo $_POST['id']; ?> .recordingDate").calendar("YMD-");
			
				jQuery("#new-song-<?php echo $_POST['id']; ?>").ajaxForm({
					url:pageTarget,
					dataType: "json",
					success:function(json){
						if ( json.success ) {
							if ( json.another ) {
								jQuery("#new-song-" + json.groupID)[0].reset();
								jQuery("#new-song-" + json.groupID).find("input.title").focus();
							} else {
								jQuery("#new-song-" + json.groupID).parents(".panel").slideUp(500);
							}
						} else {
							console.log(json);
						}
					}
				});
				
				jQuery("#existing-song-<?php echo $_POST['id']; ?>").ajaxForm({
					url:pageTarget,
					dataType: "json",
					success:function(json){
						if ( json.success ) {
							if ( json.another ) {
								jQuery("#existing-song-" + json.groupID + " option[value=" + json.song.id + "]").remove();
								jQuery("#existing-song-" + json.groupID + " select").focus();
							} else {
								jQuery("#existing-song-" + json.groupID).parents(".panel").slideUp(500);
							}
						} else {
							console.log(json);
						}
					}
				});
			</script>
		<?php
		break;
	case 'addSong':
		if ( $_POST['new'] ) {
			$s = new song();
			$args = $_POST;
			unset($args['submit'], $args['new'], $args['action'], $args['groupID'], $args['nonce']);
			foreach ( $args as $key => $value ) {
				$s->$key = $value;
			}
			if ( $s->save() ) {
				$s->addToGroup($_POST['groupID']);
				?>{success:true, song:<?php echo $s->toJSON(); ?>, another:<?php echo $_POST['submit'] == 'Add Song and Add Another' ? 'true' : 'false'; ?>, groupID:<?php echo $_POST['groupID']; ?>}<?php
			} else {
				?>{success:false}<?php
			}
		} else {
			$s = new song($_POST['songID']);
			$s->addToGroup($_POST['groupID']);
			?>{success:true, song:<?php echo $s->toJSON(); ?>, another:<?php echo $_POST['submit'] == 'Add Song and Add Another' ? 'true' : 'false'; ?>, groupID:<?php echo $_POST['groupID']; ?>}<?php
		}
		break;
	case 'songs':
		$s = new song();
		$id = $_POST['id'];
		if ( $s->getFromGroup($_POST['id']) ) {
			?>
				<ol class="songs group-list" id="group-list-<?php echo $id; ?>">
					<?php while ( $s->fetch() ) : ?>
						<li id="song-<?php echo $s->id; ?>" class="song group-list-<?php echo $id; ?>">
							<img class="dragSong icon" alt="Drag" src="<?php echo $folder; ?>images/shape_square.png" />
							<img class="clickable icon remove-from-group" alt="Remove from group" title="Remove from group" src="<?php echo $folder; ?>images/delete.png" />
							<a href="<?php echo $s->get_link(); ?>">
								<?php echo $s->title; ?>
							</a>
						</li>
					<?php endwhile; ?>
				</ol>

				<script type="text/javascript">
					jQuery('#group-list-<?php echo $id; ?>').Sortable({
						accept : 'group-list-<?php echo $id; ?>',
						opacity: 	0.5,
						fit :	false,
						axis: 'vertically',
						handle: 'img.dragSong',						
						onStop:function(){
							var id = jQuery(this).parents("ol").attr("id");
							x = jQuery.SortSerialize(id);
							jQuery.post(pageTarget, {
								action:"sortSongs",
								songs:x.hash,
								id:id.split("-").pop(),
								"nonce":nonce
							});
						}
					});
					
					jQuery(".group-list .remove-from-group").click(function(){
						if ( confirm("Remove this song from this group/album?  This will not delete the song from the database.") ) {
							jQuery.post(pageTarget, {
								action:"removeSong",
								song:jQuery(this).parents("li.song").attr("id").split('-')[1],
								group:jQuery(this).parents("ol").attr("id").split('-')[2],
								nonce:nonce
							});
						}
					});
				</script>
			<?php
		} else {
			echo 'This album doesn\'t have any songs yet.  Click on the <img class="icon" src="' . $folder . 'images/music_add.png" /> icon to add some.';
		}
		break;
	case 'sortSongs' :
		parse_str($_POST['songs'], $songs);
		$songs = array_pop($songs);
		foreach ( $songs as $key => $id ) {
			$id = array_pop(explode('-', $id));
			$wpdb->query('UPDATE `' . TABLE_SONG_GROUP . '` SET `order` = ' . ($key + 1) . ' WHERE `songID` = ' . (int) $id . ' AND `groupID` = ' . (int) $_POST['id']);
		}
		break;
	case 'removeSong' :
		$s = new song($_POST['song']);
		var_dump($s->removeFromGroup($_POST['group']));
		break;
endswitch;

?>
