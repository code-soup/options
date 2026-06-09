<?php
/**
 * Banner Sidebar Template
 *
 * @package CodeSoup\Options
 *
 * @var \CodeSoup\Options\Admin_Page $this Admin_Page instance.
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

// Validate template path is safe
$allowed_dirs = array(
	\CodeSoup\Options\Path_Helper::get_base_path(),
	get_stylesheet_directory(),
	get_template_directory(),
);

$is_allowed = false;
$real_path  = realpath( $sidebar_template );

if ( $real_path ) {
	foreach ( $allowed_dirs as $allowed_dir ) {
		$real_allowed = realpath( $allowed_dir );
		if ( $real_allowed && strpos( $real_path, $real_allowed ) === 0 ) {
			$is_allowed = true;
			break;
		}
	}
}

// If template doesn't exist or not in allowed directory, return
if ( ! $is_allowed || ! file_exists( $sidebar_template ) ) {
	return;
}
?>

<div class="codesoup-options-banner-sidebar">
	<?php
	// Include custom sidebar template
	// Template has access to $this (Admin_Page instance)
	require $sidebar_template;
	?>
</div>
