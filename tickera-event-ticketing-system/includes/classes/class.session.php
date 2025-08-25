<?php
/**
 * Class TC_Session
 * Manages the session data storage functionalities for the application, including initialization,
 * data retrieval, data insertion, and session handling operations.
 */

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( '\Tickera\TC_Session' ) ) {

    class TC_Session {

        /**
         * Session data storage
         * @var array
         */
        protected $_data;

        /**
         * Constructor method that initializes the class by calling the static init method.
         * @return void
         */
        function __construct() {
            self::init();
        }

        /**
         * Initializes the internal data array by starting the session, sanitizing the session data, and closing the session.
         * @return void
         */
        function init() {
            self::start();

            if ( isset( $_SESSION ) && $_SESSION && is_array( $_SESSION ) ) {
                 $this->_data = tickera_sanitize_array( $_SESSION, false, true );

            } else {
                $this->_data = [];
            }

            self::close();
        }

        /**
         * Retrieves a value from the internal data array based on the provided key.
         * @param string $key Optional. The key to retrieve a specific value. Defaults to an empty string.
         * @return mixed The value associated with the specified key, or the entire data array if no key is provided. Returns null if the key does not exist.
         */
        function get( $key = '' ) {

            if ( ! $key ) {
                return $this->_data;
            }

            $key = sanitize_key( $key );
            return isset( $this->_data[ $key ] ) ? $this->_data[ $key ] : null;
        }

        /**
         * Sets a value in the internal data storage, optionally sanitizing the input.
         * If a key is provided, the value is saved under the given key. If no key is provided,
         * the entire data storage is replaced with the provided value.
         *
         * @param string|false $key The key under which the value should be stored. If false, the data storage is replaced.
         * @param mixed $value The value to be stored. It can be an array or a scalar value.
         * @param bool $allow_html Whether HTML content is allowed in the value. If true, the content is sanitized for allowed HTML.
         *                         If false, the value is sanitized as plain text.
         * @return void
         */
        function set( $key = false, $value = '', $allow_html = false ) {

            $value = is_array( $value ) ? tickera_sanitize_array( $value, $allow_html, true ) : ( $allow_html ? wp_kses_post( $value ) : sanitize_text_field( $value ) );

            if ( $key ) {
                $key = sanitize_key( $key );
                $this->_data[ $key ] = $value;

            } else {
                $this->_data = $value;
            }

            self::save();
        }

        /**
         * Removes the specified key from the internal data array and triggers saving of the updated data.
         * @param string $key The key to be removed from the internal data array.
         * @return void
         */
        function drop( $key ) {
            if ( $key && isset( $this->_data[ $key ] ) ) {
                unset( $this->_data[ $key ] );
                self::save();
            }
        }

        /**
         * Saves the current session data to the session storage.
         * @return void
         */
        private function save() {
            self::start();
            $_SESSION = $this->_data;
            self::close();
        }

        /**
         * Starts a PHP session if one is not already active and headers have not been sent.
         * @return void
         */
        function start() {
            if ( ! session_id() || ( session_status() == PHP_SESSION_NONE && ! headers_sent() ) ) {
                do_action( 'tc_before_session_start' );
                @session_start();
                do_action( 'tc_after_session_started' );
            }
        }

        /**
         * Closes the current session if a session is active.
         * @return void
         */
        function close() {
            if ( session_id() || session_status() == PHP_SESSION_ACTIVE ) {
                session_write_close();
            }
        }

        /**
         * Check if the current user is an administrator.
         * @return bool True if the current user has administrative privileges, false otherwise.
         */
        function is_admin() {
            return ( function_exists( 'wp_get_current_user' ) && current_user_can( 'manage_options' ) ) ? true : false;
        }
    }
}
