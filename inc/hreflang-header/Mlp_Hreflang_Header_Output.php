<?php

/**
 * Send headers for alternative language representations.
 *
 * @link    https://support.google.com/webmasters/answer/189077?hl=en
 * @version 2015.06.26
 * @author  Inpsyde GmbH, toscho
 * @license GPL
 */
class Mlp_Hreflang_Header_Output {

	/**
	 * @var Mlp_Language_Api_Interface
	 */
	private $language_api;

	/**
	 * @var array
	 */
	private $translations = array();

	/**
	 * Constructor. Set up properties.
	 *
	 * @param Mlp_Language_Api_Interface $language_api Language API object.
	 */
	public function __construct( Mlp_Language_Api_Interface $language_api ) {

		$this->language_api = $language_api;
	}

	/**
	 * Print language attributes into the HTML head.
	 *
	 * @wp-hook wp_head
	 *
	 * @return void
	 */
	public function wp_head() {

		$translations = $this->get_translations();
		if ( empty( $translations ) ) {
			return;
		}

		foreach ( $translations as $lang => $url ) {
			$html = sprintf(
				'<link rel="alternate" hreflang="%1$s" href="%2$s" />',
				$lang,
				$url
			);

			/**
			 * Filter the output of the hreflang links in the HTML head.
			 *
			 * @param string $html Markup generated by MultilingualPress.
			 * @param string $lang Language code (e.g., 'en-US').
			 * @param string $url  Target URL.
			 */
			echo apply_filters( 'mlp_hreflang_html', $html, $lang, $url );
		}
	}

	/**
	 * Add language attributes to the HTTP header.
	 *
	 * @wp-hook template_redirect
	 *
	 * @return void
	 */
	public function http_header() {

		$translations = $this->get_translations();
		if ( empty( $translations ) ) {
			return;
		}

		foreach ( $translations as $lang => $url ) {
			$header = sprintf(
				'Link: <%1$s>; rel="alternate"; hreflang="%2$s"',
				$url,
				$lang
			);

			/**
			 * Filter the output of the hreflang links in the HTTP header.
			 *
			 * @param string $header Header generated by MultilingualPress.
			 * @param string $lang   Language code (e.g., 'en-US').
			 * @param string $url    Target URL.
			 */
			$header = apply_filters( 'mlp_hreflang_http_header', $header, $lang, $url );
			if ( $header ) {
				header( $header, FALSE );
			}
		}
	}

	/**
	 * Query the language API for translations and cache the result.
	 *
	 * @return array
	 */
	private function get_translations() {

		if ( array( 'failed' ) === $this->translations ) {
			return array();
		}

		/** @var Mlp_Translation_Interface[] $translations */
		$translations = $this->language_api->get_translations( array( 'include_base' => TRUE ) );
		if ( empty( $translations ) ) {
			$this->translations = array( 'failed' );

			return array();
		}

		$prepared = array();

		foreach ( $translations as $translation ) {
			$language = $translation->get_language();

			$language_name = $language->get_name( 'http' );

			$url = $translation->get_remote_url();
			if ( $url ) {
				$prepared[ $language_name ] = $url;
			}
		}

		if ( empty( $prepared ) ) {
			$this->translations = array( 'failed' );

			return array();
		}

		$this->translations = $prepared;

		return $this->translations;
	}

}
