<?php
/*
Plugin Name: Simple Alert Box 
Description: A simple reusable modal plugin that grabs content from a custom post type and uses a shortcode to add the content to any page or post in a popup box that works on a link.
Version: 1.0
Plugin URI: https://www.9realmsmedia.com/wp-alert-box
License: GPLv2 or later
Author URI: https://www.9realmsmedia.com
Author: J.V Krakowski at 9 Realms Media
text-domain: nrm-alert-box
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define minimum WordPress version required
define('ALERT_BOX_MIN_WP_VERSION', '5.0');

// Compatibility check
function alert_box_check_compatibility() {
    global $wp_version;

    if (version_compare($wp_version, ALERT_BOX_MIN_WP_VERSION, '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(esc_html__('This plugin requires WordPress version ', 'nrm-alert-box') . esc_html(ALERT_BOX_MIN_WP_VERSION) . esc_html__(' or higher.', 'nrm-alert-box'));
    }
}
add_action('admin_init', 'alert_box_check_compatibility');

// Install hook
function alert_box_install() {
    // Set default options
    if (get_option('alert_box_default_options') === false) {
        $default_options = array(
            'bg_color' => '#f8d7da',
        );
        add_option('alert_box_default_options', $default_options);
    }
}
register_activation_hook(__FILE__, 'alert_box_install');

// Register Custom Post Type
function create_alert_cpt() {
    $labels = array(
        'name' => _x('Alerts', 'Post Type General Name', 'nrm-alert-box'),
        'singular_name' => _x('Alert', 'Post Type Singular Name', 'nrm-alert-box'),
        'menu_name' => __('Alerts', 'nrm-alert-box'),
        'name_admin_bar' => __('Alert', 'nrm-alert-box'),
        'archives' => __('Alert Archives', 'nrm-alert-box'),
        'attributes' => __('Alert Attributes', 'nrm-alert-box'),
        'parent_item_colon' => __('Parent Alert:', 'nrm-alert-box'),
        'all_items' => __('All Alerts', 'nrm-alert-box'),
        'add_new_item' => __('Add New Alert', 'nrm-alert-box'),
        'add_new' => __('Add New', 'nrm-alert-box'),
        'new_item' => __('New Alert', 'nrm-alert-box'),
        'edit_item' => __('Edit Alert', 'nrm-alert-box'),
        'update_item' => __('Update Alert', 'nrm-alert-box'),
        'view_item' => __('View Alert', 'nrm-alert-box'),
        'view_items' => __('View Alerts', 'nrm-alert-box'),
        'search_items' => __('Search Alert', 'nrm-alert-box'),
        'not_found' => __('Not found', 'nrm-alert-box'),
        'not_found_in_trash' => __('Not found in Trash', 'nrm-alert-box'),
        'featured_image' => __('Featured Image', 'nrm-alert-box'),
        'set_featured_image' => __('Set featured image', 'nrm-alert-box'),
        'remove_featured_image' => __('Remove featured image', 'nrm-alert-box'),
        'use_featured_image' => __('Use as featured image', 'nrm-alert-box'),
        'insert_into_item' => __('Insert into alert', 'nrm-alert-box'),
        'uploaded_to_this_item' => __('Uploaded to this alert', 'nrm-alert-box'),
        'items_list' => __('Alerts list', 'nrm-alert-box'),
        'items_list_navigation' => __('Alerts list navigation', 'nrm-alert-box'),
        'filter_items_list' => __('Filter alerts list', 'nrm-alert-box'),
    );
    $args = array(
        'label' => __('Alert', 'nrm-alert-box'),
        'description' => __('Alert custom post type', 'nrm-alert-box'),
        'labels' => $labels,
        'supports' => array('title', 'editor', 'excerpt', 'custom-fields',),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'has_archive' => true,
        'exclude_from_search' => false,
        'publicly_queryable' => true,
        'capability_type' => 'post',
    );
    register_post_type('alert', $args);
}
add_action('init', 'create_alert_cpt', 0);

// Enqueue Scripts and Styles
function alert_box_enqueue_scripts() {
    wp_enqueue_style('alert-box-style', plugin_dir_url(__FILE__) . 'css/style.css', array(), '1.0');
    wp_enqueue_script('alert-box-script', plugin_dir_url(__FILE__) . 'js/alert-box.js', array('jquery'), '1.0', true);
    wp_localize_script('alert-box-script', 'alertBox', array(
        'ajax_url' => esc_url(admin_url('admin-ajax.php')),
        'nonce' => wp_create_nonce('alert_box_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'alert_box_enqueue_scripts');

// Shortcode to display alert boxes
function alert_box_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'bg_color' => '#f8d7da',
            'ids' => '',
            'link_text' => __('Show Alert', 'nrm-alert-box'),
        ),
        $atts,
        'alert_box'
    );

    // Sanitize and validate attributes
    $bg_color = sanitize_hex_color($atts['bg_color']);
    $ids = array_map('absint', explode(',', $atts['ids']));
    $link_text = sanitize_text_field($atts['link_text']);

    $args = array(
        'post_type' => 'alert',
        'posts_per_page' => -1,
        'post__in' => $ids,
    );
    $alerts = new WP_Query($args);

    $output = '<div class="alert-wrapper">';
    while ($alerts->have_posts()) {
        $alerts->the_post();
        $alert_id = get_the_ID();
        $alert_content = apply_filters('the_content', get_the_content());
        $output .= '<div class="alert" data-alert-id="' . esc_attr($alert_id) . '" style="background-color: ' . esc_attr($bg_color) . ';">';
        $output .= '<p>' . wp_kses_post($alert_content) . '</p>';
        $output .= '<span class="close">X</span>';
        $output .= '</div>';
        $output .= '<a class="show-alert" href="#" data-show-alert="' . esc_attr($alert_id) . '">' . esc_html($link_text) . '</a>';
    }
    $output .= '</div>';

    wp_reset_postdata();

    return $output;
}
add_shortcode('alert_box', 'alert_box_shortcode');

// Add Post ID to Admin Columns
function add_alert_columns($columns) {
    $columns['alert_id'] = __('Alert ID', 'nrm-alert-box');
    return $columns;
}
add_filter('manage_alert_posts_columns', 'add_alert_columns');

function alert_custom_column($column, $post_id) {
    switch ($column) {
        case 'alert_id':
            echo esc_html($post_id);
            break;
    }
}
add_action('manage_alert_posts_custom_column', 'alert_custom_column', 10, 2);

// Uninstall hook
function alert_box_uninstall() {
    // Delete all custom post type posts
    $alerts = get_posts(array(
        'post_type' => 'alert',
        'numberposts' => -1,
        'post_status' => 'any'
    ));

    foreach ($alerts as $alert) {
        wp_delete_post($alert->ID, true);
    }

    // Delete plugin options
    delete_option('alert_box_default_options');
}
register_uninstall_hook(__FILE__, 'alert_box_uninstall');
?>