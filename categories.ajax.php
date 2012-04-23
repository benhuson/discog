<?php

require_once 'ajaxSetup.php';

if ( 'load' == $_POST['action'] ) {
	$categories = $wpdb->get_results('SELECT * FROM `' . TABLE_CATS . '` ORDER BY `' . $options['categorySortColumn'] .'` ' . $options['categorySortDirection']);
	?>
		<div class="clickable" id="category-add-trigger">
			<img class="icon" src="<?php echo $folder; ?>images/add.png" /> Add another category
		</div>
		<div id="category-add-form" style="display: none;">
			<form id="new-category" method="post" action="<?php echo $folder; ?>categories.ajax.php">
				<input type="text" id="new-category-name" name="name" />
				<input type="submit" name="" value="Add category" />
				<input style="display: inline" type="reset" name="" value="Cancel" id="new-category-reset" />
				<input type="hidden" name="action" value="add" />
				<input type="hidden" name="nonce" value="<?php echo $_POST['nonce']; ?>" />
			</form>
		</div>
		<div style="padding-top: 1em;">Drag and order the categories using this icon: <img class="icon" src="<?php echo $folder; ?>images/shape_square.png" />  (I'll find something better later)</div>
		<ul id="music-categories" class="items sort-<?php echo $options['categorySortColumn']; ?> direction-<?php echo $options['categorySortDirection']; ?>">
			<?php foreach ( $categories as $cat ) : 
				dtc_discography_utilities::listItem($cat->id, $cat->name, 'category', array('page_white_edit' => 'Edit', 'delete' => 'Delete'));
			endforeach; ?>
		</ul>

		<script type="text/javascript">
			target = ajaxTarget + "categories.ajax.php";
			
			jQuery("#category-add-trigger").click(function(){
				jQuery("#category-add-form:hidden").slideDown(300, function(){
					jQuery("#new-category-name").focus();
				});
			});
			
			jQuery("#new-category-reset").click(function(){
				jQuery("#category-add-form").slideUp(300);
			});
			
			jQuery("#new-category").ajaxForm({
				dataType: "json",
				success:function(json){
					if ( json.success ) {
						var newItem = listItem(json.id, jQuery("#new-category-name").val(), 'category', [['page_white_edit', 'Edit'],['delete', 'Delete']]);
						<?php if ( $options['categorySortColumn'] == 'order' || $options['categorySortColumn'] == 'id' ) : ?>
							jQuery('#music-categories').<?php echo ($options['categorySortDirection'] == 'ASC' ? 'append' : 'prepend'); ?>(newItem);
						<?php endif; ?>
						<?php if ( $options['categorySortColumn'] == 'name' ) : ?>
							var items = jQuery('#music-categories li');
							var newTitle = jQuery("#new-category-name").val();
							for ( i = 0; i < items.size(); i++ ) {
								if ( newTitle < items.eq(i).children().children('span.title').html() ) {
									items.eq(i).<?php echo ($options['categorySortDirection'] == 'ASC' ? 'before' : 'after'); ?>(newItem);
									break;
								}
							}
						<?php endif; ?>
						
						<?php if ( $options['categorySortColumn'] == 'order' ) : ?>
							jQuery('#music-categories').SortableAddItem('#category-' + json.id);
							jQuery('#category-' + json.id + ' div').prepend('<img class="drag" alt="Drag" src="<?php echo $folder; ?>images/shape_square.png" />');
						<?php endif; ?>
						jQuery("#new-category-reset").click();
						setupEvents();
					}
				}
			});
			
			setupEvents = function() {
				//jQuery('.drag').remove();
			
				jQuery(".category .buttons .delete, .category .buttons .edit").unbind("click");
				jQuery(".category .delete").click(function() {
					if ( confirm('Are you sure you want to delete "' + jQuery(this).parents("div").children("span.title").html() + '"? All albums in this category will be left uncategorized') ) {
						item = jQuery(this).parents("li");
						item.hide(500, function(){jQuery(this).remove();});
						jQuery.post(target, {
							action:"delete",
							"nonce":nonce,
							id:item.attr("id").split("-")[1]
						});
					}
				});
				jQuery(".category .buttons .edit").click(function(){
					//var target = jQuery(this).parents("li").attr("id");
					var target = jQuery(this).parents("li").children(".edit.panel");
					target.load(pageTarget, {
						id: jQuery(this).parents("li").attr("id").split("-")[1],
						action:"editPanel",
						nonce:nonce
					}, function() {
						target.filter(":hidden").slideDown(500);
					});
				});
				
				jQuery('ul.sortable').SortableDestroy().removeClass("sortable");
				
				jQuery('ul.items').Sortable({
					accept : 'item',
					opacity: 	0.5,
					fit :	false,
					axis: 'vertically',
					handle: 'img.drag',
					onStop:function(){
						var id = jQuery(this).parents("ul").attr("id");
						x = jQuery.SortSerialize(id);
						jQuery.post(pageTarget, {
							action:"sort",
							list:x.hash,
							id:id,
							"nonce":nonce
						});
					}

				});
				
				jQuery('ul.items').addClass("sortable");

			};
			
			jQuery('ul.sort-order li div').prepend('<img class="drag" alt="Drag" src="<?php echo $folder; ?>images/shape_square.png" />');
			setupEvents();
			
			
			

		</script>

		<div class="todo">
			<h3>Todo:</h3>
			<ul>
				<li>add custom data capabilities</li>
			</ul>
		</div>

	<?php 
} elseif ( 'add' == $_POST['action'] ) {
	if ( !empty($_POST['name']) ) {
		$c = new category();
		$c->name = $_POST['name'];
		$c->slug = dtc_discography_utilities::generateSlug($_POST['name']);
		$c->order = $wpdb->get_var('SELECT MAX(`order`) FROM `' . TABLE_CATS . '`') + 1;
		$result = $c->save();
		if ( (bool) $result ) {
			?>{success:true,id:<?php echo $c->id; ?>}<?php
		} else {
			?>{success:false,msg:"Database error"}<?php
		}
	} else {
		?>{success:false,msg:"You didn't enter a category name"}<?php
	}
} elseif ( 'sort' == $_POST['action'] ) {
	parse_str($_POST['list'], $categories);
	$categories = array_pop($categories);
	$c = new category();
	foreach ( $categories as $key => $id ) {
		$id = substr($id, 9);
		$c->get($id);
		$c->order = $key;
		$c->save();
	}
} elseif ( 'delete' == $_POST['action'] ) {
	$c = new category($_POST['id']);
	$c->delete();
} elseif ( 'editPanel' == $_POST['action'] ) {
	$category = $wpdb->get_row('SELECT * FROM `' . TABLE_CATS . '` WHERE `id`  = ' . (int) $_POST['id']);
	$orderColumns = array(
		'title' => 'Alphabetical',
		'releaseDate' => 'Release Date',
		'id' => 'ID',
		'order' => 'Custom'
	);
	$orderDirection = array(
		'ASC' => 'Ascending',
		'DESC' => 'Descending',
	);
	?>
		<form id="edit-<?php echo $category->id; ?>" method="post">
			<input type="hidden" name="id" value="<?php echo $category->id; ?>" />
			<input type="hidden" name="nonce" value="<?php echo $_POST['nonce']; ?>" />
			<input type="hidden" name="action" value="saveCategory" />
			<div><label>Title: <input name="name" type="text" value="<?php echo $category->name; ?>" /></label></div>
			<div><label>
				Sort albums in this category by: 
				<select name="orderColumn">
					<?php foreach ( $orderColumns as $key => $value ) : ?>
						<option value="<?php echo $key; ?>" <?php echo $category->orderColumn == $key ? 'selected="selected"' : ''; ?>><?php echo $value; ?></option>
					<?php endforeach; ?>
				</select>
			</label></div>
			<div><label>
				Ordering Direction: 
				<select name="orderDirection">
					<?php foreach ( $orderDirection as $key => $value ) : ?>
						<option value="<?php echo $key; ?>" <?php echo $category->orderDirection == $key ? 'selected="selected"' : ''; ?>><?php echo $value; ?></option>
					<?php endforeach; ?>
				</select>
			</label></div>
			<div>
				Other notes <br />
				<textarea name="notes" rows="7" cols="60"><?php echo $category->notes; ?></textarea>
			</div>
			<div>
				<input type="submit" value="Save Changes" /> <input type="reset" value="Cancel" />
			</div>
		</form>
		<script type="text/javascript">
			jQuery("#edit-<?php echo $category->id; ?> input").unbind("click");
			jQuery("#edit-<?php echo $category->id; ?> input:reset").click(function(){
				jQuery(this).parents("div.panel").slideUp(500);
			});
			jQuery("#edit-<?php echo $category->id; ?>").ajaxForm({
				url : pageTarget,
				success:function(){
					jQuery("#edit-<?php echo $category->id; ?>").parents("li").children().children("span.title").html(jQuery("#edit-<?php echo $category->id; ?> input:text").val());
					jQuery("#edit-<?php echo $category->id; ?>").parents("div.panel").slideUp(500);
				}
			})
		</script>
	<?php
} elseif ( 'saveCategory' == $_POST['action'] ) {
	$wpdb->query('UPDATE `' . TABLE_CATS . '` SET name = "' . $wpdb->escape($_POST['name']) . '", notes = "' . $wpdb->escape($_POST['notes']) . '", orderColumn = "' . $wpdb->escape($_POST['orderColumn']) . '", orderDirection = "' . $wpdb->escape($_POST['orderDirection']) . '" WHERE `id`  = ' . (int) $_POST['id']);
}
?>
