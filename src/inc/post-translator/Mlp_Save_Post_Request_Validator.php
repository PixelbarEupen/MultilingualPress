<?php

use Inpsyde\MultilingualPress\Common\Nonce\Nonce;
use Inpsyde\MultilingualPress\Common\RequestValidator;

/**
 * Request validation for the action 'save_post'.
 *
 * @author  toscho
 * @version 2014.03.09
 * @license MIT
 */
class Mlp_Save_Post_Request_Validator implements RequestValidator {

	/**
	 * @var Nonce
	 */
	private $nonce;

	/**
	 * Constructor.
	 *
	 * @param Nonce $nonce Nonce object.
	 */
	public function __construct( Nonce $nonce ) {

		$this->nonce = $nonce;
	}

	/**
	 * Is this a valid request?
	 *
	 * @param  int $context Post id
	 * @return bool
	 */
	public function is_valid( $context = null ): bool
	{
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return FALSE;

		if ( $this->is_real_revision( $context ) )
			return FALSE;

		if ( ! current_user_can( 'edit_post', $context ) )
			return FALSE;

		return $this->nonce->is_valid();
	}

	/**
	 * Check post status.
	 *
	 * Includes special hacks for auto-drafts.
	 *
	 * @param  int $post_id
	 * @return bool
	 */
	private function is_real_revision( $post_id ) {

		if ( ! wp_is_post_revision( $post_id ) )
			FALSE;

		$post = get_post( $post_id );

		if ( in_array( $post->post_status, [ 'publish', 'draft', 'private', 'auto-draft' ], true ) ) {
			return false;
		}

		/* Auto-drafts are sent as revision with a status 'inherit'.
		 * We have to inspect the $_POST array to distinguish them from real
		 * revisions and attachments (which have the same status)
		 */

		if ( 'inherit' !== $post->post_status )
			return FALSE;

		if ( 'revision' !== $post->post_type )
			return FALSE;

		return 'auto-draft' !== filter_input( INPUT_POST, 'original_post_status' );
	}
}
