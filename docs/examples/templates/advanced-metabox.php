<?php
/**
 * Advanced Metabox Template
 *
 * @package CodeSoup\Options
 *
 * @var \WP_Post $post Post object.
 * @var array    $args Metabox arguments.
 */

defined( 'ABSPATH' ) || die;

use CodeSoup\Options\Manager;

$manager = Manager::get( 'site_settings' );
$options = $manager->get_options( 'advanced' );

$enable_feature_x = $options['enable_feature_x'] ?? false;
$api_key          = $options['api_key'] ?? '';
?>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<label for="enable-feature-x">
					<?php esc_html_e( 'Enable Feature X', 'codesoup-options' ); ?>
				</label>
			</th>
			<td>
				<label>
					<input
						type="checkbox"
						id="enable-feature-x"
						name="advanced_options[enable_feature_x]"
						value="1"
						<?php checked( $enable_feature_x, true ); ?>
					/>
					<?php esc_html_e( 'Enable experimental feature X', 'codesoup-options' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="api-key">
					<?php esc_html_e( 'API Key', 'codesoup-options' ); ?>
				</label>
			</th>
			<td>
				<input
					type="text"
					id="api-key"
					name="advanced_options[api_key]"
					value="<?php echo esc_attr( $api_key ); ?>"
					class="regular-text"
				/>
				<p class="description">
					<?php esc_html_e( 'Enter your API key for external service integration.', 'codesoup-options' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>

