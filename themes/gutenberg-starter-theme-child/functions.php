<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('gravity-styles', get_stylesheet_uri(), array(), '1.1.5');
    wp_enqueue_script('gravity-scripts', get_stylesheet_directory_uri() . '/assets/js/scripts.js', array('jquery'), '1.2.2', true);
}, 4);

function isProduction() {
    return strpos(home_url(), 'gravityglobal.com') !== false;
}