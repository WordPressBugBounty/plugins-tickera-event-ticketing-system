<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Session' ) ) {

    class TC_Session {

        /**
         * Session data storage
         * @var array
         */
        protected $_data;

        function __construct() {
            self::init();
        }

        /**
         * Start Session.
         * Suppress error to avoid process interruption
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

        function get( $key = '' ) {

            if ( ! $key ) {
                return $this->_data;
            }

            $key = sanitize_key( $key );
            return isset( $this->_data[ $key ] ) ? $this->_data[ $key ] : null;
        }

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

        function drop( $key ) {
            if ( $key && isset( $this->_data[ $key ] ) ) {
                unset( $this->_data[ $key ] );
                self::save();
            }
        }

        private function save() {
            self::start();
            $_SESSION = $this->_data;
            self::close();
        }

        function start() {
            if ( ! session_id() || ( session_status() == PHP_SESSION_NONE && ! headers_sent() ) ) {
                do_action( 'tc_before_session_start' );
                @session_start();
                do_action( 'tc_after_session_started' );
            }
        }

        function close() {
            if ( session_id() || session_status() == PHP_SESSION_ACTIVE ) {
                session_write_close();
            }
        }

        function is_admin() {
            return ( function_exists( 'wp_get_current_user' ) && current_user_can( 'manage_options' ) ) ? true : false;
        }
    }
}
