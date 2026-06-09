<?php
/**
 * Vertical Tab Navigation Template
 *
 * @package CodeSoup\Options
 *
 * @var string                        $active_tab Active tab ID.
 * @var array<\CodeSoup\Options\Page> $tab_pages  Registered pages.
 * @var \CodeSoup\Options\Admin_Page   $this       Admin_Page instance.
 */

defined( 'ABSPATH' ) || die;
?>

<nav class="codesoup-options-vertical-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Options navigation', 'codesoup-options' ); ?>">
	<ul>
		<?php foreach ( $tab_pages as $page_item ) : ?>
			<?php
			if ( ! current_user_can( $page_item->capability ) ) {
				continue;}
			?>

			<?php
			$is_active = $active_tab === $page_item->id;
			$tab_class = $is_active
				? 'codesoup-options-tab-item active'
				: 'codesoup-options-tab-item';
			$tab_url   = $this->get_tab_url( $page_item->id );
			?>

			<li class="<?php echo esc_attr( $tab_class ); ?>">
				<a
					href="<?php echo esc_url( $tab_url ); ?>"
					role="tab"
					aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
					aria-controls="tab-panel-<?php echo esc_attr( $page_item->id ); ?>"
				>
					<?php echo esc_html( $page_item->title ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

