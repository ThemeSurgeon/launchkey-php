<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if (!class_exists('WP_Http')) {
	interface WP_Http {
		/**
		 * @param string       $url  The request URL.
		 * @param string|array $args {
		 *     Optional. Array or string of HTTP request arguments.
		 *
		 *     @type string       $method              Request method. Accepts 'GET', 'POST', 'HEAD', or 'PUT'.
		 *                                             Some transports technically allow others, but should not be
		 *                                             assumed. Default 'GET'.
		 *     @type int          $timeout             How long the connection should stay open in seconds. Default 5.
		 *     @type int          $redirection         Number of allowed redirects. Not supported by all transports
		 *                                             Default 5.
		 *     @type string       $httpversion         Version of the HTTP protocol to use. Accepts '1.0' and '1.1'.
		 *                                             Default '1.0'.
		 *     @type string       $user-agent          User-agent value sent.
		 *                                             Default WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ).
		 *     @type bool         $reject_unsafe_urls  Whether to pass URLs through {@see wp_http_validate_url()}.
		 *                                             Default false.
		 *     @type bool         $blocking            Whether the calling code requires the result of the request.
		 *                                             If set to false, the request will be sent to the remote server,
		 *                                             and processing returned to the calling code immediately, the caller
		 *                                             will know if the request succeeded or failed, but will not receive
		 *                                             any response from the remote server. Default true.
		 *     @type string|array $headers             Array or string of headers to send with the request.
		 *                                             Default empty array.
		 *     @type array        $cookies             List of cookies to send with the request. Default empty array.
		 *     @type string|array $body                Body to send with the request. Default null.
		 *     @type bool         $compress            Whether to compress the $body when sending the request.
		 *                                             Default false.
		 *     @type bool         $decompress          Whether to decompress a compressed response. If set to false and
		 *                                             compressed content is returned in the response anyway, it will
		 *                                             need to be separately decompressed. Default true.
		 *     @type bool         $sslverify           Whether to verify SSL for the request. Default true.
		 *     @type string       sslcertificates      Absolute path to an SSL certificate .crt file.
		 *                                             Default ABSPATH . WPINC . '/certificates/ca-bundle.crt'.
		 *     @type bool         $stream              Whether to stream to a file. If set to true and no filename was
		 *                                             given, it will be droped it in the WP temp dir and its name will
		 *                                             be set using the basename of the URL. Default false.
		 *     @type string       $filename            Filename of the file to write to when streaming. $stream must be
		 *                                             set to true. Default null.
		 *     @type int          $limit_response_size Size in bytes to limit the response to. Default null.
		 *
		 * }
		 * @return array|WP_Error Array containing 'headers', 'body', 'response', 'cookies', 'filename'.
		 *                        A WP_Error instance upon error.
		 */
        function request($url, $args = array());
	}
}

if (!class_exists('WP_Error')) {
    interface WP_Error {

        /**
         * Retrieve all error codes.
         *
         * @since 2.1.0
         * @access public
         *
         * @return array List of error codes, if available.
         */
        function get_error_codes();


        /**
         * Retrieve first error code available.
         *
         * @since 2.1.0
         * @access public
         *
         * @return string|int Empty string, if no error codes.
         */
        public function get_error_code();

        /**
         * Retrieve all error messages or error messages matching code.
         *
         * @since 2.1.0
         *
         * @param string|int $code Optional. Retrieve messages matching code, if exists.
         * @return array Error strings on success, or empty array on failure (if using code parameter).
         */
        public function get_error_messages($code = '');

        /**
         * Get single error message.
         *
         * This will get the first message available for the code. If no code is
         * given then the first code available will be used.
         *
         * @since 2.1.0
         *
         * @param string|int $code Optional. Error code to retrieve message.
         * @return string
         */
        public function get_error_message($code = '');

        /**
         * Retrieve error data for error code.
         *
         * @since 2.1.0
         *
         * @param string|int $code Optional. Error code.
         * @return mixed Null, if no errors.
         */
        public function get_error_data($code = '');

        /**
         * Add an error or append additional message to an existing error.
         *
         * @since 2.1.0
         * @access public
         *
         * @param string|int $code Error code.
         * @param string $message Error message.
         * @param mixed $data Optional. Error data.
         */
        public function add($code, $message, $data = '');

        /**
         * Add data for error code.
         *
         * The error code can only contain one error data.
         *
         * @since 2.1.0
         *
         * @param mixed $data Error data.
         * @param string|int $code Error code.
         */
        public function add_data($data, $code = '');

        /**
         * Removes the specified error.
         *
         * This function removes all error messages associated with the specified
         * error code, along with any error data for that code.
         *
         * @since 4.1.0
         *
         * @param string|int $code Error code.
         */
        public function remove( $code );
    }
}