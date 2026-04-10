<?php
/**
 * Banner Sidebar Template
 *
 * @package CodeSoup\Options
 *
 * @var \CodeSoup\Options\AdminPage $this AdminPage instance.
 */

defined( 'ABSPATH' ) || die;

// Default to advertising template
$default_template = $this->manager->get_template_path( 'sidebar/advertising.php' );

// Allow custom sidebar template via filter
$sidebar_template = apply_filters(
	'codesoup_options_sidebar_template',
	$default_template,
	$this->manager->get_instance_key()
);

// If template doesn't exist, return
if ( ! file_exists( $sidebar_template ) ) {
	return;
}
?>

<div class="codesoup-options-banner-sidebar">
	<?php
	// Include custom sidebar template
	// Template has access to $this (AdminPage instance)
	require $sidebar_template;
	?>
</div>
