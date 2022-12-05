<?php

declare(strict_types=1);

\defined('ABSPATH') || exit;

use Statik\Sharing\Dashboard\MetaBox;
use Statik\Sharing\Share\ShareStructureManager;

/**
 * @var MetaBox  $this
 * @var \WP_Post $post
 */
?>

<p><?= \__('Sharing table is not available from the replica post level.', 'statik'); ?></p>

<a href="<?= ShareStructureManager::getPrimaryPostEditLink(); ?>" class="button button-primary">
    <?= \__('Edit Primary post', 'statik'); ?>
</a>
