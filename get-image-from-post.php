<?php
/*
Plugin Name: Get Image from Post
Plugin URI:
Description: Used to fetch images from your posts to display as part of the excerpt.
Tags: adopt-me
Author: Hors Hipsrectors
Author URI:
Version: 2017.08.13
*/

/**
 * Get Image from Post core file
 *
 * This file contains all the logic required for the plugin
 *
 * @link		http://wordpress.org/extend/plugins/get-image-from-posts/
 *
 * @package		Get Image from Post
 * @copyright		Copyright ( c ) 2017, Hors Hipsrectors
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, v2 ( or newer )
 *
 * @since		Get Image from Post 1.0
 */

function horshipsrectors_get_image_from_post( $options = '' ) {
	global $post, $wpdb;

	$ns_options = array(
		"image" => "1",
		"show" => false,
		"link" => false,
		"width" => false,
		"height" => false,
		"strip" => false,
		);

	$options = explode( "&", $options );

	foreach ( $options as $option ) {
		$parts = explode( "=", $option );
		if ( $parts[1] )
			$ns_options[$parts[0]] = $parts[1];
	}

	global $more;
	$more = 1;
	$content = strtolower( get_the_content() );
	$content = explode( "<img", $content ) ;

	if ( $content ) {
		foreach ( $content as $image ) {
			$newimage = explode( ">", $image );
			$thisimage[] =	"<img ".$newimage[0]."/>";
		}

		$more = 0;
		$link = get_permalink();

		if ( $strip )
			$thisimage[$ns_options['image']] = horshipsrectors_strip_tags_attributes( $thisimage[$ns_options['image']], "img", "src" );
		if ( $show )
			echo $thisimage[$ns_options['image']];} else {return  $thisimage[$ns_options['image']];
	}
}

function horshipsrectors_strip_tags_attributes( $string, $allowtags = NULL, $allowattributes = NULL ){
	$string = strip_tags( $string, $allowtags );
	if ( ! is_null( $allowattributes ) ) {
		if( ! is_array( $allowattributes ) )
			$allowattributes = explode( ",",  $allowattributes );
		if( is_array( $allowattributes ) )
			$allowattributes = implode( " )( ?<!", $allowattributes );
		if ( strlen( $allowattributes ) > 0 )
			$allowattributes = "( ?<!".$allowattributes." )";
		$string = preg_replace_callback( "/<[^>]*>/i", create_function(
			'$matches',
			'return preg_replace( "/ [^ =]*'.$allowattributes.'=( \"[^\"]*\"|\'[^\']*\' )/i", "", $matches[0] );'
		), $string );
	}
	return $string;
}

?>
