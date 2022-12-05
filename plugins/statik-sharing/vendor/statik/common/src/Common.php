<?php

declare(strict_types=1);

namespace Statik\Common;

\define('Statik\Common\VERSION', '1.0.1');
\define('Statik\Common\DEVELOPMENT', \defined('STATIK_COMMON_DEVELOPMENT') && STATIK_COMMON_DEVELOPMENT === true);

if (\defined('WP_PLUGIN_URL') && \defined('WP_PLUGIN_DIR')) {
    \define('Statik\Common\COMMON_DIR', \rtrim(\dirname(__DIR__), '/\\') . '/');
    \define('Statik\Common\COMMON_URL', WP_PLUGIN_URL . \str_replace(WP_PLUGIN_DIR, '', COMMON_DIR));
} else {
    \define('Statik\Common\COMMON_DIR', \rtrim(\dirname(__DIR__), '/\\') . '/');
    \define('Statik\Common\COMMON_URL', '');
}
