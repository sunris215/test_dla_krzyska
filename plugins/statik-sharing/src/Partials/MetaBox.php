<?php

declare(strict_types=1);

\defined('ABSPATH') || exit;

use Statik\Sharing\Dashboard\MetaBox;
use Statik\Sharing\Share\Handler\CronShareHandler;
use Statik\Sharing\Share\ShareStructureManager;

/**
 * @var MetaBox  $this
 * @var \WP_Post $post
 */
$currentBlogId = \get_current_blog_id();
$postSharingStructure = ShareStructureManager::getPostSharingStructure((int) $post->ID);

?>

<div id="statik_sharing_table_grid" data-nonce="<?= \wp_create_nonce('statik_sharing_meta_box_nonce'); ?>">
    <div class="row head">
        <div class="cell checkbox"><label><input type="checkbox" class="js-bulk-check"></label></div>
        <div class="cell blog-name"><?= \__('Blog name', 'statik'); ?></div>
        <div class="cell post-url"><?= \__('Post URL', 'statik'); ?></div>
        <div class="cell bulk-actions">
            <label for="bulk_actions">
                <select name="bulk_actions" id="bulk_actions" class="js-bulk-actions">
                    <option value="" selected disabled><?= \__('Select bulk action...', 'statik'); ?></option>
                    <option value="share"><?= \__('Share posts', 'statik'); ?></option>
                    <option value="detach"><?= \__('Detach posts', 'statik'); ?></option>
                    <option value="delete"><?= \__('Delete posts', 'statik'); ?></option>
                </select>
            </label>
            <button class="js-bulk-action-apply js-action-button button-secondary button" type="button">
                <?= \__('Apply', 'statik'); ?>
            </button>
        </div>
    </div>

    <?php foreach ($this->getBlogs() as $blog) { ?>
        <?php
        $blogSharing = $postSharingStructure['blogs'][(int) $blog->blog_id] ?? null;
        $isPrimaryBlog = (int) $blog->blog_id === $currentBlogId;

        $canEdit = \current_user_can_for_blog($blog->blog_id, 'edit_post', $blogSharing->post->ID ?? null);
        $canEdit = null !== $blogSharing && false === $isPrimaryBlog && $canEdit ? '' : 'disabled';

        $canShare = \current_user_can_for_blog($blog->blog_id, 'edit_posts');
        $canShare = null === $blogSharing && false === $isPrimaryBlog && $canShare ? '' : 'disabled';

        $frontUrl = $isPrimaryBlog ? \get_permalink($post->ID) : $blogSharing->frontUrl ?? null;
        $duringCron = ($blogSharing->status ?? null) === CronShareHandler::CRON_IN_PROGRESS;
        ?>

        <div class="row single-blog"
             data-status="<?= $blogSharing->status ?? null; ?>"
             data-blog="<?= $blog->blog_id; ?>"
             data-post="<?= $blogSharing->post->ID ?? null; ?>">
            <div class="cell checkbox">
                <?php if ('shared' === ($blogSharing->status ?? null)) { ?>
                    <input type="hidden" name="statik_share_to[]" value="<?= $blog->blog_id; ?>">
                <?php } ?>

                <?php if ((int) $blog->blog_id !== $currentBlogId && false === $duringCron) { ?>
                    <label>
                        <input type="checkbox"
                               name="statik_share_blogs[]" <?= $canEdit || $canShare ? '' : 'disabled'; ?>
                        >
                    </label>
                <?php } ?>
            </div>
            <div class="cell blog-name"><?= $blog->blogname; ?></div>
            <div class="cell post-url">
                <a href="<?= $frontUrl; ?>" target="_blank"><?= $frontUrl ?? null; ?></a>
            </div>
            <div class="cell action-cell edit">
                <?php if (false === $isPrimaryBlog) { ?>
                    <a <?= isset($blogSharing->editUrl) ? "href='{$blogSharing->editUrl}'" : null; ?>
                            target="_blank" class="<?= $canEdit; ?>">
                        <?php include 'Icons/Edit.php'; ?>
                    </a>
                <?php } ?>
            </div>
            <div class="cell action-cell detach">
                <?php if (false === $isPrimaryBlog) { ?>
                    <button class="js-action-button <?= $canEdit; ?>" data-action="detach" type="button">
                        <?php include 'Icons/Detach.php'; ?>
                    </button>
                <?php } ?>
            </div>
            <div class="cell action-cell delete">
                <?php if (false === $isPrimaryBlog) { ?>
                    <button class="js-action-button <?= $canEdit; ?>" data-action="delete" type="button">
                        <?php include 'Icons/Delete.php'; ?>
                    </button>
                <?php } ?>
            </div>
            <div class="cell action-cell share">
                <?php if (false === $isPrimaryBlog) { ?>
                    <button class="js-action-button <?= $canShare; ?>" data-action="share" type="button">
                        <?php include 'Icons/Share.php'; ?>
                    </button>
                <?php } ?>
            </div>
            <div class="cell sharing-cell">
                <?= \__('The blog will be shared by Cron task in the next few minutes', 'statik'); ?>
            </div>
            <div class="loading-wrapper">
                <div class="loader">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
            <div class="error-wrapper">
                <div class="message"></div>
                <div class="buttons">
                    <button class="js-close-error button button-secondary" type="button">
                        <?= \__('Cancel', 'statik'); ?>
                    </button>
                    <button class="js-action-button button button-primary" data-action="share"
                            data-warning="conflict" type="button">
                        <?= \__('Override', 'statik'); ?>
                    </button>
                </div>
                <button class="js-close-error close" type="button">X</button>
            </div>
            <div class="success-wrapper">
                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle cx="26" cy="26" r="25" fill="none"/>
                    <path fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
        </div>
    <?php } ?>
</div>
