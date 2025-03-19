<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function sanitize_event_data( $data ) {
    return [
        'title' => sanitize_text_field( $data['title'] ?? '' ),
        'description' => sanitize_textarea_field( $data['description'] ?? '' ),
        'start_date' => sanitize_text_field( $data['start_date'] ?? '' ),
        'end_date' => sanitize_text_field( $data['end_date'] ?? '' ),
        'event_categories' =>  $data['event_categories'],
        'status' => sanitize_text_field( $data['status'] ?? '' ),
    ];
}
