<?php
/**
 * Example metabox template for footer content
 *
 * @var \WP_Post $post Current post object
 * @package CodeSoup\Options
 */

use CodeSoup\Options\Manager;

// Get current values from Manager instance.
$manager = Manager::get( 'site_settings' );
$options = $manager->get_options( 'footer' );

$copyright_text = $options['copyright_text'] ?? '';
$footer_scripts = $options['footer_scripts'] ?? '';
?>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<label for="copyright_text"><?php esc_html_e( 'Copyright Text', 'codesoup-options' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="copyright_text"
					name="copyright_text"
					value="<?php echo esc_attr( $copyright_text ); ?>"
					class="large-text"
				/>
				<p class="description">
					<?php esc_html_e( 'Copyright notice displayed in footer.', 'codesoup-options' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="footer_scripts"><?php esc_html_e( 'Footer Scripts', 'codesoup-options' ); ?></label>
			</th>
			<td>
				<textarea
					id="footer_scripts"
					name="footer_scripts"
					rows="5"
					class="large-text code"
				><?php echo esc_textarea( $footer_scripts ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Scripts to include in footer (e.g., analytics code).', 'codesoup-options' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>

