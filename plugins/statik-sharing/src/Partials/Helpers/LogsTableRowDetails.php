<?php

declare(strict_types=1);

\defined('ABSPATH') || exit;

use Statik\Sharing\Dashboard\Page\HelpersPage;
use Statik\Sharing\Logger;

/**
 * @var HelpersPage $this  Logs page
 * @var int         $index current index
 * @var object      $log   log instance
 */
$class = $log->level >= Logger::ERROR ? 'error' : ($log->level >= Logger::WARNING ? 'warning' : null);
$logLevel = $log->level >= Logger::WARNING
    ? "<i class='dashicons dashicons-warning'></i> {$log->level_name}"
    : $log->level_name;

$class = $log->level >= Logger::ERROR ? 'error' : ($log->level >= Logger::WARNING ? 'warning' : null);
$logLevel = $log->level >= Logger::WARNING
    ? "<i class='dashicons dashicons-warning'></i> {$log->level_name}"
    : $log->level_name;

$editSourcePostUrl = $this::safeSwitchToBlog(
    $log->context->source_blog,
    'get_edit_post_link',
    $log->context->source_id
);
$sourcePostTitle = $this::safeSwitchToBlog(
    $log->context->source_blog,
    'get_the_title',
    $log->context->source_id
);

$editDestinationPostUrl = $this::safeSwitchToBlog(
    $log->context->dest_blog,
    'get_edit_post_link',
    $log->context->dest_id
);
$destinationPostTitle = $this::safeSwitchToBlog(
    $log->context->dest_blog,
    'get_the_title',
    $log->context->dest_id
);

?>

<div class="table-result">
    <div class="col">
        <h4><?= \__('Source Blog', 'statik'); ?>:</h4>
        <a href="<?= \get_admin_url($log->context->source_blog); ?>">
            <?= \get_blog_details($log->context->source_blog)->blogname ?? null; ?>
            (ID: <?= $log->context->source_blog; ?>)
            <span class="dashicons dashicons-external"> </span>
        </a>
        <br><br>
        <h4><?= \__('Source Post', 'statik'); ?>:</h4>
        <a href="<?= $editSourcePostUrl; ?>">
            <?= $sourcePostTitle; ?> (ID: <?= $log->context->source_id; ?>)
            <span class="dashicons dashicons-external"> </span>
        </a>
    </div>

    <div class="col">
        <h4><?= \__('Destination Blog', 'statik'); ?>:</h4>
        <a href="<?= \get_admin_url($log->context->dest_blog); ?>">
            <?= \get_blog_details($log->context->dest_blog)->blogname ?? null; ?>
            (ID: <?= $log->context->dest_blog; ?>)
            <span class="dashicons dashicons-external"> </span>
        </a>
        <br><br>
        <h4><?= \__('Destination Post', 'statik'); ?>:</h4>
        <a href="<?= $editDestinationPostUrl; ?>">
            <?= $destinationPostTitle; ?> (ID: <?= $log->context->dest_id; ?>)
            <span class="dashicons dashicons-external"> </span>
        </a>
    </div>

    <br>

    <?php if (isset($log->context->warning)) { ?>
        <div class="col-2 warning">
            <h4>Warning: </h4>
            <p><?= $log->context->warning; ?></p>
        </div>
    <?php } ?>

    <?php if (isset($log->context->error)) { ?>
        <div class="col-2 warning">
            <h4>Warning: </h4>
            <p><?= $log->context->error; ?></p>
        </div>
    <?php } ?>
</div>

