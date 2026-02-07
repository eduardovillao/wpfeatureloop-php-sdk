# WPFeatureLoop PHP SDK

Collect feature requests and feedback from your WordPress plugin users.

## Installation

```bash
composer require eduardovillao/wpfeatureloop-sdk
```

## Usage

### Step 1: Initialize in your main plugin file

This must run on **every WordPress request** so REST API routes are registered:

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 */

require_once __DIR__ . '/vendor/autoload.php';

use WPFeatureLoop\Client;

Client::init('pk_live_your_public_key', 'your_project_id', [
    'language' => 'en',
]);
```

### Step 2: Render the widget

Add the widget wherever you want to display it:

```php
use WPFeatureLoop\Client;

echo Client::renderWidget();
```

## Configuration

| Option       | Type   | Default  | Description                               |
| ------------ | ------ | -------- | ----------------------------------------- |
| `language`   | string | `'en'`   | Widget language                           |
| `capability` | string | `'read'` | WordPress capability required to interact |

## Supported Languages

| Code    | Language             | Status      |
| ------- | -------------------- | ----------- |
| `en`    | English              | Available   |
| `pt-BR` | Brazilian Portuguese | Available   |
| `es`    | Spanish              | Coming soon |

## Requirements

- PHP 7.4+
- WordPress 5.0+

## License

MIT
