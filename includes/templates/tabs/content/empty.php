<?php
/**
 * Empty State Template
 *
 * Displayed when no metaboxes are registered for a tab.
 *
 * @package CodeSoup\Options
 */

defined( 'ABSPATH' ) || die;
?>

<div class="codesoup-options-empty-state">
	<h2><?php esc_html_e( 'No Settings Defined', 'codesoup-options' ); ?></h2>
	<p>
		<?php esc_html_e( 'There are no settings configured for this page yet.', 'codesoup-options' ); ?>
	</p>
	<p>
		<a href="https://github.com/code-soup/options" class="button button-primary" target="_blank">
			<?php esc_html_e( 'View Documentation', 'codesoup-options' ); ?>
		</a>
	</p>
</div>
