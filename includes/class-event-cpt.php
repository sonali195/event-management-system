<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Event_CPT {
    public static function register() {
        // Register the custom post type.
        register_post_type( 'event', [
            'label' => 'Events',
            'public' => true,
            'has_archive' => true,
            'supports' => [ 'title', 'editor' ],
            'show_in_rest' => true, // Enables REST API support.
            'labels' => [
                'name' => 'Events',
                'singular_name' => 'Event',
                'add_new' => 'Add New Event',
                'add_new_item' => 'Add New Event',
                'edit_item' => 'Edit Event',
                'new_item' => 'New Event',
                'view_item' => 'View Event',
                'search_items' => 'Search Events',
            ],
        ] );

        // Register the custom taxonomy for Event categories.
        register_taxonomy( 'event_category', 'event', [
            'hierarchical' => true,
            'labels' => [
                'name' => 'Event Categories',
                'singular_name' => 'Event Category',
                'search_items' => 'Search Event Categories',
                'all_items' => 'All Event Categories',
                'edit_item' => 'Edit Event Category',
                'update_item' => 'Update Event Category',
                'add_new_item' => 'Add New Event Category',
                'new_item_name' => 'New Event Category Name',
                'menu_name' => 'Event Categories',
            ],
            'show_ui' => true,
            'show_in_rest' => true, // Enables REST API support.
            'show_admin_column' => true,
            'rewrite' => [ 'slug' => 'event-category' ],
        ] );
    }
}
