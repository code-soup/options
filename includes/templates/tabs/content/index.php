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
	\CodeSoup\Options\AdminNotice::error(
		__( 'Page data not found.', 'codesoup-options' ),
		false
	);
	return;
}

$pages       = $manager->get_pages();
$active_page = $pages[ $active_tab ] ?? null;

if ( ! $active_page ) {
	\CodeSoup\Options\AdminNotice::error(
		__( 'Page configuration error.', 'codesoup-options' ),
		false
	);
	return;
}

$current_post_type        = $post->post_type;
$current_post_type_object = get_post_type_object( $current_post_type );

// Trigger add_meta_boxes to register metaboxes for this page.
do_action( 'add_meta_boxes', $current_post_type, $post );
do_action( 'add_meta_boxes_' . $current_post_type, $post ); ?>

<div id="tab-panel-<?php echo esc_attr( $active_tab ); ?>" role="tabpanel" aria-labelledby="tab-<?php echo esc_attr( $active_tab ); ?>">

	<?php
	// Check if any metaboxes are registered.
	global $wp_meta_boxes;
	$has_metaboxes = false;

	if ( isset( $wp_meta_boxes[ $current_post_type ] ) ) {
		foreach ( array( 'normal', 'advanced' ) as $context ) {
			if ( ! empty( $wp_meta_boxes[ $current_post_type ][ $context ] ) ) {
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
