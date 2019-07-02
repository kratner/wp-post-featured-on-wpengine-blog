<?php
/**
 * Plugin Name: WordPress Post Featured on WP Engine's Blog
 * Plugin URI: https://github.com/kratner/wp-post-featured-on-wpengine-blog
 * Description: Mark post as featured on WP Engine's blog.
 * Version: 1.0
 * Author: Keith Ratner
 * Author URI: http://keithratner.live
 */

 /**
 * Adds a meta box to the post editing screen
 */
function krwpengineoptions_custom_meta() {
    add_meta_box( 'krwpengineoptions_meta', __('WP Engine Options', 'krwpengineoptions-textdomain' ), 'krwpengineoptions_meta_callback', 'post' );
}
add_action( 'add_meta_boxes', 'krwpengineoptions_custom_meta' );
function krwpengineoptions_meta_callback( $post ) {
    wp_nonce_field( basename( __FILE__ ), 'krwpengineoptions_nonce' );
    $krwpengineoptions_stored_meta = get_post_meta( $post->ID );
    ?>
        <label class="selectit">
    
            <input type="checkbox" name="krwpengineoptions-featuredonwpengineblog" id="krwpengineoptions-featuredonwpengineblog" value="yes" <?php if ( isset ( $krwpengineoptions_stored_meta['krwpengineoptions-featuredonwpengineblog'] ) ) checked( $krwpengineoptions_stored_meta['krwpengineoptions-featuredonwpengineblog'][0], 'yes' ); ?> />
        Featured on WPEngine's Blog
        </label>
    <?php
}
function krwpengineoptions_meta_save( $post_id ) {
 
    // Checks save status
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST[ 'krwpengineoptions_nonce' ] ) && wp_verify_nonce( $_POST[ 'krwpengineoptions_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
        return;
    }
    if( isset( $_POST[ 'meta-text' ] ) ) {
        update_post_meta( $post_id, 'meta-text', sanitize_text_field( $_POST[ 'meta-text' ] ) );
    }

    if( isset( $_POST[ 'krwpengineoptions-featuredonwpengineblog' ] ) ) {
        update_post_meta( $post_id, 'krwpengineoptions-featuredonwpengineblog', 'yes' );
    } else {
        update_post_meta( $post_id, 'krwpengineoptions-featuredonwpengineblog', '' );
    } 
}
add_action( 'save_post', 'krwpengineoptions_meta_save' );
add_action( 'rest_api_init', 'add_custom_fields' );
function add_custom_fields() {
    register_rest_field(
        'post', 
        'krwpengineoptions-featuredonwpengineblog',
        array(
            'get_callback'    => 'get_custom_fields',
            'update_callback' => null,
            'schema'          => null,
            )
    );
}
function get_custom_fields( $object, $field_name, $request ) {
    $metavalue = get_post_meta($object['id'], $field_name, true);
    return $metavalue;
}

function krwpengineoptions_wpebfeaturedposts_cb() {
    $args = Array(
        'post_type' => 'post',
        'posts_per_page' => '5',
        'meta_key' => 'krwpengineoptions-featuredonwpengineblog',
        'meta_value' => 'yes',
        'meta_compare' => '=',
        'orderby' => 'date',
        'order' => 'DESC'
      );
    $query = new WP_Query($args);
    $posts = $query->get_posts();

    $controller = new WP_REST_Posts_Controller('post');

    $array = [];

    foreach ( $posts as $post ) {
        $data = $controller->prepare_item_for_response($post,$request);
        $array[] = $controller->prepare_response_for_collection($data);
    }

    return $array;
}
add_action( 'rest_api_init', function () {
    register_rest_route( 'wpeb/v1', '/last5featured/', array(
            'methods' => 'GET',
            'callback' => 'krwpengineoptions_wpebfeaturedposts_cb'
    ) );
} );