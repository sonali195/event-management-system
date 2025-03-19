<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Rest_API {
    public static function register_routes() {
        register_rest_route( 'v2/events', '/create', [
            'methods' => 'POST',
            'callback' => [ __CLASS__, 'create_event' ],
            'permission_callback' => [ __CLASS__, 'is_admin_user' ],
        ] );

        register_rest_route( 'v2/events', '/update/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [ __CLASS__, 'update_event' ],
            'permission_callback' => [ __CLASS__, 'is_admin_user' ],
        ] );

        register_rest_route( 'v2/events', '/list', [
            'methods' => 'GET',
            'callback' => [ __CLASS__, 'list_events' ],
            'permission_callback' => [ __CLASS__, 'is_admin_user' ],
        ] );

        register_rest_route( 'v2/events', '/show/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [ __CLASS__, 'show_event' ],
            'permission_callback' => [ __CLASS__, 'is_admin_user' ],
        ] );

        register_rest_route( 'v2/events', '/delete/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [ __CLASS__, 'delete_event' ],
            'permission_callback' => [ __CLASS__, 'is_admin_user' ],
        ] );
    }

    public static function create_event( $request ) {
        $data = sanitize_event_data( $request->get_json_params() );
        
        $post_id = wp_insert_post( [
            'post_type'    => 'event',
            'post_title'   => $data['title'],
            'post_content' => $data['description'],
            'post_status'  => $data['status'],
        ] );
    
        if ( is_wp_error( $post_id ) ) {
            return rest_ensure_response( [ 'error' => 'Unable to create event.' ] );
        }
        
        // Update event categories with validation.
        if ( isset( $data['event_categories'] ) && is_array( $data['event_categories'] ) ) {
            
            // Ensure the categories exist.
            $valid_categories = [];
            foreach ( $data['event_categories'] as $category ) {
                // Check if the category exists by slug or ID
                $term = get_term_by( 'id', $category, 'event_category' ) ?: get_term_by( 'slug', $category, 'event_category' );
                if ( $term ) {
                    $valid_categories[] = $term->term_id;
                }
            }

            // Update the event's categories only if valid categories are found.
            if ( ! empty( $valid_categories ) ) {
                wp_set_post_terms( $post_id, $valid_categories, 'event_category' );
            } else {
                return new WP_Error( 'invalid_categories', 'One or more categories are invalid.', [ 'status' => 400 ] );
            }
        }
    
        update_post_meta( $post_id, 'start_date', $data['start_date'] );
        update_post_meta( $post_id, 'end_date', $data['end_date'] );
    
        return rest_ensure_response( [ 'success' => 'Event created successfully.', 'event_id' => $post_id ] );
    }    

    public static function update_event( $request ) {
        $post_id = (int) $request['id'];
        $data = sanitize_event_data( $request->get_json_params() );
    
        if ( ! get_post( $post_id ) || get_post_type( $post_id ) !== 'event' ) {
            return rest_ensure_response( [ 'error' => 'Event not found.' ] );
        }
    
        wp_update_post( [
            'ID'           => $post_id,
            'post_title'   => $data['title'],
            'post_content' => $data['description'],
        ] );
    
        // Update event categories with validation.
        if ( isset( $data['event_categories'] ) && is_array( $data['event_categories'] ) ) {
            // Ensure the categories exist.
            $valid_categories = [];
            foreach ( $data['event_categories'] as $category ) {
                // Check if the category exists by slug or ID
                $term = get_term_by( 'id', $category, 'event_category' ) ?: get_term_by( 'slug', $category, 'event_category' );
                if ( $term ) {
                    $valid_categories[] = $term->term_id;
                }
            }

            // Update the event's categories only if valid categories are found.
            if ( ! empty( $valid_categories ) ) {
                wp_set_post_terms( $post_id, $valid_categories, 'event_category' );
            } else {
                return new WP_Error( 'invalid_categories', 'One or more categories are invalid.', [ 'status' => 400 ] );
            }
        }
    
        update_post_meta( $post_id, 'start_date', $data['start_date'] );
        update_post_meta( $post_id, 'end_date', $data['end_date'] );
    
        return rest_ensure_response( [ 'success' => 'Event updated successfully.' ] );
    }    

    public static function list_events( $request ) {
        $date = $request->get_param( 'date' );
        $args = [ 'post_type' => 'event', 'post_status' => 'publish', 'numberposts' => -1 ];

        if ( $date ) {
            $args['meta_query'] = [
                [
                    'key' => 'start_date',
                    'value' => $date,
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
            ];
        }

        $events = get_posts( $args );
        $result = [];

        // Get event categories
        // $categories = wp_get_post_terms( $event->ID, 'event_category' );
        // $category_names = wp_list_pluck( $categories, 'name' ); // Get just the category names

        foreach ( $events as $event ) {
            // Get event categories
            $categories = get_the_terms( $event->ID, 'event_category' );
            $category_data = [];

            if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
                foreach ( $categories as $category ) {
                    $category_data[] = [
                        'id' => $category->term_id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ];
                }
            }

            $result[] = [
                'id' => $event->ID,
                'title' => $event->post_title,
                'description' => $event->post_content,
                'start_date' => get_post_meta( $event->ID, 'start_date', true ),
                'end_date' => get_post_meta( $event->ID, 'end_date', true ),
                'category' => $category_data,
            ];
        }

        return rest_ensure_response( $result );
    }

    public static function show_event( $request ) {
        $post_id = (int) $request['id'];

        if ( ! get_post( $post_id ) || get_post_type( $post_id ) !== 'event' ) {
            return rest_ensure_response( [ 'error' => 'Event not found.' ] );
        }
        
        $event = get_post( $post_id );
        // Get event categories
        // $categories = wp_get_post_terms( $event->ID, 'event_category' );
        // $category_names = wp_list_pluck( $categories, 'name' ); // Get just the category names

        // Get event categories
        $categories = get_the_terms( $post_id, 'event_category' );
        $category_data = [];

        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            foreach ( $categories as $category ) {
                $category_data[] = [
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ];
            }
        }

        return rest_ensure_response( [
            'id' => $post_id,
            'title' => $event->post_title,
            'description' => $event->post_content,
            'start_date' => get_post_meta( $post_id, 'start_date', true ),
            'end_date' => get_post_meta( $post_id, 'end_date', true ),
            'category' => $category_data,
        ] );
    }

    public static function delete_event( $request ) {
        $id = (int) $request['id'];

        if ( ! get_post( $id ) || get_post_type( $id ) !== 'event' ) {
            return rest_ensure_response( [ 'error' => 'Event not found.' ] );
        }

        wp_delete_post( $id, true );

        return rest_ensure_response( [ 'success' => 'Event deleted successfully.' ] );
    }

    public static function is_admin_user() {
        return current_user_can( 'administrator' );
    }
}
