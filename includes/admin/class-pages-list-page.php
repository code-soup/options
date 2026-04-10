<?php
/**
 * Pages List Page Handler
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Pages_List_Page class
 *
 * Handles custom admin page rendering for pages mode.
 *
 * @since 1.3.0
 */
class Pages_List_Page {

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
	 * @var Pages_List_Table|null
	 */
	private ?Pages_List_Table $list_table = null;

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
			$this->list_table = new Pages_List_Table( $this->manager );
		}

		$this->list_table->prepare_items();

		?>
		<div class="wrap">
			<?php $config = $this->manager->get_config(); ?>
			<h1 class="wp-heading-inline"><?php echo esc_html( $config['menu']['label'] ); ?></h1>
			<hr class="wp-header-end">

			<?php
			$message = isset( $_GET['message'] ) ? sanitize_key( $_GET['message'] ) : '';
			if ( 'updated' === $message ) {
				Admin_Notice::success(
					__( 'Settings saved.', 'codesoup-options' )
				);
			}
			?>

			<form method="post">
				<?php
				$this->list_table->display();
				?>
			</form>
		</div>
		<?php
	}
}
