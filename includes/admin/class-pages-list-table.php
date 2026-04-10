<?php
/**
 * Pages List Table
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Pages_List_Table class
 *
 * Custom list table for options pages.
 *
 * @since 1.3.0
 */
class Pages_List_Table extends \WP_List_Table {

	/**
	 * Manager instance
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Constructor
	 *
	 * @param Manager $manager Manager instance.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;

		parent::__construct(
			array(
				'singular' => 'page',
				'plural'   => 'pages',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get columns
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return array(
			'title'       => __( 'Title', 'codesoup-options' ),
			'description' => __( 'Description', 'codesoup-options' ),
		);
	}

	/**
	 * Prepare items for display
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$pages = $this->manager->get_pages();
		$items = array();

		foreach ( $pages as $page ) {
			if ( ! current_user_can( $page->capability ) ) {
				continue;
			}

			$post = $this->manager->get_post_by_page_id( $page->id );

			$items[] = array(
				'id'          => $page->id,
				'title'       => $page->title,
				'description' => $page->description ?? '',
				'post_id'     => $post ? $post->ID : 0,
			);
		}

		$this->items = $items;
	}

	/**
	 * Column default
	 *
	 * @param array  $item Item data.
	 * @param string $column_name Column name.
	 * @return string
	 */
	protected function column_default( $item, $column_name ): string {
		return esc_html( $item[ $column_name ] ?? '' );
	}

	/**
	 * Column title
	 *
	 * @param array $item Item data.
	 * @return string
	 */
	protected function column_title( $item ): string {
		if ( ! $item['post_id'] ) {
			return sprintf(
				'<strong>%s</strong>',
				esc_html( $item['title'] )
			);
		}

		$edit_url = add_query_arg(
			array(
				'post'   => $item['post_id'],
				'action' => 'edit',
			),
			admin_url( 'post.php' )
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>',
			esc_url( $edit_url ),
			esc_html( $item['title'] )
		);
	}

	/**
	 * Display no items message
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No pages found.', 'codesoup-options' );
	}
}
