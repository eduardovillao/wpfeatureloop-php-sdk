<?php
/**
 * Manual include file for users without Composer autoload
 *
 * Usage:
 * require_once 'path/to/wpfeatureloop-sdk/include.php';
 */

defined('ABSPATH') || exit;

// Load all SDK classes
require_once __DIR__ . '/src/User.php';
require_once __DIR__ . '/src/Api.php';
require_once __DIR__ . '/src/RestApi.php';
require_once __DIR__ . '/src/Widget.php';
require_once __DIR__ . '/src/Client.php';
