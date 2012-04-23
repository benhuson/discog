<?php
ereg('(.*/)wp\-content/', __FILE__, $path);
require_once($path[1] . 'wp-config.php');
require_once('classes.php');
require_once('version.php');

if ( !is_user_logged_in() ) die("not logged in");

if ( !wp_verify_nonce($_POST['nonce']) ) die("invalid nonce");

$folder = get_bloginfo('wpurl') . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/';
$options = get_option('discography');

$default_options = array(
	'deliciousPlayer' => 0,
);

$options = dtcDisc::getOptions();
?>
