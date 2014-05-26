<?php
/*
Plugin Name: Swipe Slider
Plugin URI: 
Description: WordPress plugin for mobile-friendly swipe slider based on "Owl Carousel" jQuery plugin [cpad_slideshow]
Version: 1.0
Author: Viktor Kovalenko
Author URI: http://creative-design-lab.co.uk/
License: GPL & MIT
*/

/*  Copyright 2014  Viktor Kovalenko  (email: vitek21@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function cpad_register_scripts() {
	if (!is_admin()) {
		wp_register_script('cpad_min-script', plugins_url('owl.carousel.min.js', __FILE__));
		wp_enqueue_script('cpad_min-script');
	}
}

function cpad_register_styles() {
	if (!is_admin()) {
		wp_register_style('cpad_styles', plugins_url('owl.carousel.css', __FILE__));
		wp_enqueue_style('cpad_styles');
	}
}

function cpad_function($type='cpad_function') {
	$args = array(
		'post_type' => 'cpad_slider',
		'posts_per_page' => -1,
		'order' => 'ASC'
	);
	
	$output = '<div id="cpad_slider" class="owl-carousel clearfix">';
	$loop = new WP_Query($args);
	
	while ($loop->have_posts()) {
		$loop->the_post();
		$slide_img_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), $type);
		$the_slide_url = get_post_meta(get_the_id(), 'cpad_slide_url_textbox', true);
		    
		if ( get_post_meta($post->ID, 'cpad_slide_url_textbox', true) !== '' ) {
			$output .='<a href="'. $the_slide_url .'" title="'.get_the_title().'"><img src="'.$slide_img_src[0].'" data-thumb="'.$slide_img_src[0].'" alt="'.get_the_title().'"/></a>';
		} else {
			$output .='<div><img title="'.get_the_title().'" src="'.$slide_img_src[0].'" data-thumb="'.$slide_img_src[0].'" alt="'.get_the_title().'"/></div>';
		}
	}
	
	$output .= '</div>';
	return $output;
}

function cpad_init() {
	add_shortcode('cpad_slideshow', 'cpad_function');
	$args = array(
		'public' => true,
		'label' => 'Swipe Slider',
		'menu_icon' => 'dashicons-format-gallery',	
		'supports' => array(
			'title',
			'thumbnail',
			//'custom-fields'
		),
		'rewrite' => array(
			'slug' => 'slider'
		)
	);
	register_post_type('cpad_slider', $args);
}

// Add custom admin columns
add_filter( 'manage_edit-cpad_slider_columns', 'set_custom_edit_cpad_slider_columns' );
add_action( 'manage_cpad_slider_posts_custom_column' , 'custom_cpad_slider_column', 10, 2 );

function set_custom_edit_cpad_slider_columns() {
    return array(
		'cb' => '<input type=”checkbox” />',
		'cpad_slider_thumbs' => __('Slide Preview'),
		'title' => __('Slide Title'),
		'cpad_slide_url_textbox' => __('Slide URL'),
		'date' => __('Date')
	);
}

function custom_cpad_slider_column( $column_name, $post_id ) {
    global $post;
	switch($column_name){
		case "cpad_slider_thumbs":
		    echo the_post_thumbnail('thumbnail');
		    break;
		case "cpad_slide_url_textbox":
		    $custom = get_post_custom();
		    echo $custom["cpad_slide_url_textbox"][0];
		    break;
	}
}

// Add Custom CSS to admin columns
function cpad_custom_admin_css() {
	echo '<style>';
	echo '#cpad_slider_thumbs{width:150px}';
	echo '.post-type-cpad_slider .column-title{width:auto!important}';
	echo '</style>';
}
add_action('admin_head', 'cpad_custom_admin_css');

// Add Custom Slide URL Meta Box
add_action( 'add_meta_boxes', 'cd_meta_box_add' );
function cd_meta_box_add() {
	add_meta_box( 'cpad_slide_url_metabox', 'Slide URL', 'cpad_slide_url', 'cpad_slider', 'normal', 'high' ); 
}

function cpad_slide_url( $post ) {
	$values = get_post_custom( $post->ID );
	$text = isset( $values['cpad_slide_url_textbox'] ) ? esc_attr( $values['cpad_slide_url_textbox'][0] ) : '';
	wp_nonce_field( 'my_meta_box_nonce', 'meta_box_nonce' ); ?>	
	<p>
		<label style="display:inline-block;margin-right:20px" for="cpad_slide_url_textbox">Slide Custom URL</label>
		<input style="width:70%;display:inline-block" type="text" name="cpad_slide_url_textbox" id="cpad_slide_url_textbox" value="<?php echo $text; ?>" />
	</p>
<?php }

add_action( 'save_post', 'cd_meta_box_save' );
function cd_meta_box_save( $post_id ) {
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'my_meta_box_nonce' ) ) return;
	if( !current_user_can( 'edit_post' ) ) return;
	$allowed = array( 'a' => array('href' => array()));
	
	if(isset( $_POST['cpad_slide_url_textbox'] ))
	update_post_meta( $post_id, 'cpad_slide_url_textbox', wp_kses( $_POST['cpad_slide_url_textbox'], $allowed ) );
}

////////////////////////////////////////

function render_Script(){	
	$render = "<script type='text/javascript'>
		$(document).ready(function(){
			$(\"#cpad_slider\").owlCarousel({
				slideSpeed:500,
				paginationSpeed:5000,
				singleItem:true,
				autoPlay:true,
				stopOnHover:true,
				navigation:false,
				navigationText:[\"&lsaquo;\",\"&rsaquo;\"],
				autoHeight:true,
				transitionStyle:\"fade\"
			});
		});
		</script>";
	echo $render;
}

// add actions
add_action('init', 'cpad_init');
add_action('wp_print_scripts', 'cpad_register_scripts');
add_action('wp_print_styles', 'cpad_register_styles');
add_action('wp_footer', 'render_Script');
 ?>