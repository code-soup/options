<?php
/**
 * Admin Page Wrapper Template
 *
 * @package CodeSoup\Options
 *
 * @var string                        $active_tab    Active tab ID.
 * @var array<\CodeSoup\Options\Page> $tab_pages     Registered pages.
 * @var string                        $tab_position  Tab position (top/left).
 * @var \CodeSoup\Options\AdminPage   $this          AdminPage instance.
 */

defined( 'ABSPATH' ) || die;

$layout_class = sprintf(
	'codesoup-options-layout codesoup-options-layout-%s',
	esc_attr( $tab_position )
);

$page_slug = $this->get_page_slug();
?>

<div class="wrap <?php echo esc_attr( $layout_class ); ?>">
	<div class="codesoup-options-container">
		<h1><?php echo esc_html( $this->manager->get_config( 'menu_label' ) ); ?></h1>

		<?php
		if ( isset( $_GET['message'] ) && 'updated' === $_GET['message'] ) {
			\CodeSoup\Options\AdminNotice::success(
				__( 'Settings saved.', 'codesoup-options' )
			);
		}
		?>

		<?php
		if ( 'top' === $tab_position ) {
			require $this->manager->get_template_path( 'tabs/navigation/horizontal.php' );
		}
		?>

		<!-- Mobile Navigation -->
		<?php require $this->manager->get_template_path( 'tabs/navigation/mobile.php' ); ?>

		<div class="codesoup-options-content-wrapper">
			<?php
			if ( 'left' === $tab_position ) {
				require $this->manager->get_template_path( 'tabs/navigation/vertical.php' );
			}
			?>

			<div class="codesoup-options-tab-content">
				<?php require $this->manager->get_template_path( 'tabs/content/index.php' ); ?>
			</div>

			<?php require $this->manager->get_template_path( 'sidebar/banner-sidebar.php' ); ?>
		</div>
	</div>
</div>

