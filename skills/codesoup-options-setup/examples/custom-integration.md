# Custom Integration Example

Create custom integrations for CMB2, MetaBox.io, Carbon Fields, or any field framework.

## Integration Interface

All integrations must implement `IntegrationInterface`:

```php
namespace CodeSoup\Options\Integrations;

use CodeSoup\Options\Manager;

interface IntegrationInterface {
	public function __construct( Manager $manager );
	public static function is_available(): bool;
	public function register_hooks(): void;
	public static function get_name(): string;
}
```

## Example: CMB2 Integration

```php
namespace MyPlugin\Integrations;

use CodeSoup\Options\Integrations\IntegrationInterface;
use CodeSoup\Options\Manager;

class CMB2 implements IntegrationInterface {

	private Manager $manager;

	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	public static function is_available(): bool {
		return class_exists( 'CMB2' );
	}

	public function register_hooks(): void {
		if ( ! self::is_available() ) {
			add_action( 'admin_notices', array( $this, 'show_missing_notice' ) );
			return;
		}
		add_action( 'cmb2_admin_init', array( $this, 'register_metaboxes' ) );
	}

	public static function get_name(): string {
		return 'CMB2';
	}

	public function show_missing_notice(): void {
		echo '<div class="notice notice-error"><p>CMB2 plugin is required but not installed.</p></div>';
	}

	public function register_metaboxes(): void {
		$pages = $this->manager->get_pages();
		$config = $this->manager->get_config();

		foreach ( $pages as $page ) {
			$cmb = new_cmb2_box(
				array(
					'id'           => $config['prefix'] . $page->get_id(),
					'title'        => $page->get_title(),
					'object_types' => array( $config['post_type'] ),
					'show_on'      => array(
						'key'   => 'post_name',
						'value' => $config['prefix'] . $page->get_id(),
					),
				)
			);

			// Add fields based on page ID
			if ( 'general' === $page->get_id() ) {
				$cmb->add_field(
					array(
						'name' => 'Site Title',
						'id'   => 'site_title',
						'type' => 'text',
					)
				);

				$cmb->add_field(
					array(
						'name' => 'Site Logo',
						'id'   => 'site_logo',
						'type' => 'file',
					)
				);
			}
		}
	}
}
```

## Registering Custom Integration

```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
	'instance_key',
	array(
		'integrations' => array(
			// Disable ACF
			'acf'  => array(
				'enabled' => false,
			),
			// Enable custom CMB2 integration
			'cmb2' => array(
				'enabled' => true,
				'class'   => 'MyPlugin\\Integrations\\CMB2',
			),
		),
	)
);

$manager->register_page(
	array(
		'id'         => 'general',
		'title'      => 'General Settings',
		'capability' => 'manage_options',
	)
);

$manager->init();
```

## Integration Lifecycle

1. **Construction** - Integration receives Manager instance
2. **Availability Check** - `is_available()` determines if integration can run
3. **Hook Registration** - `register_hooks()` sets up WordPress hooks
4. **Execution** - Integration handles its own field rendering and saving

## Best Practices

- Check availability - Verify required plugins/classes exist
- Fail gracefully - Return false from `is_available()` if requirements not met
- Use Manager instance - Access Manager config via `$this->manager->get_config()`
- Handle saving - Integration should save its own data
- Log errors - Use `$this->manager->get_logger()` for debugging

