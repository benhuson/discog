<?php
require_once 'ajaxSetup.php';

switch ( $_POST['action'] ) :
	case 'load':
		?>
			<?php if ( !$options || empty($options['parent']) ) : ?>
				<div>You need to select a page to house your discography.  You may need to go create a <a href="page-new.php">new page</a> first.
			<?php endif; ?>
			<form id="settings-form" method="post">
				<input type="hidden" name="nonce" value="<?php echo $_POST['nonce']; ?>" />
				<input type="hidden" name="action" value="save" />
				<h3>General Options</h3>
				<div><label>
					Select a page to house your discography: 
					<?php wp_dropdown_pages('show_option_none=Select one...&name=parent&selected=' . $options['parent']); ?>
				</label></div>
		
				
				<h3>Songs</h3>
				<div><label>Default Song Price: <input type="text" class="songPrice price" name="songPrice" value="<?php if ( isset($options['songPrice']) ) echo $options['songPrice']; ?>" /></label></div>
				<div><label>
					Allow comments on songs: 
					<select name="openComments">
						<option value="open" <?php if ( isset($options['openComments']) && $options['openComments'] == 'open') echo 'selected="selected"'; ?>>Open</option>
						<option value="closed" <?php if ( isset($options['openComments']) && $options['openComments'] == 'closed') echo 'selected="selected"'; ?>>Closed</option>
					</select>
				</label></div>
				<div><label>
					Allow "pingbacks" on songs: 
					<select name="openPingbacks">
						<option value="open" <?php if ( isset($options['openPingbacks']) && $options['openPingbacks'] == 'open') echo 'selected="selected"'; ?>>Open</option>
						<option value="closed" <?php if ( isset($options['openPingbacks']) && $options['openPingbacks'] == 'closed') echo 'selected="selected"'; ?>>Closed</option>
					</select>
				</label></div>
				<div><label>
					Use the lightweight (but less secure) Delicious music player: 
					<select name="deliciousPlayer">
						<option value="0" <?php if ( isset($options['deliciousPlayer']) && $options['deliciousPlayer'] == 0) echo 'selected="selected"'; ?>>No</option>
						<option value="1" <?php if ( isset($options['deliciousPlayer']) && $options['deliciousPlayer'] == 1) echo 'selected="selected"'; ?>>Yes</option>
					</select>
				</label></div>
				
				<h3>Albums/Groups</h3>
				<div><label>Default Album/Group Price: <input type="text" class="groupPrice price" name="groupPrice" value="<?php if ( isset($options['groupPrice']) ) echo $options['groupPrice']; ?>" /></label></div>
				<div><label>Default "album artist": <input type="text" value="<?php if ( isset($options['artist']) ) echo $options['artist']; ?>" name="artist" /></label></div>
				
				<div><label>
					Uncategorized album/group ordering: 
					<select name="groupSortColumn">releaseDate
						<option value="releaseDate" <?php if ( isset($options['groupSortColumn']) && $options['groupSortColumn'] == 'releaseDate') echo 'selected="selected"'; ?>>Release Date</option>
						<option value="order" <?php if ( isset($options['groupSortColumn']) && $options['groupSortColumn'] == 'order') echo 'selected="selected"'; ?>>Custom</option>
						<option value="title" <?php if ( isset($options['groupSortColumn']) && $options['groupSortColumn'] == 'title') echo 'selected="selected"'; ?>>Alphabetical</option>
						<option value="id" <?php if ( isset($options['groupSortColumn']) && $options['groupSortColumn'] == 'id') echo 'selected="selected"'; ?>>Category ID</option>
					</select>
				</label></div>

				<div><label>
					Uncategorized album/group order direction: 
					<select name="groupSortDirection">
						<option value="ASC" <?php if ( isset($options['groupSortDirection']) && $options['groupSortDirection'] == 'ASC') echo 'selected="selected"'; ?>>Ascending</option>
						<option value="DESC" <?php if ( isset($options['groupSortDirection']) && $options['groupSortDirection'] == 'DESC') echo 'selected="selected"'; ?>>Descending</option>
					</select> 
				</label></div>

				<h3>Categories</h3>
				<div><label>
					Use Categories: 
					<select name="useCategories">
						<option value="1" <?php if ( isset($options['useCategories']) && $options['useCategories'] == '1') echo 'selected="selected"'; ?>>Yes</option>
						<option value="0" <?php if ( isset($options['useCategories']) && $options['useCategories'] == '0') echo 'selected="selected"'; ?>>No</option>
					</select>
				</label></div>

				<div><label>
					Category ordering: 
					<select name="categorySortColumn">
						<option value="order" <?php if ( isset($options['categorySortColumn']) && $options['categorySortColumn'] == 'order') echo 'selected="selected"'; ?>>Custom</option>
						<option value="name" <?php if ( isset($options['categorySortColumn']) && $options['categorySortColumn'] == 'name') echo 'selected="selected"'; ?>>Alphabetical</option>
						<option value="id" <?php if ( isset($options['categorySortColumn']) && $options['categorySortColumn'] == 'id') echo 'selected="selected"'; ?>>Category ID</option>
					</select>
				</label></div>

				<div><label>
					Category order direction: 
					<select name="categorySortDirection">
						<option value="ASC" <?php if ( isset($options['categorySortDirection']) && $options['categorySortDirection'] == 'ASC') echo 'selected="selected"'; ?>>Ascending</option>
						<option value="DESC" <?php if ( isset($options['categorySortDirection']) && $options['categorySortDirection'] == 'DESC') echo 'selected="selected"'; ?>>Descending</option>
					</select> 
				</label></div>
				
				<br /><br />
				<div><input type="submit" value="Save Options" /></div>
			</form>
			
			<div class="todo">
				<h3>Settings to come</h3>
				<ul>
					<li>
						Albums/Groups:
						<ul>
							<li>Default song ordering</li>
						</ul>
					</li>
					<li>
						Albums/Groups:
						<ul>
						</ul>
					</li>
					<li>
						Categories:
						<ul>
						</ul>
					</li>
					
				</ul>		
			</div>
			
			<script type="text/javascript">
				setupEvents = function() {
					jQuery("#settings form").ajaxForm({
						url:pageTarget,
						success:function(){
							if ( jQuery("#music-menu li").length == 1 ) {
								window.location.reload();
							}
						}
					});
				}
				
				setTimeout('setupEvents();', 0);
				
				
				
			</script>
			

		<?php
		break;
	case 'save':
		$args = $_POST;
		unset($args['nonce'], $args['action']);
		update_option('discography', $args);
		$option = get_option('discography');
		break;
endswitch;

