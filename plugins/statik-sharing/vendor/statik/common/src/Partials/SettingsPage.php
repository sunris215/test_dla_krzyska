<?php

declare(strict_types=1);

namespace Statik\Deploy\Dashboard\Page;

/**
 * Fire on settings page filter.
 *
 * @param array current tabs
 */
$tabs = (array) \apply_filters('Statik\Common\settingsTabs', []);

$currentTab = \filter_input(\INPUT_GET, 'tab', \FILTER_SANITIZE_STRING);
$currentTab = \array_key_exists($currentTab, $tabs) ? $currentTab : \key($tabs);
?>

<div id="statik" class="wrap">
    <h1><?= $GLOBALS['title']; ?></h1>

    <hr/>

    <div class="nav-tab-wrapper settings-tabs">
        <?php foreach ($tabs as $tab => $name) { ?>
            <a href="<?= \add_query_arg('tab', $tab); ?>"
               class="nav-tab <?= $tab === $currentTab ? 'nav-tab-active' : null; ?>"
               data-env="<?= $tab; ?>"
            >
                <?= $name; ?>
            </a>
        <?php } ?>

        <label>
            <select id="nav-select">
                <?php foreach ($tabs as $tab => $name) { ?>
                    <option value="<?= \add_query_arg('tab', $tab); ?>"
                        <?php \selected($tab === $currentTab); ?>>
                        <?= $name; ?>
                    </option>
                <?php } ?>
            </select>
        </label>
    </div>

    <?= \do_action('Statik\Common\settingsPageTabs', $currentTab); ?>
</div>