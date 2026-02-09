<?php
/**
 * Actions Metabox Template
 *
 * @package CodeSoup\Options
 *
 * @var \WP_Post $post Post object.
 */

// Don't allow direct access to file.
defined( 'ABSPATH' ) || die;
?>
<div class="submitbox" id="submitpost">
	<div id="delete-action">
		<?php
		if ( current_user_can( 'delete_post', $post->ID ) ) {
			$delete_url = get_delete_post_link(
				$post->ID,
				'',
				true
			);
			printf(
				'<a class="submitdelete deletion" href="%s">%s</a>',
				esc_url( $delete_url ),
				esc_html__( 'Delete', 'codesoup-options' )
			);
		}
		?>
	</div>
	<div id="publishing-action">
		<span class="spinner"></span>
		<?php submit_button(
			__( 'Update', 'codesoup-options' ),
			'primary large',
			'publish',
			false 
		); ?>
	</div>
	<div class="clear"></div>
</div>

