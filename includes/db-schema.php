<?php

class Discography_DB_Schema {
	
	/**
	 * DB Schema class constructor.
	 * Automatically tries to upgrade the schema when called.
	 */
	function Discography_DB_Schema() {
		$this->upgrade_schema();
	}
	
	/**
	 * Upgrade the DB Schema.
	 * All lines withing the CREATE TABLE function must be prexised
	 * by 2 spaces. The PRIMARY KEY must also be followed by 2 spaces.
	 */
	function upgrade_schema() {
		global $wpdb;
		$installed_db_version = get_option( 'discography_db_version' );
		if ( $installed_db_version != DISCOGRAPHY_DB_VERSION ) {
			
			// Table: Discography Category Meta
			$table_discography_categorymeta = "CREATE TABLE " . $wpdb->prefix . "discography_categorymeta (
			  meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  discography_category_id bigint(20) unsigned NOT NULL DEFAULT '0',
			  meta_key varchar(255) DEFAULT NULL,
			  meta_value longtext,
			  PRIMARY KEY  (meta_id),
			  KEY post_id (discography_category_id),
			  KEY meta_key (meta_key)
			);";
			
			// Update Schema
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $table_discography_categorymeta );
			update_option( 'discography_db_version', DISCOGRAPHY_DB_VERSION );
		}
	}
	
}

?>