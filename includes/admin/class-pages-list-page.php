<?php
/**
 * Pages List Page Handler
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * PagesListPage class
 *
 * Handles custom admin page rendering for pages mode.
 *
 * @since 1.3.0
 */
class PagesListPage {

	/**
	 * Manager instance
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Instance key
	 *
	 * @var string
	 */
	private string $instance_key;

	/**
	 * List table instance
	 *
	 * @var PagesListTable|null
	 */
	private ?PagesListTable $list_table = null;

	/**
	 * Constructor
	 *
	 * @param Manager $manager Manager instance.
	 */
	public function __construct( Manager $manager ) {
		$this->manager      = $manager;
		$this->instance_key = $manager->get_instance_key();
	}

	/**
	 * Get page slug
	 *
	 * @return string
	 */
	public function get_page_slug(): string {
		return sprintf(
			'codesoup-options-pages-%s',
			$this->instance_key
		);
	}

	/**
	 * Render admin page
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! $this->list_table ) {
			$this->list_table = new PagesListTable( $this->manager );
		}

		$this->list_table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( $this->manager->get_config( 'menu_label' ) ); ?></h1>
			<hr class="wp-header-end">

			<?php
			$message = isset( $_GET['message'] ) ? sanitize_key( $_GET['message'] ) : '';
			if ( 'updated' === $message ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'codesoup-options' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post">
				<?php
				$this->list_table->display();
				?>
			</form>
		</div>
		<?php
	}
}
