<?php
/**
 * Example metabox template for site information
 *
 * @var \WP_Post $post Current post object
 * @package CodeSoup\Options
 */

use CodeSoup\Options\Manager;

// Get current values from Manager instance.
$manager = Manager::get( 'site_settings' );
$options = $manager->get_options( 'general' );

$site_title       = $options['site_title'] ?? '';
$site_description = $options['site_description'] ?? '';
$site_email       = $options['site_email'] ?? '';
?>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<label for="site_title"><?php esc_html_e( 'Site Title', 'codesoup-options' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="site_title"
					name="site_title"
					value="<?php echo esc_attr( $site_title ); ?>"
					class="regular-text"
				/>
				<p class="description">
					<?php esc_html_e( 'Enter your site title.', 'codesoup-options' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="site_description"><?php esc_html_e( 'Site Description', 'codesoup-options' ); ?></label>
			</th>
			<td>
				<textarea
					id="site_description"
					name="site_description"
					rows="3"
					class="large-text"
				><?php echo esc_textarea( $site_description ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Brief description of your site.', 'codesoup-options' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="site_email"><?php esc_html_e( 'Contact Email', 'codesoup-options' ); ?></label>
			</th>
			<td>
				<input
					type="email"
					id="site_email"
					name="site_email"
					value="<?php echo esc_attr( $site_email ); ?>"
					class="regular-text"
				/>
				<p class="description">
					<?php esc_html_e( 'Primary contact email address.', 'codesoup-options' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>

<?php
/**
 * Save handler - Hook into save_post to save custom fields
 *
 * Add this to your functions.php or plugin:
 *
 * use CodeSoup\Options\Manager;
 *
 * add_action( 'save_post', function( $post_id ) {
 *     $manager = Manager::get( 'site_settings' );
 *
 *     if ( get_post_type( $post_id ) !== $manager->get_config( 'post_type' ) ) {
 *         return;
 *     }
 *
 *     // Verify user has permission to edit this page
 *     if ( ! $manager->can_edit_page( $post_id ) ) {
 *         return;
 *     }
 *
 *     // Sanitize your data
 *     $data = array(
 *         'site_title'       => isset( $_POST['site_title'] ) ? sanitize_text_field( $_POST['site_title'] ) : '',
 *         'site_description' => isset( $_POST['site_description'] ) ? sanitize_textarea_field( $_POST['site_description'] ) : '',
 *         'site_email'       => isset( $_POST['site_email'] ) ? sanitize_email( $_POST['site_email'] ) : '',
 *     );
 *
 *     // Save using Manager::save_options() - throws exception on failure
 *     try {
 *         $manager->save_options( $post_id, $data );
 *     } catch ( \InvalidArgumentException $e ) {
 *         error_log( 'Failed to save options: ' . $e->getMessage() );
 *     }
 * }, 10, 1 );
 */

