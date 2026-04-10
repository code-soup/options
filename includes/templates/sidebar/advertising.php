<?php
/**
 * Advertising Sidebar Template
 *
 * Displays promotional banners and advertising content in the sidebar.
 *
 * @package CodeSoup\Options
 *
 * @var \CodeSoup\Options\Admin_Page $this Admin_Page instance
 */

defined( 'ABSPATH' ) || die;

// Default ads.
$ads = array(
	array(
		'type'        => 'text',
		'title'       => '🚀 CodeSoup Projects',
		'description' => 'Open source WordPress tools and frameworks',
		'items'       => array(
			array(
				'label'       => 'Pumpkin',
				'link'        => 'https://github.com/code-soup/pumpkin',
				'description' => 'WordPress starter theme with ACF PRO integration and component-based development',
			),
			array(
				'label'       => 'Metabox Schema',
				'link'        => 'https://github.com/code-soup/metabox-schema',
				'description' => 'Schema-driven form builder. Define fields as arrays, render forms, validate input',
			),
			array(
				'label'       => 'WordPress Plugin Boilerplate',
				'link'        => 'https://github.com/code-soup/wordpress-plugin-boilerplate',
				'description' => 'Modern plugin boilerplate with PSR-4, Webpack 5, live reload, and code quality tools',
			),
			array(
				'label'       => 'ACF Admin Categories',
				'link'        => 'https://github.com/code-soup/acf-admin-categories',
				'description' => 'Category organization for ACF field groups',
			),
			array(
				'label'       => 'View All Projects',
				'link'        => 'https://github.com/code-soup',
				'description' => 'Browse all CodeSoup repositories on GitHub',
			),
		),
	),
);

// Allow customization via filter.
$custom_ads = apply_filters(
	'codesoup_options_sidebar_ads',
	array(),
	$this->manager->get_instance_key()
);

if ( ! empty( $custom_ads ) ) {
	$ads = $custom_ads;
}
?>

<style>
.codesoup-ad-widget {
	background: #fff;
	border: 1px solid rgba(0, 0, 0, 0.05);
	border-radius: 4px;
	overflow: hidden;
	margin-bottom: 16px;
}

.codesoup-ad-widget:last-child {
	margin-bottom: 0;
}

.codesoup-ad-banner a {
	display: block;
	text-decoration: none;
	color: inherit;
}

.codesoup-ad-banner img {
	display: block;
	width: 100%;
	height: auto;
}

.codesoup-ad-content {
	padding: 16px;
}

.codesoup-ad-title {
	margin: 0 0 16px;
	font-size: 16px;
	font-weight: 600;
	color: #1e1e1e;
}

.codesoup-ad-description {
	margin: 0;
	font-size: 14px;
	color: #646970;
	line-height: 1.5;
}

.codesoup-ad-text {
	padding: 16px 24px;
}

.codesoup-ad-links {
	margin: 0;
	padding: 0;
	list-style: none;
}

.codesoup-ad-links li {
	margin-bottom: 10px;
}

.codesoup-ad-links li:last-child {
	margin-bottom: 0;
}

.codesoup-ad-links a {
	display: block;
	font-size: 14px;
	font-weight: 500;
	color: #26619c;
	text-decoration: none;
	transition: color 0.15s ease;
	margin-bottom: 4px;
}

.codesoup-ad-links a:hover {
	color: #135e96;
	text-decoration: underline;
}

.codesoup-ad-item-desc {
	font-size: 12px;
	color: #646970;
	line-height: 1.4;
	margin: 0 0 8px;
}
</style>

<?php foreach ( $ads as $ad ) : ?>
	<?php if ( 'banner' === $ad['type'] ) : ?>
		<div class="codesoup-ad-widget codesoup-ad-banner">
			<a href="<?php echo esc_url( $ad['link'] ); ?>" target="_blank" rel="noopener noreferrer">
				<img src="<?php echo esc_url( $ad['image'] ); ?>" alt="<?php echo esc_attr( $ad['title'] ); ?>" />
				<?php if ( ! empty( $ad['title'] ) || ! empty( $ad['description'] ) ) : ?>
					<div class="codesoup-ad-content">
						<?php if ( ! empty( $ad['title'] ) ) : ?>
							<h4 class="codesoup-ad-title"><?php echo esc_html( $ad['title'] ); ?></h4>
						<?php endif; ?>
						<?php if ( ! empty( $ad['description'] ) ) : ?>
							<p class="codesoup-ad-description"><?php echo esc_html( $ad['description'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</a>
		</div>
	<?php elseif ( 'text' === $ad['type'] ) : ?>
		<div class="codesoup-ad-widget codesoup-ad-text">
			<?php if ( ! empty( $ad['title'] ) ) : ?>
				<h4 class="codesoup-ad-title"><?php echo esc_html( $ad['title'] ); ?></h4>
			<?php endif; ?>
			<?php if ( ! empty( $ad['items'] ) ) : ?>
				<ul class="codesoup-ad-links">
					<?php foreach ( $ad['items'] as $item ) : ?>
						<li>
							<a href="<?php echo esc_url( $item['link'] ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $item['label'] ); ?>
							</a>
							<?php if ( ! empty( $item['description'] ) ) : ?>
								<p class="codesoup-ad-item-desc"><?php echo esc_html( $item['description'] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>
<?php endforeach; ?>
