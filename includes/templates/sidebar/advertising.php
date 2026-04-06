<?php
/**
 * Advertising Sidebar Template
 *
 * Displays promotional banners and advertising content in the sidebar.
 *
 * @package CodeSoup\Options
 *
 * @var \CodeSoup\Options\AdminPage $this AdminPage instance
 */

defined( 'ABSPATH' ) || die;

// Get ads from filter
$ads = apply_filters(
	'codesoup_options_sidebar_ads',
	array(),
	$this->manager->get_instance_key()
);

// Always use demo content for now - customize via filter
$ads = array(
	array(
		'type'  => 'text',
		'title' => '⭐ Premium Version',
		'items' => array(
			array(
				'label' => 'Unlock Advanced Features',
				'link'  => 'https://codesoup.co',
			),
			array(
				'label' => 'Priority Support',
				'link'  => 'https://codesoup.co/support',
			),
			array(
				'label' => 'Lifetime Updates',
				'link'  => 'https://codesoup.co',
			),
		),
	),
	array(
		'type'  => 'text',
		'title' => '📚 Resources',
		'items' => array(
			array(
				'label' => 'Documentation',
				'link'  => 'https://codesoup.co/docs',
			),
			array(
				'label' => 'Video Tutorials',
				'link'  => 'https://youtube.com',
			),
			array(
				'label' => 'Code Examples',
				'link'  => 'https://codesoup.co/examples',
			),
		),
	),
	array(
		'type'  => 'text',
		'title' => '💬 Support',
		'items' => array(
			array(
				'label' => 'Community Forum',
				'link'  => 'https://codesoup.co/forum',
			),
			array(
				'label' => 'Submit a Ticket',
				'link'  => 'https://codesoup.co/support',
			),
			array(
				'label' => 'Report a Bug',
				'link'  => 'https://github.com',
			),
		),
	),
	array(
		'type'  => 'text',
		'title' => '🚀 More from CodeSoup',
		'items' => array(
			array(
				'label' => 'All Plugins',
				'link'  => 'https://codesoup.co/plugins',
			),
			array(
				'label' => 'WordPress Themes',
				'link'  => 'https://codesoup.co/themes',
			),
			array(
				'label' => 'Custom Development',
				'link'  => 'https://codesoup.co/hire',
			),
		),
	),
);

// Allow customization via filter (merges with defaults).
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
	color: #26619c;
	text-decoration: none;
	transition: color 0.15s ease;
}

.codesoup-ad-links a:hover {
	color: #135e96;
	text-decoration: underline;
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
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>
<?php endforeach; ?>
