<?php
/**
 * Form Handler
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Form_Handler class
 *
 * Handles form submissions for tabs mode.
 *
 * @since 1.2.0
 */
class Form_Handler {

	/**
	 * Manager instance
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Constructor
	 *
	 * @param Manager $manager Manager instance.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action(
			'admin_post_codesoup_options_save',
			array(
				$this,
				'handle_save',
			)
		);
	}

	/**
	 * Handle form submission
	 *
	 * @return void
	 */
	public function handle_save(): void {
		$post_id      = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0;
		$instance_key = isset( $_POST['instance_key'] ) ? sanitize_key( $_POST['instance_key'] ) : '';
		$page_id      = isset( $_POST['page_id'] ) ? sanitize_key( $_POST['page_id'] ) : '';

		if ( ! $post_id || ! $instance_key || ! $page_id ) {
			wp_die(
				esc_html__( 'Invalid form submission.', 'codesoup-options' ),
				esc_html__( 'Error', 'codesoup-options' ),
				array( 'response' => 400 )
			);
		}

		if ( $instance_key !== $this->manager->get_instance_key() ) {
			wp_die(
				esc_html__( 'Invalid instance key.', 'codesoup-options' ),
				esc_html__( 'Error', 'codesoup-options' ),
				array( 'response' => 400 )
			);
		}

		if ( ! check_admin_referer( 'update-post_' . $post_id ) ) {
			wp_die(
				esc_html__( 'Nonce verification failed.', 'codesoup-options' ),
				esc_html__( 'Error', 'codesoup-options' ),
				array( 'response' => 403 )
			);
		}

		if ( ! $this->manager->can_edit_page( $post_id ) ) {
			wp_die(
				esc_html__( 'You do not have permission to edit this page.', 'codesoup-options' ),
				esc_html__( 'Error', 'codesoup-options' ),
				array( 'response' => 403 )
			);
		}

		do_action(
			'save_post',
			$post_id,
			get_post( $post_id ),
			true
		);

		do_action(
			sprintf(
				'save_post_%s',
				$this->manager->get_config( 'post_type' )
			),
			$post_id,
			get_post( $post_id ),
			true
		);

		$admin_page = $this->manager->get_admin_page();
		$redirect   = $admin_page->get_tab_url( $page_id );
		$redirect   = add_query_arg( 'message', 'updated', $redirect );

		wp_safe_redirect( $redirect );
		exit;
	}
}
