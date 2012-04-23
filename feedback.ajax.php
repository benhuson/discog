<?php

require_once 'ajaxSetup.php';

if ( 'load' == $_POST['action'] ) {
	$userinfo = wp_get_current_user();
	//print_r($userinfo);
	?>
		<h3 class="no-margin">Want to tell me what a great/horrible job I did?</h3>
		<form id="submit-feedback" class="feedback" action="<?php echo $folder;?>feedback.ajax.php" method="post">
			<input type="hidden" name="nonce" value="<?php echo $_POST['nonce']; ?>" />
			<input type="hidden" name="action" value="feedback" />
			<div>
				<label>Your name: <input type="text" name="name" value="<?php echo $userinfo->display_name; ?>" /></label>
				<select id="nameOptions">
					<option><?php echo $userinfo->display_name; ?></option>
					<option><?php echo $userinfo->nickname; ?></option>
					<?php if ( isset($userinfo->user_firstname) ) : ?>
						<option><?php echo $userinfo->user_firstname; ?></option>
						<?php if ( isset($userinfo->user_lastname) ) : ?>
							<option><?php echo $userinfo->user_firstname; ?> <?php echo $userinfo->user_lastname; ?></option>
						<?php endif; ?>
					<?php endif; ?>
				</select>
			</div>
			<div><label>Email Address: <input type="text" name="email" value="<?php echo $userinfo->user_email; ?>" /></label></div>
			<div><label>
				Type of feedback: 
				<select name="type">
					<option>Bug Report</option>
					<option>Feedback/Comments</option>
					<option>Feature Request</option>
				</select>
			</label></div>
			<div>If you're reporting a bug, please be as specific as possible.<br /><textarea name="message"></textarea></div>
			<h4 class="no-margin">Additional information that might help (deselect anything you don't want to send)</h4>
			<div id="additional-info">
				<div><label><input type="checkbox" value="blogurl" checked="checked" /> Blog URL</label> <input type="text" readonly="readonly" value="<?php bloginfo('wpurl'); ?>" name="blogurl" id="blogurl" /></div>
				<div><label><input type="checkbox" value="wpversion" checked="checked" /> WordPress Version</label> <input type="text" readonly="readonly" value="<?php echo $wp_version; ?>" name="wpversion" id="wpversion" /></div>
				<div><label><input type="checkbox" value="phpversion" checked="checked" /> PHP Version</label> <input type="text" readonly="readonly" value="<?php echo phpversion(); ?>" name="phpversion" id="phpversion" /></div>
			</div>
			<input type="submit" value="Send Feedback" />
		</form>
		<script type="text/javascript">
			
			target = ajaxTarget + "categories.ajax.php";
			jQuery("#additional-info input:checkbox").change(function(){
				$(this.value).disabled = !this.checked;
			});
			
			jQuery("form#submit-feedback").ajaxForm({
				dataType: "json",
				success:function(json){
					if ( json.success ) {
						jQuery("#feedback").html('<div style="text-align: center">Thanks for your feedback!  I\'ll try to get back to you as soon as I can.</div>');
					}
				}
			});
			
			(function($){
				$("input[name=name]").focus();
				
				$("#nameOptions").change(function(){
					$("input[name=name]").val(($(this).val()));
				});
			}(jQuery));
		</script>
	<?php
} elseif ( 'feedback' == $_POST['action'] ) {
	$feedbackEmail = 'feedback.music' . (true?'@':'blah') . 'ssdn.us';  // Take that, spambots reading my repository!
	$msg = '';
	foreach ( $_POST as $key => $value ) {
		if ( $key != 'nonce' && $key != 'message' && $key != 'action' ) {
			$msg .= $key . ': ' . $value . "\n";
		}
	}
	
	$msg .= 'Plugin Version: ' . DTC_DISC_PLUGIN_VERSION . "\n";
	$msg .= 'Database Version: ' . DTC_DISC_DB_VERSION . "\n";
	
	$msg .= "\nMessage:\n" . $_POST['message'];
	if ( wp_mail($feedbackEmail, 'Discography - ' . $_POST['type'], $msg, 'Reply-To: ' . str_replace(array("\r", "\n"), '', $_POST['email'])) ) {
		echo '{success: true}';
	} else {
		echo '{success: false}';
	}
}

?>
