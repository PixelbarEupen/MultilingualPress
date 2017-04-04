<?php # -*- coding: utf-8 -*-

declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\Common\HTTP;

/**
 * @package Inpsyde\MultilingualPress\Common\HTTP
 * @since   3.0.0
 */
final class PHPServerRequest implements ServerRequest {

	/**
	 * @var array
	 */
	private static $values = [];

	/**
	 * @var array
	 */
	private static $headers = [];

	/**
	 * @var array
	 */
	private static $server = [];

	/**
	 * @var HeaderParser
	 */
	private $default_header_parser;

	/**
	 * Constructor. Sets properties.
	 *
	 * @param HeaderParser|null $default_header_parser
	 */
	public function __construct( HeaderParser $default_header_parser = null ) {

		$this->default_header_parser = $default_header_parser;
	}

	/**
	 * Return a value from request body, optionally filtered.
	 *
	 * @param string $name    Key to get value for.
	 * @param int    $method  HTTP method constants, can be one of INPUT_REQUEST, INPUT_GET or INPUT_POST
	 * @param int    $filter  Optional. One of the FILTER_* constants. Defaults to FILTER_UNSAFE_RAW (value unchanged).
	 * @param null   $options Optional. Options for filter. Defaults to null.
	 *
	 * @return mixed
	 */
	public function body_value(
		string $name,
		int $method = INPUT_REQUEST,
		int $filter = FILTER_UNSAFE_RAW,
		$options = null
	) {

		$this->ensure_values();

		if ( ! array_key_exists( $method, self::$values ) || ! array_key_exists( $name, self::$values[ $method ] ) ) {
			return null;
		}

		return filter_var( self::$values[ $method ][ $name ], $filter, $options );
	}

	/**
	 * Returns a request header.
	 *
	 * @param string $name
	 *
	 * @return string Header value, empty string if the header is not set.
	 */
	public function header( string $name ): string {

		$this->ensure_headers();

		return array_key_exists( $name, self::$headers ) ? self::$headers[ $name ] : '';
	}

	/**
	 * Returns a parsed header value.
	 *
	 * @param string       $name   Header name.
	 * @param HeaderParser $parser Parser to use. If not provided, the default parser will be used. When neither default
	 *                             parser was passed to constructor, `TrimmingHeaderParser` is instantiated, used and
	 *                             stored as default parser for subsequent calls.
	 *
	 * @return array
	 */
	public function parsed_header( string $name, HeaderParser $parser = null ): array {

		$this->ensure_headers();

		$header = $this->header( $name );

		if ( $parser ) {
			return $parser->parse( $header );
		}

		if ( ! $this->default_header_parser ) {
			$this->default_header_parser = new TrimmingHeaderParser();
		}

		return $this->default_header_parser->parse( $header );
	}

	/**
	 * Returns a server value.
	 *
	 * @param string $name
	 *
	 * @return string Server setting value, empty string if not set.
	 */
	public function server_value( string $name ): string {

		$this->ensure_server();

		$name = strtoupper( $name );

		return array_key_exists( $name, self::$values ) ? (string) self::$values[ $name ] : '';
	}

	/**
	 * Ensure values from request are available in object property.
	 */
	private function ensure_values() {

		if ( null !== self::$values ) {
			return;
		}

		$url_query_data = (array) filter_input_array( INPUT_GET, FILTER_DEFAULT, false );

		self::$values[ INPUT_GET ] = $url_query_data;

		$method = $_SERVER['REQUEST_METHOD'] ?? '';

		// For POST requests merge URL query data with form data. This will also work with multipart forms.
		if ( $method && strtoupper( $method ) === 'POST' ) {

			self::$values[ INPUT_POST ] = (array) filter_input_array( INPUT_POST, FILTER_DEFAULT, false );

			self::$values[ INPUT_REQUEST ] = array_merge( self::$values[ INPUT_GET ], self::$values[ INPUT_POST ] );

			return;
		}

		$content_type = $_SERVER['CONTENT_TYPE'] ?? '';

		// For GET requests or when content type is not URL-encoded, just return URL query data.
		if ( $method === 'GET' || $content_type !== 'application/x-www-form-urlencoded' ) {
			self::$values[ INPUT_REQUEST ] = $url_query_data;

			return;
		}

		// When not GET nor POST method is used, but content is URL-encoded, we can safely decode raw body stream.
		@parse_str( stream_get_contents( fopen( 'php://input', 'r' ) ), $values );

		self::$values[ INPUT_REQUEST ] = is_array( $values )
			? array_merge( $url_query_data, $values )
			: $url_query_data;
	}

	/**
	 * Ensure headers from request are available in object property.
	 */
	private function ensure_headers() {

		if ( null !== self::$headers ) {
			return;
		}

		$this->ensure_server();

		$headers = [];
		foreach ( self::$server as $key => $value ) {
			// Apache prefixes environment variables with REDIRECT_ if they are added by rewrite rules
			if ( strpos( $key, 'REDIRECT_' ) === 0 ) {
				$key = substr( $key, 9 );
				// We will not overwrite existing variables with the prefixed versions, though
				if ( array_key_exists( $key, self::$server ) ) {
					continue;
				}
			}

			if ( $value && strpos( $key, 'HTTP_' ) === 0 ) {
				$headers[ strtr( strtolower( substr( $key, 5 ) ), '_', '-' ) ] = $value;
				continue;
			}

			if ( $value && strpos( $key, 'CONTENT_' ) === 0 ) {
				$headers[ 'content-' . strtolower( substr( $key, 8 ) ) ] = $value;
				continue;
			}
		}

		self::$headers = $headers;
	}

	/**
	 * Ensure server values from request are available in object property.
	 */
	private function ensure_server() {

		if ( null !== self::$server ) {
			return;
		}

		self::$server = array_change_key_case( $_SERVER, CASE_UPPER );

		if ( array_key_exists( 'HTTP_AUTHORIZATION', $_SERVER ) ) {
			return;
		}

		// This seems to be the only way to get the Authorization header on Apache
		$apache_request_headers = apache_request_headers();
		if ( ! $apache_request_headers ) {
			return;
		}

		$apache_request_headers = array_change_key_case( apache_request_headers(), CASE_LOWER );

		if ( array_key_exists( 'authorization', $apache_request_headers ) ) {
			self::$server['HTTP_AUTHORIZATION'] = $apache_request_headers['authorization'];

			return;
		}
	}
}
