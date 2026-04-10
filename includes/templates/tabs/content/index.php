<?php
/**
 * Tab Content Template
 *
 * @package CodeSoup\Options
 *
 * @var string                      $active_tab Active tab ID.
 * @var \CodeSoup\Options\Admin_Page $this       Admin_Page instance.
 */

defined( 'ABSPATH' ) || die;

$manager = $this->manager;
$post    = $manager->get_post_by_page_id( $active_tab );

if ( ! $post ) {
	\CodeSoup\Options\Admin_Notice::error(
		__( 'Page data not found.', 'codesoup-options' ),
		false
	);
	return;
}

$pages       = $manager->get_pages();
$active_page = $pages[ $active_tab ] ?? null;

if ( ! $active_page ) {
	\CodeSoup\Options\Admin_Notice::error(
		__( 'Page configuration error.', 'codesoup-options' ),
		false
	);
	return;
}

$current_post_type        = $post->post_type;
$current_post_type_object = get_post_type_object( $current_post_type );

// Trigger add_meta_boxes to register metaboxes for this page.
$screen_id_for_action = $this->get_screen_id();
do_action( 'add_meta_boxes', $screen_id_for_action, $post );
do_action( 'add_meta_boxes_' . $screen_id_for_action, $post ); ?>

<div id="tab-panel-<?php echo esc_attr( $active_tab ); ?>" role="tabpanel" aria-labelledby="tab-<?php echo esc_attr( $active_tab ); ?>">

	<?php
	// Check if any metaboxes are registered.
	global $wp_meta_boxes;
	$has_metaboxes = false;
	$screen_id     = $this->get_screen_id();

	if ( isset( $wp_meta_boxes[ $screen_id ] ) ) {
		foreach ( array( 'normal', 'advanced' ) as $context ) {
			if ( ! empty( $wp_meta_boxes[ $screen_id ][ $context ] ) ) {
				$has_metaboxes = true;
				break;
			}
		}
	}

	if ( ! $has_metaboxes ) {
		require $manager->get_template_path( 'tabs/content/empty.php' );
	} else {
		require $manager->get_template_path( 'tabs/content/form.php' );
	}
	?>

</div>

<?php
wp_reset_postdata();
?>
