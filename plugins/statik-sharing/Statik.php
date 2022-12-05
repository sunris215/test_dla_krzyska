<?php
/**
 * Plugin Name: Statik Sharing
 * Plugin URI:  https://iamdripfeed.com/
 * Description: Statik plugin Share content in WordPress multisite.
 * Version:     1.0.1
 * Network:     True
 * Text domain: statik
 * Domain Path: /languages
 * Author:      Statik LTD
 * Author URI:  https://iamdripfeed.com/
 * License:     GPLv2 or later.
 */

declare(strict_types=1);

namespace Statik\Sharing;

\defined('WPINC') || exit;

require_once __DIR__ . '/vendor/autoload.php';

\define('Statik\Sharing\VERSION', '1.0.1');
\define('Statik\Sharing\COMMONS_VERSION', ['1.0.0', '1.1.0']);
\define('Statik\Sharing\PLUGIN_DIR', \plugin_dir_path(__FILE__));
\define('Statik\Sharing\PLUGIN_URL', \plugin_dir_url(__FILE__));
\define('Statik\Sharing\DEVELOPMENT', \defined('STATIK_SHARING_DEVELOPMENT') && STATIK_SHARING_DEVELOPMENT === true);
\define('Statik\Sharing\USE_DEFAULT_SETTINGS', \defined('STATIK_SHARING_SETTINGS') && STATIK_SHARING_SETTINGS);
\define('Statik\Sharing\DEFAULT_SETTINGS', USE_DEFAULT_SETTINGS ? STATIK_SHARING_SETTINGS : []);

DIProvider::registerGlobalDI();

new Plugin(__FILE__);
