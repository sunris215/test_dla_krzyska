<?php

declare(strict_types=1);

\defined('ABSPATH') || exit;

use Statik\Sharing\Dashboard\Page\HelpersPage;
use Statik\Sharing\Logger;

/**
 * @var HelpersPage $this  Logs page
 * @var int         $index Current index
 * @var object      $log   Log instance
 */
$class = $log->level >= Logger::ERROR ? 'error-row'
    : ($log->level >= Logger::WARNING ? 'warning-row' : 'positive-row');
$logLevelIconClass = $log->level >= Logger::ERROR ? 'dashicons-dismiss'
    : ($log->level >= Logger::WARNING ? 'dashicons-warning' : 'dashicons-yes-alt');

?>

<tr class="<?= $class; ?> js-collapse-trigger collapse-trigger" data-collapse="<?= $index; ?>">
    <td><?= $index; ?></td>
    <td><?= \date('d M Y, H:i:s e', \strtotime($log->datetime)); ?></td>
    <td><i class="dashicons <?= $logLevelIconClass; ?>"> </i> <?= \ucfirst(\strtolower($log->level_name)); ?></td>
    <td><?= $log->context->action ?: '---'; ?></td>
    <td data-source-key="source_blog"><?= $log->context->source_blog ?: '---'; ?></td>
    <td data-source-key="source_post"><?= $log->context->source_id ?: '---'; ?></td>
    <td data-source-key="dest_blog"><?= $log->context->dest_blog ?: '---'; ?></td>
    <td data-source-key="dest_post"><?= $log->context->dest_id ?: '---'; ?></td>
    <td data-source-key="user_id"><?= $log->context->usr ?: '---'; ?></td>
</tr>

<tr class="<?= $class; ?> toggle-collapse" data-collapse="<?= $index; ?>">
    <td colspan="9">
        <div class="table-result js-toggle-collapse" style="display: none">
            <div class="col">
                <h4><?= \__('Action', 'statik'); ?>:</h4>
                <span><?= $log->context->action ?: '---'; ?></span>
                <br>
                <h4><?= \__('User', 'statik'); ?>:</h4>
                <span data-dest="user_id"><?= $log->context->usr ?: '---'; ?></span>
            </div>

            <div class="col">
                <h4><?= \__('Source Blog', 'statik'); ?>:</h4>
                <span data-dest="source_blog"><?= $log->context->source_blog ?: '---'; ?></span>
                <br>
                <h4><?= \__('Source Post', 'statik'); ?>:</h4>
                <span data-dest="source_post"><?= $log->context->source_id ?: '---'; ?></span>
            </div>

            <div class="col">
                <h4><?= \__('Destination Blog', 'statik'); ?>:</h4>
                <span data-dest="dest_blog"><?= $log->context->dest_blog ?: '---'; ?></span>
                <br>
                <h4><?= \__('Destination Post', 'statik'); ?>:</h4>
                <span data-dest="dest_post"><?= $log->context->dest_id ?: '---'; ?></span>
            </div>

            <?php if (isset($log->context->warning)) { ?>
                <?php $message = \json_decode($log->context->warning) ?: $log->context->warning; ?>

                <div class="col-2 warning-wrapper">
                    <h4><?= \__('Warning message', 'statik'); ?>: </h4>
                    <span><?= \print_r($message, true); ?></span>
                </div>
            <?php } ?>

            <?php if (isset($log->context->error)) { ?>
                <?php $message = \json_decode($log->context->error) ?: $log->context->error; ?>

                <div class="col-2 warning-wrapper error-wrapper">
                    <h4><?= \__('Error message', 'statik'); ?>: </h4>
                    <span><?= \print_r($message, true); ?></span>
                </div>
            <?php } ?>
        </div>
    </td>
</tr>