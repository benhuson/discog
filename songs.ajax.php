<?php

require_once 'ajaxSetup.php';

switch ( $_REQUEST['action'] ) {
	case 'load':
		$s = new song();
		$s->search(null, isset($_POST['sortBy']) ? $_POST['sortBy'] : 'id DESC');
		$count = 0;
		?>
			<label>
				Sort by:
				<select id="sortBy">
					<option value="id DESC" <?php if ( $_POST['sortBy'] == 'id DESC' ) echo 'selected="selected"'; ?>>ID</option>
					<option value="title" <?php if ( $_POST['sortBy'] == 'title' ) echo 'selected="selected"'; ?>>Title</option>
					<option value="recordingDate DESC" <?php if ( $_POST['sortBy'] == 'recordingDate DESC' ) echo 'selected="selected"'; ?>>Recording Date</option>
				</select>
			</label>
			<table class="songs widefat">
				<thead>
					<tr>
						<th style="text-align: center;" scope="col">ID</th>
						<th scope="col">Title</th>
						<th scope="col">Download</th>
						<th scope="col">Streaming</th>
						<th style="text-align: center" scope="col">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php while ( $s->fetch() ) : ?>
						<tr id="song-<?php echo $s->id; ?>" class="song-<?php echo $s->id; ?> <?php echo ++$count % 2 ? "alternate" : "";?>">
							<th style="text-align: center;" scope="row"><?php echo $s->id; ?></th>
							<td class="title"><?php echo $s->title; ?></td>
							<td class="allowDownload"><?php echo $s->allowDownload ? "Yes" : "No"; ?></td>
							<td class="allowStreaming"><?php echo $s->allowStreaming ? "Yes" : "No"; ?></td>
							<td style="text-align: center; position: relative;">
								<div style="position: relative">
									<a style="border: 0px;" href="<?php echo get_bloginfo('wpurl') . '/' . get_page_uri($s->postID); ?>"><img alt="View Page" title="View Page" class="clickable view" src="<?php echo $folder; ?>images/page_white_magnify.png" /></a>
									<img alt="Edit" title="Edit" class="clickable edit" src="<?php echo $folder; ?>images/page_white_edit.png" />
									<img alt="Albums/Groups" title="Albums/Groups" class="clickable groups" src="<?php echo $folder; ?>images/cd.png" />
									<img alt="Delete" title="Delete" class="clickable delete" src="<?php echo $folder; ?>images/delete.png" />
								</div>
							</td>
						</tr>
						<tr id="song-panel-<?php echo $s->id; ?>" class="song-<?php echo $s->id; ?> panel <?php echo $count % 2 ? "alternate" : "";?>">
							<td style="background-color: white"></td>
							<td class="panel" colspan="4"></td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
			
			<script type="text/javascript">

				(function($){
					$("#sortBy").change(function(){
						try{pageDestroy();}catch(e){};
						$("#loading").show();
						$(".music-page:visible").hide();
						$("#songs").load(pageTarget, {
							action:"load", 
							"page":"songs",
							sortBy:$(this).val(),
							"nonce":nonce
						}, function(){
							$("#loading").hide();
							$(".music-page:visible").hide();
							$("#songs").show();
						});
					});
					
					setupEvents = function() {
						jQuery("#songs img.clickable").unbind("click");
						
						jQuery("#songs img.clickable").click(function(){
							if ( jQuery(this).attr("title") != "Delete" ) {
								var panel = jQuery(this).parents("tr").next()
								panel.children(".panel").html("<div style='text-align: center; height: 23px;'><img src='http://music.ssdn.us/wp-content/plugins/discography/images/ajax-loader.gif' /></div>");
								panel.css("display", "table-row");
							}
						});
						
						jQuery("#songs .edit").click(function(){
							var panel = $(this).parents("tr").next().children(".panel");
							panel.load(pageTarget, {
								action:'edit',
								nonce:nonce,
								id:jQuery(this).parents("tr").attr("id").split("-")[1]
							}, function() {
								panel.children("div").append('<div class="clickable close" style="position: absolute; top:0px; right:0px;"><img alt="Close" title="Close" src="<?php echo $folder; ?>images/cancel.png" /></div>');
								$("div.close").click(function(){
									$(this).parents("tr").hide();
								});
							});
						});
						jQuery("#songs .groups").click(function(){
							var panel = $(this).parents("tr").next().children(".panel");
							jQuery(panel).load(pageTarget, {
								action:'groups',
								nonce:nonce,
								id:jQuery(this).parents("tr").attr("id").split("-")[1]
							}, function() {
							console.log(panel);
								panel.children("div").append('<div class="clickable close" style="position: absolute; top:0px; right:0px;"><img alt="Close" title="Close" src="<?php echo $folder; ?>images/cancel.png" /></div>');
								$("div.close").click(function(){
									$(this).parents("tr").hide();
								});
							});
						});
						jQuery("#songs .delete").click(function(){
							if ( confirm("Delete \"" + jQuery(this).parents("tr").find("td.title").html() + "\"? This cannot be undone.") ) {
								jQuery("tr.song-" + jQuery(this).parents("tr").attr("id").split("-")[1]).remove();
								resetRows();
								jQuery.post(pageTarget, {
									action:'delete',
									nonce:nonce,
									id:jQuery(this).parents("tr").attr("id").split("-")[1]
								}, function() {
								
								});
							}
						});
					}
					
					resetRows = function() {
						var rows = jQuery("table.songs tbody tr");
						for ( i = 0; i < rows.length; i++ ) {
							jQuery(rows[i]).removeClass("alternate");
							if ( !(Math.floor(i/2) % 2) ) {
								jQuery(rows[i]).addClass("alternate");
							}
						}
					}
					
					setupEvents();
				}(jQuery))
			

			</script>
			
			<div class="todo">
				<h3>Todo</h3>
				<ul class="todo">
				</ul>
			</div>

		<?php
		break;
	case 'edit':
		$s = new song($_POST['id']);
		?>
			<form enctype="multipart/form-data" method="post" class="song" id="edit-song-<?php echo $_POST['id']; ?>">
				<div><label>Title: <input value="<?php echo dtcDisc::escapeForInput($s->title); ?>" class="title wide" name="title" type="text" /></label></div>
				<div><label>Price: <input value="<?php echo dtcDisc::escapeForInput($s->price); ?>" class="price" name="price" type="text" /></label></div>
				<div><label>Recording Artist: <input value="<?php echo dtcDisc::escapeForInput($s->recordingArtist); ?>"  class="recordingArtist wide" name="recordingArtist" type="text" /></label></div>
				<div><label>Composer: <input value="<?php echo dtcDisc::escapeForInput($s->composer); ?>"  class="composer wide" name="composer" type="text" /></label></div>
				<div><label>Track Length: <input value="<?php echo dtcDisc::escapeForInput($s->length); ?>"  class="length" name="length" type="text" /></label></div>
				<div><label>Purchase Download Link: <input value="<?php echo dtcDisc::escapeForInput($s->purchaseDownloadLink); ?>"  class="purchaseDownloadLink wide" name="purchaseDownloadLink" type="text" /></label></div>
				<div><label>Free Download/Streaming Link: <input value="<?php echo dtcDisc::escapeForInput($s->freeDownloadLink); ?>"  class="freeDownloadLink wide" name="freeDownloadLink" type="text" /></label></div>
				<!-- <div><label>Upload File (link will be auto-generated): <input class="fileUpload wide" name="fileUpload1" type="file" /></label></div> -->
				<div><label>
					Allow streaming? 
					<select name="allowStreaming">
						<option value="1" <?php if ( $s->allowStreaming == 1 ) echo 'selected="selected"'; ?>>Yes</option>
						<option value="0" <?php if ( $s->allowStreaming == 0 ) echo 'selected="selected"'; ?>>No</option>
					</select>
				</label></div>
				<div><label>
					Allow download? 
					<select name="allowDownload">
						<option value="1" <?php if ( $s->allowDownload == 1 ) echo 'selected="selected"'; ?>>Yes</option>
						<option value="0" <?php if ( $s->allowDownload == 0 ) echo 'selected="selected"'; ?>>No</option>
					</select>
				</label></div>

				<div><label>Recording Date <input value="<?php echo dtcDisc::escapeForInput($s->recordingDate); ?>"  class="recordingDate" name="recordingDate" type="text"  /></label></div>
				<div>Lyrics:<br><textarea class="lyrics" name="lyrics" rows="7" cols="80"><?php echo $s->lyrics; ?></textarea></div>
				<div>Description/Other notes:<br><textarea class="description" name="description" rows="7" cols="80"><?php echo $s->description; ?></textarea></div>
				<div>
					<input class="save" value="Save Song" type="submit" />
					<input class="reset" value="Cancel" type="reset" />
				</div>
				<input name="action" value="saveSong" type="hidden" />
				<input name="id" value="<?php echo $_POST['id']; ?>" type="hidden" />

				<input name="nonce" value="<?php echo $_POST['nonce']; ?>" type="hidden" />
			</form>

			<script type="text/javascript">
				(function($){
					$("#edit-song-<?php echo $_POST['id']; ?> .recordingDate").calendar({dateFormat:"YMD-"});
					
					$("#edit-song-<?php echo $_POST['id']; ?> .reset").click(function(){
						$(this).parents("tr").hide();
					});
					
					$("#edit-song-<?php echo $_POST['id']; ?>").ajaxForm({
					url:pageTarget,
					dataType: "json", 
					success:function(json){
						if ( json.success ) {
							console.log(json);
							$("#song-panel-" + json.song.id).hide();
						}
					}
					
				});
				}(jQuery));
			</script>
		<?php
		break;
	case 'groups':
		$s = new song($_POST['id']);
		$g = $s->get_groups();
		$groups = array();
		while ( $g->fetch() ) {
			$groups[] = $g->id;
		}
		$g->search(null, 'title');
		?>
			<div id="song-groups-<?php echo $s->id; ?>">
				<h4 class="no-margin">This song is in these albums/groups:</h4>
				<ul>
					<?php while ( $g->fetch() ) : ?>
						<li class="group-<?php echo $g->id; ?>" style="<?php if ( !in_array($g->id, $groups) ) echo 'display: none;'; ?>" id="in-<?php echo $g->id; ?>"><label><input class="in" type="checkbox" value="<?php echo $g->id; ?>" checked="checked" /> <?php echo $g->title; ?></label></li>
					<?php endwhile; ?>
				</ul>
				<?php
				
				$g->search(null, 'title');
				?>
				<h4 class="no-margin">And not in these:</h4>
				<ul>
					<?php while ( $g->fetch() ) : ?>
						<li class="group-<?php echo $g->id; ?>" style="<?php if ( in_array($g->id, $groups) ) echo 'display: none;'; ?>" id="out-<?php echo $g->id; ?>"><label><input class="out" type="checkbox" value="<?php echo $g->id; ?>" /> <?php echo $g->title; ?></label></li>
					<?php endwhile; ?>
				</ul>
			</div>
			<script type="text/javascript">
				(function($){
					$("#song-groups-<?php echo $s->id; ?> input:checkbox").click(function(){
						el = $(this);
						var add;
						if ( el.hasClass("in") ) {
							el.attr("checked", true);
							add = 0;
						} else {
							el.attr("checked", false);						
							add = 1;
						}
						$(".group-" + el.val()).toggle();
						$.post(pageTarget, {
							nonce:nonce,
							action:"group",
							songID:<?php echo $s->id; ?>,
							groupID:el.val(),
							add:add
						});
					});
				}(jQuery));
			</script>
		<?php

		break;
	case 'group':
		$s = new song($_POST['songID']);
		if ( $_POST['add'] ) $s->addToGroup($_POST['groupID']);
		else $s->removeFromGroup($_POST['groupID']);
	case 'delete':
		$s = new song($_POST['id']);
		$s->delete();
		break;
	case 'saveSong':
		$s = new song($_POST['id']);
		$args = $_POST;
		unset($args['id'],$args['nonce'],$args['action']);
		foreach ( $args as $key => $value ) {
			$s->$key = $value;
		}
		if ( $s->save() ) echo '{success:true, song:' . $s->toJSON() . '}';
		else echo '{success:false}';
		break;
}

?>
