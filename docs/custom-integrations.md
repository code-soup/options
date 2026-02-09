# Custom Integrations

Creating integrations for CMB2, MetaBox.io, Carbon Fields, or any other field framework.

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
		add_action( 'cmb2_admin_init', array( $this, 'register_metaboxes' ) );
	}

	public static function get_name(): string {
		return 'CMB2';
	}

	public function register_metaboxes(): void {
		// Your CMB2 metabox registration
		// Access manager: $this->manager->get_config(), $this->manager->get_pages(), etc.
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
			'cmb2' => array(
				'enabled' => true,
				'class'   => 'MyPlugin\\Integrations\\CMB2',
			),
		),
	)
);
```

## Integration Lifecycle

1. **Construction** - Integration receives Manager instance
2. **Availability Check** - `is_available()` determines if integration can run
3. **Hook Registration** - `register_hooks()` sets up WordPress hooks
4. **Execution** - Integration handles its own field rendering and saving

## Best Practices

- **Check availability** - Verify required plugins/classes exist
- **Fail gracefully** - Return false from `is_available()` if requirements not met
- **Use Manager instance** - Access Manager config via `$this->manager->get_config()`
- **Handle saving** - Integration should save its own data
- **Log errors** - Use `$this->manager->get_logger()` for debugging

## Available Frameworks

- **CMB2** - Custom Metaboxes 2
- **MetaBox.io** - Meta Box plugin
- **Carbon Fields** - WordPress developer library
- **Custom** - Your own field system

## Complete Example

See `docs/examples/03-custom-integration.php` for working examples with different frameworks.
