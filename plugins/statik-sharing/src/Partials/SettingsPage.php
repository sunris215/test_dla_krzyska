<?php

declare(strict_types=1);

\defined('ABSPATH') || exit;

use Statik\Sharing\Dashboard\Page\SettingsPage;
use Statik\Sharing\DI;

/** @var SettingsPage $this */
?>
<form method="POST">
    <?php \wp_nonce_field('statik_sharing_settings_nonce'); ?>

    <?= DI::Generator()->generateStructure('sharing_settings'); ?>

    <?= \get_submit_button('', 'primary', 'submit', false); ?>
</form>