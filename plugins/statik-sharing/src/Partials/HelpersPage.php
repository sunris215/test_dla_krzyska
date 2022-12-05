<?php

declare(strict_types=1);

\defined('ABSPATH') || exit;

use Statik\Sharing\Dashboard\Page\HelpersPage;
use const Statik\Sharing\PLUGIN_DIR;

/**
 * @var HelpersPage $this
 */
$logsFiles = $this->getLogsDates();

$date = \filter_input(\INPUT_GET, 'date', \FILTER_SANITIZE_STRING);
$date = empty($date) ? \array_key_first($logsFiles) : $date;

$tabs = [
    'logs'           => 'Logs',
    'posts_exporter' => 'Posts exporter',
];

$currentTab = \filter_input(\INPUT_GET, 'tab', \FILTER_SANITIZE_STRING);
$currentTab = \array_key_exists($currentTab, $tabs) ? $currentTab : \key($tabs);

?>

<div id="statik" class="wrap statik_helpers_page">
    <h1><?= $GLOBALS['title']; ?></h1>

    <hr/>

    <div class="nav-tab-wrapper environments-tabs">
        <?php foreach ($tabs as $tab => $name) { ?>
            <a href="<?= \add_query_arg('tab', $tab); ?>"
               class="nav-tab <?= $tab === $currentTab ? 'nav-tab-active' : null; ?>"
               data-env="<?= $tab; ?>"
            >
                <?= $name; ?>
            </a>
        <?php } ?>
    </div>

    <?php switch ($currentTab) {
        case 'logs':
            require_once PLUGIN_DIR . 'src/Partials/Helpers/Logs.php';
            break;
        case 'posts_exporter':
            require_once PLUGIN_DIR . 'src/Partials/Helpers/PostsExporter.php';
            break;
    } ?>
</div>