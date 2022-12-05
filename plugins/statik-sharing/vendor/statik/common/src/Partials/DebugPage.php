<?php
declare(strict_types=1);

use Statik\Common\Config\AbstractConfig;
use Statik\Common\Dashboard\Page\DebugPage;

/* @var DebugPage $this */

$instances = AbstractConfig::getInstances();

?>

<div id="statik" class="wrap">
    <h1><?= $GLOBALS['title']; ?></h1>

    <hr/>

    <form method="POST">
        <?php \wp_nonce_field('statik_commons_debug', '_commons_nonce'); ?>
        <label for="statik_debug[config]"><?= \__('Config', 'statik'); ?></label>
        <select name="statik_debug[config]" id="statik_debug[config]" required>
            <?php foreach (\array_keys($instances) as $name) { ?>
                <option value="<?= $name; ?>"><?= $name; ?></option>
            <?php } ?>
        </select>

        <label for="statik_debug[key]"><?= \__('Config key', 'statik'); ?></label>
        <input type="text" name="statik_debug[key]" id="statik_debug[key]" required>

        <label for="statik_debug[value]"><?= \__('Config new value', 'statik'); ?></label>
        <input type="text" name="statik_debug[value]" id="statik_debug[value]">

        <input type="submit" value="<?= \__('Save new value', 'statik'); ?>" name="statik_debug[add]" class="button">
        <input type="submit" value="<?= \__('Delete key', 'statik'); ?>" name="statik_debug[remove]"
               class="button button-remove">
    </form>

    <hr/>

    <?php foreach ($instances as $name => $instance) { ?>
        <h3><?= $name; ?></h3>
        <?php /** @noinspection ForgottenDebugOutputInspection */ ?>
        <?php \dump($instance->toArray()); ?>
    <?php } ?>

</div>