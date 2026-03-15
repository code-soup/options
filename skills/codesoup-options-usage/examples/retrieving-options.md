# Retrieving Options Examples

## Get All Options for a Page

Returns all options as an array from post_content (fast bulk retrieval):

```php
use CodeSoup\Options\Manager;

$manager = Manager::get( 'theme_settings' );
$options = $manager->get_options( 'general' );

$site_logo = $options['site_logo'] ?? '';
$site_tagline = $options['site_tagline'] ?? '';
```

## Get Single Option

Uses ACF's `get_field()` if ACF is enabled, otherwise retrieves from post_content:

```php
$manager = Manager::get( 'theme_settings' );

// Get single field
$logo_id = $manager->get_option( 'general', 'site_logo' );

// With default value
$footer_text = $manager->get_option( 'footer', 'copyright', '© 2024' );
```

## Using in Header Template

```php
use CodeSoup\Options\Manager;

$settings = Manager::get( 'theme_settings' );

// Display logo
$logo_id = $settings->get_option( 'header', 'logo' );
if ( $logo_id ) {
	echo wp_get_attachment_image( $logo_id, 'full' );
}
```

## Using in Footer Template

```php
use CodeSoup\Options\Manager;

$settings = Manager::get( 'theme_settings' );

// Display social links
$facebook = $settings->get_option( 'social', 'facebook_url' );
if ( $facebook ) {
	printf( '<a href="%s">Facebook</a>', esc_url( $facebook ) );
}

// Display copyright
$copyright = $settings->get_option( 'footer', 'copyright_text', '© ' . gmdate( 'Y' ) );
echo esc_html( $copyright );
```

## Helper Function Pattern

```php
use CodeSoup\Options\Manager;

function get_site_contact_email() {
	$manager = Manager::get( 'site_settings' );
	return $manager->get_option( 'general', 'contact_email', get_option( 'admin_email' ) );
}
```

## Bulk Retrieval Pattern

```php
use CodeSoup\Options\Manager;

// Get all options at once (faster for multiple values)
$settings = Manager::get( 'theme_settings' );
$footer_options = $settings->get_options( 'footer' );

// Access multiple values
$copyright = $footer_options['copyright'] ?? '';
$address = $footer_options['address'] ?? '';
$phone = $footer_options['phone'] ?? '';
```

## Conditional Display Pattern

```php
use CodeSoup\Options\Manager;

$settings = Manager::get( 'theme_settings' );
$social = $settings->get_options( 'social' );

// Display social links if they exist
$links = array(
	'facebook'  => $social['facebook'] ?? '',
	'twitter'   => $social['twitter'] ?? '',
	'instagram' => $social['instagram'] ?? '',
);

foreach ( $links as $platform => $url ) {
	if ( $url ) {
		printf(
			'<a href="%s" class="social-%s">%s</a>',
			esc_url( $url ),
			esc_attr( $platform ),
			esc_html( ucfirst( $platform ) )
		);
	}
}
```

