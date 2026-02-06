# WPFeatureLoop PHP SDK

Collect feature requests and feedback from your WordPress plugin users.

## Installation

### With Composer (Recommended)

```bash
composer require eduardovillao/wpfeatureloop-sdk
```

### Without Composer

1. Download or clone this repository into your plugin's folder
2. Include the SDK manually:

```php
require_once plugin_dir_path(__FILE__) . 'wpfeatureloop-sdk/include.php';
```

## Usage

### Step 1: Initialize in your main plugin file

This must run on every WordPress request so REST API routes are registered:

```php
use WPFeatureLoop\Client;

// Initialize early in your main plugin file
Client::init('pk_live_xxx', 'your_project_id');
```

### Step 2: Render the widget where you need it

```php
use WPFeatureLoop\Client;

// Render the widget (outputs HTML)
echo Client::renderWidget(['locale' => 'en']);
```

That's it! Two lines of code total.

## Configuration Options

### Client::init() options

```php
Client::init('pk_live_xxx', 'your_project_id', [
    'api_url' => 'https://custom-api.example.com',  // Custom API URL
    'capability' => 'manage_options',                // Required WP capability (default: 'read')
    'assets_url' => 'https://example.com/assets',   // Custom assets URL (auto-detected normally)
]);
```

### Client::renderWidget() options

```php
echo Client::renderWidget([
    'locale' => 'en',           // 'en' or 'pt-BR' (default: 'en')
    'container_id' => 'my-id',  // HTML container ID (default: 'wpfeatureloop')
]);
```

## Full Example

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 */

// Load Composer autoload
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use WPFeatureLoop\Client;

// Initialize SDK (runs on every request)
Client::init('pk_live_xxx', 'proj_xxx');

// Add admin page
add_action('admin_menu', function() {
    add_menu_page(
        'Feature Requests',
        'Feature Requests',
        'read',
        'my-plugin-features',
        function() {
            echo '<div class="wrap">';
            echo Client::renderWidget(['locale' => 'en']);
            echo '</div>';
        }
    );
});
```

## Advanced Usage

If you need more control, you can use the Widget class directly:

```php
use WPFeatureLoop\Client;
use WPFeatureLoop\Widget;

$client = Client::getInstance();
$widget = new Widget($client, [
    'locale' => 'en',
    'container_id' => 'custom-container',
    'templates_path' => '/path/to/custom/templates',
]);
echo $widget->render();
```

## Supported Locales

- `en` (English)
- `pt-BR` (Brazilian Portuguese)

## Requirements

- PHP 7.4+
- WordPress 5.0+

## License

MIT
