<?php
/**
 * Admin Header Template
 *
 * @package CodeSoup\Options
 *
 * @var string $logo_url     Logo URL.
 * @var string $menu_label   Page title.
 * @var object $active_page  Active page object (tabs mode only).
 */

defined( 'ABSPATH' ) || die;
?>

<div class="codesoup-options-header">
	<div class="codesoup-options-container">
		<div class="codesoup-options-header-inner">
			<div class="codesoup-options-header-logo">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $menu_label ); ?>" />
			</div>
			<div class="codesoup-options-header-title">
				<h1><?php echo esc_html( $active_page ? $active_page->title : $menu_label ); ?></h1>
				<?php if ( $active_page && $active_page->description ) : ?>
					<p class="description"><?php echo esc_html( $active_page->description ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
