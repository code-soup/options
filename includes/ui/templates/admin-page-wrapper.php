<?php
/**
 * Admin Page Wrapper Template
 *
 * @package CodeSoup\Options
 *
 * @var string                        $active_tab    Active tab ID.
 * @var array<\CodeSoup\Options\Page> $pages         Registered pages.
 * @var string                        $tab_position  Tab position (top/left/right).
 * @var \CodeSoup\Options\AdminPage   $this          AdminPage instance.
 */

defined( 'ABSPATH' ) || die;

$layout_class = sprintf(
	'codesoup-options-layout codesoup-options-layout-%s',
	esc_attr( $tab_position )
);
?>

<div class="wrap <?php echo esc_attr( $layout_class ); ?>">
	<h1><?php echo esc_html( $this->manager->get_config( 'menu_label' ) ); ?></h1>

	<?php if ( isset( $_GET['message'] ) && 'updated' === $_GET['message'] ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved.', 'codesoup-options' ); ?></p>
		</div>
	<?php endif; ?>

	<?php
	if ( 'top' === $tab_position ) {
		require __DIR__ . '/tab-navigation-horizontal.php';
	}
	?>

	<div class="codesoup-options-content-wrapper">
		<?php
		if ( 'left' === $tab_position ) {
			require __DIR__ . '/tab-navigation-vertical.php';
		}
		?>

		<div class="codesoup-options-tab-content">
			<?php require __DIR__ . '/tab-content.php'; ?>
		</div>

		<?php
		if ( 'right' === $tab_position ) {
			require __DIR__ . '/tab-navigation-vertical.php';
		}
		?>
	</div>
</div>

