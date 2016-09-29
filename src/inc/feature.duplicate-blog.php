<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Database\WPDBTableStringReplacer;
use Inpsyde\MultilingualPress\Database\WPDBTableDuplicator;
use Inpsyde\MultilingualPress\Database\WPDBTableReplacer;

add_action( 'inpsyde_mlp_loaded', 'mlp_feature_duplicate_blog' );

/**
 * @param Inpsyde_Property_List_Interface $data Plugin data.
 *
 * @return void
 */
function mlp_feature_duplicate_blog( Inpsyde_Property_List_Interface $data ) {

	global $wpdb;

	$duplicator = new Mlp_Duplicate_Blogs(
		$data->get( 'link_table' ),
		$wpdb,
		new WPDBTableDuplicator(),
		new WPDBTableReplacer(),
		$data->get( 'table_list' ),
		new WPDBTableStringReplacer()
	);
	$duplicator->setup();
}
