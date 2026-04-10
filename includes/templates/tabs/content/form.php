<?php
/**
 * Metaboxes Form Template
 *
 * Displays the form with metaboxes and save button.
 *
 * @package CodeSoup\Options
 *
 * @var object $post                  WordPress post object.
 * @var string $current_post_type     Post type.
 * @var string $active_tab            Active tab ID.
 * @var object $manager               Manager instance.
 */

defined( 'ABSPATH' ) || die;
?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="post" class="codesoup-options-edit-form">
	<input type="hidden" name="action" value="codesoup_options_save" />
	<input type="hidden" name="post_ID" value="<?php echo esc_attr( $post->ID ); ?>" />
	<input type="hidden" name="post_type" value="<?php echo esc_attr( $current_post_type ); ?>" />
	<input type="hidden" name="instance_key" value="<?php echo esc_attr( $manager->get_instance_key() ); ?>" />
	<input type="hidden" name="page_id" value="<?php echo esc_attr( $active_tab ); ?>" />

	<?php
	wp_nonce_field( 'update-post_' . $post->ID );
	$screen_id = $manager->get_admin_page()->get_screen_id();
	do_meta_boxes( $screen_id, 'normal', $post );
	do_meta_boxes( $screen_id, 'advanced', $post );
	?>

	<!-- Save Button -->
	<div class="codesoup-save-actions">
		<?php submit_button( __( 'Save Changes', 'codesoup-options' ), 'primary large', 'submit', false ); ?>
	</div>
</form>
