<?php
/**
 * Mobile Tab Navigation Template (Select Dropdown)
 *
 * @package CodeSoup\Options
 *
 * @var array  $tab_pages Array of page objects.
 * @var string $active_tab Currently active tab ID.
 * @var string $page_slug Admin page slug.
 */

defined( 'ABSPATH' ) || die;
?>

<div class="codesoup-options-mobile-nav">
	<select id="codesoup-mobile-tab-select" data-page-slug="<?php echo esc_attr( $page_slug ); ?>">
		<?php
		foreach ( $tab_pages as $page_item ) {
			if ( ! current_user_can( $page_item->capability ) ) {
				continue;
			}

			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $page_item->id ),
				selected( $active_tab, $page_item->id, false ),
				esc_html( $page_item->title )
			);
		}
		?>
	</select>
</div>
