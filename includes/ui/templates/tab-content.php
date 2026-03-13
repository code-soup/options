<?php
/**
 * Tab Content Template
 *
 * @package CodeSoup\Options
 *
 * @var string                      $active_tab Active tab ID.
 * @var \CodeSoup\Options\AdminPage $this       AdminPage instance.
 */

defined( 'ABSPATH' ) || die;

$manager = $this->manager;
$post    = $manager->get_post_by_page_id( $active_tab );

if ( ! $post ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Page data not found.', 'codesoup-options' )
	);
	return;
}

$pages       = $manager->get_pages();
$active_page = $pages[ $active_tab ] ?? null;

if ( ! $active_page ) {
	return;
}

global $post;

wp_nonce_field( 'update-post_' . $post->ID );
wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
?>

<div id="tab-panel-<?php echo esc_attr( $active_tab ); ?>" role="tabpanel" aria-labelledby="tab-<?php echo esc_attr( $active_tab ); ?>">
	
	<?php if ( $active_page->description ) : ?>
		<p class="description"><?php echo esc_html( $active_page->description ); ?></p>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="post">
		<input type="hidden" name="action" value="codesoup_options_save" />
		<input type="hidden" name="post_ID" value="<?php echo esc_attr( $post->ID ); ?>" />
		<input type="hidden" name="instance_key" value="<?php echo esc_attr( $manager->get_instance_key() ); ?>" />
		<input type="hidden" name="page_id" value="<?php echo esc_attr( $active_tab ); ?>" />
		
		<?php wp_nonce_field( 'codesoup_options_save_' . $post->ID, 'codesoup_options_nonce' ); ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<!-- Main content area for metaboxes -->
				</div>

				<div id="postbox-container-1" class="postbox-container">
					<?php do_meta_boxes( $manager->get_config( 'post_type' ), 'side', $post ); ?>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<?php do_meta_boxes( $manager->get_config( 'post_type' ), 'normal', $post ); ?>
					<?php do_meta_boxes( $manager->get_config( 'post_type' ), 'advanced', $post ); ?>
				</div>
			</div>
		</div>
	</form>
</div>

<script type="text/javascript">
	jQuery(document).ready(function($) {
		postboxes.add_postbox_toggles('<?php echo esc_js( $manager->get_config( 'post_type' ) ); ?>');
	});
</script>

