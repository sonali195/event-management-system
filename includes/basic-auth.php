<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class JSON_Basic_Auth {
    private static $instance;
    private $auth_error = null;

    /**
     * Singleton instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Hook into WordPress filters.
     */
    public function init() {
        add_filter( 'determine_current_user', [ $this, 'handle_basic_auth' ], 20 );
        add_filter( 'rest_authentication_errors', [ $this, 'handle_auth_errors' ] );
    }

    /**
     * Handle Basic Authentication.
     *
     * @param mixed $user
     * @return mixed
     */
    public function handle_basic_auth( $user ) {
        // Don't authenticate twice.
        if ( ! empty( $user ) ) {
            return $user;
        }

        // Check that we're trying to authenticate.
        if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) ) {
            return $user;
        }

        $username = sanitize_text_field( $_SERVER['PHP_AUTH_USER'] );
        $password = sanitize_text_field( $_SERVER['PHP_AUTH_PW'] );

        /**
         * Prevent infinite recursion in multi-site by temporarily removing the filter.
         */
        remove_filter( 'determine_current_user', [ $this, 'handle_basic_auth' ], 20 );

        $user = wp_authenticate( $username, $password );

        add_filter( 'determine_current_user', [ $this, 'handle_basic_auth' ], 20 );

        if ( is_wp_error( $user ) ) {
            $this->auth_error = $user;
            return null;
        }

        $this->auth_error = true;

        return $user->ID;
    }

    /**
     * Handle authentication errors.
     *
     * @param mixed $error
     * @return mixed
     */
    public function handle_auth_errors( $error ) {
        // Passthrough other errors.
        if ( ! empty( $error ) ) {
            return $error;
        }

        return $this->auth_error;
    }
}

// Initialize the class and hook into WordPress.
add_action( 'init', function () {
    $auth = JSON_Basic_Auth::get_instance();
    $auth->init();
} );
