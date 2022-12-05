<?php

declare(strict_types=1);

namespace Statik\Sharing\Dashboard\Page;

use Statik\Common\Dashboard\DashboardInterface;
use Statik\Common\Dashboard\Page\AbstractPage;
use Statik\Common\Helper\NoticeManager;
use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Logger;
use const Statik\Sharing\PLUGIN_DIR;
use Statik\Sharing\Post\PostMeta;

/**
 * Class HelpersPage.
 */
class HelpersPage extends AbstractPage
{
    use SwitcherTrait;

    /**
     * DeploymentPage constructor.
     */
    public function __construct(DashboardInterface $dashboard)
    {
        parent::__construct($dashboard);

        $this->saveSettingsHandler();

        \add_action('network_admin_menu', [$this, 'initPage'], 20);
        \add_action('wp_ajax_sharing_logs_links', [$this, 'handleAjaxSharingLogsLinks']);
    }

    /**
     * {@inheritdoc}
     */
    public function initPage(): void
    {
        \add_submenu_page(
            'statik',
            \__('Sharing helpers', 'statik'),
            \__('Sharing helpers', 'statik'),
            'manage_options',
            'statik_sharing',
            fn() => $this->getSettingsPage(),
            1
        );
    }

    /**
     * Get settings page and set required variables.
     */
    public function getSettingsPage(): void
    {
        include PLUGIN_DIR . 'src/Partials/HelpersPage.php';
    }

    /**
     * Handle Ajax request for sharing logs links.
     */
    public function handleAjaxSharingLogsLinks(): void
    {
        $sourceBlogId = \filter_input(\INPUT_GET, 'source_blog', \FILTER_VALIDATE_INT);
        $sourcePostId = \filter_input(\INPUT_GET, 'source_post', \FILTER_VALIDATE_INT);
        $destBlogId = \filter_input(\INPUT_GET, 'dest_blog', \FILTER_VALIDATE_INT);
        $destPostId = \filter_input(\INPUT_GET, 'dest_post', \FILTER_VALIDATE_INT);
        $userId = \filter_input(\INPUT_GET, 'user_id', \FILTER_VALIDATE_INT);

        if (false === $sourceBlogId || false === $destBlogId) {
            \wp_send_json_error();
        }

        $user = \get_user_by('ID', $userId);

        $template = '<a href="%s" target="_blank">%s (ID: %s)<span class="dashicons dashicons-external"> </span></a>';

        $blogs = [
            'source' => [$sourceBlogId, $sourcePostId],
            'dest'   => [$destBlogId, $destPostId],
        ];

        $data = [
            'user_id' => $user ? \sprintf(
                $template,
                \get_edit_user_link($userId),
                $user->display_name ?: "{$user->first_name} {$user->last_name}",
                $userId
            ) : '---',
        ];

        foreach ($blogs as $key => $blog) {
            [$blogId, $postId] = $blog;

            $blogUrl = \get_admin_url($blogId);
            $blogName = \get_blog_details($blogId)->blogname ?? null;
            $blogNameCut = \strlen($blogName) > 12 ? \substr($blogName, 0, 12) . '...' : $blogName;

            $postUrl = $this::safeSwitchToBlog($blogId, 'get_edit_post_link', $postId);
            $postName = $this::safeSwitchToBlog($blogId, 'get_the_title', $postId);
            $postNameCut = \strlen($postName) > 12 ? \substr($blogName, 0, 12) . '...' : $postName;

            $data["{$key}_blog"] = $blogId ? \sprintf($template, $blogUrl, $blogName, $blogId) : '---';
            $data["{$key}_blog_short"] = $blogId ? \sprintf($template, $blogUrl, $blogNameCut, $blogId) : '---';
            $data["{$key}_post"] = $postId ? \sprintf($template, $postUrl, $postName, $postId) : '---';
            $data["{$key}_post_short"] = $postId ? \sprintf($template, $postUrl, $postNameCut, $postId) : '---';
        }

        \wp_send_json_success($data);
    }

    /**
     * Get all logs dates.
     */
    private function getLogsDates(): array
    {
        if (false === \file_exists(Logger::LOGS_DIRECTORY)) {
            return [];
        }

        $logs = \scandir(Logger::LOGS_DIRECTORY);
        $logs = \array_filter($logs, fn($file) => 0 === \strpos($file, 'log'));

        $logsFiles = [];

        foreach ($logs as $log) {
            $date = \str_replace(['log-', '.txt'], '', $log);
            $logsFiles[$date] = $log;
        }

        \krsort($logsFiles);

        return $logsFiles;
    }

    /**
     * Get current Log file Data.
     */
    private function getCurrentLog(?string $date): string
    {
        $logFile = Logger::LOGS_DIRECTORY . "/log-{$date}.txt";

        if (false === \file_exists($logFile)) {
            return '';
        }

        $currentLog = @\file_get_contents($logFile);
        $level = (int) \filter_input(\INPUT_GET, 'level', \FILTER_SANITIZE_NUMBER_INT);
        $search = \filter_input(\INPUT_GET, 'search', \FILTER_SANITIZE_STRING);

        $timeFrom = \filter_input(\INPUT_GET, 'time_from', \FILTER_SANITIZE_STRING);
        $timeFrom = false === empty($timeFrom) ? \strtotime("{$date} {$timeFrom}") : 0;
        $timeTo = \filter_input(\INPUT_GET, 'time_to', \FILTER_SANITIZE_STRING);
        $timeTo = false === empty($timeTo) ? \strtotime("{$date} {$timeTo}") : 0;

        $currentLog = \explode(\PHP_EOL, (string) $currentLog);
        $logs = [];

        foreach (\array_reverse($currentLog) as $index => $log) {
            $log = \json_decode($log);

            if (null === $log) {
                continue;
            }

            if ($level && $log->level !== $level) {
                continue;
            }

            if (false === empty($search) && false === \strpos($log->message, $search)) {
                continue;
            }

            $time = \strtotime($log->datetime);

            if ($timeFrom && $time < $timeFrom) {
                continue;
            }

            if ($timeTo && $time > $timeTo) {
                continue;
            }

            \ob_start();
            include PLUGIN_DIR . 'src/Partials/Helpers/LogsTableRow.php';

            $logs[] = \ob_get_clean();
        }

        return \implode(\PHP_EOL, $logs) . '';
    }

    /**
     * This is the CSV export handler.
     */
    private function saveSettingsHandler(): void
    {
        if (empty($_POST['statik_sharing_export'])) {
            return;
        }

        if (false === \wp_verify_nonce($_POST['_wpnonce'] ?? null, 'statik_pages_exporter_nonce')) {
            NoticeManager::error(
                \__('CSV file cannot be exported because not valid nonce. Please try again!', 'statik')
            );

            return;
        }

        $cpt = \filter_var($_POST['statik_sharing_export']['cpt'] ?? '', \FILTER_SANITIZE_STRING);
        $separator = \filter_var($_POST['statik_sharing_export']['separator'] ?? '', \FILTER_SANITIZE_STRING);
        $separator = 'semicolon' === $separator ? ';' : ',';

        $blogs = \get_sites([
            'archived' => 0,
            'spam'     => 0,
            'deleted'  => 0,
        ]);

        $posts = [];

        foreach ($blogs as $blog) {
            $posts = \array_merge(
                $posts,
                self::safeSwitchToBlog($blog->id, [$this, 'getPostsData'], $cpt, $blog, $blogs)
            );
        }

        $time = \gmdate('D, d M Y H:i:s');
        $date = \date('Y-m-d', \strtotime($time));
        \header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
        \header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
        \header("Last-Modified: {$time} GMT");

        \header('Content-Type: application/force-download');
        \header('Content-Type: application/octet-stream');
        \header('Content-Type: application/download');

        \header("Content-Disposition: attachment;filename=statik-export-{$cpt}-{$date}.csv;");
        \header('Content-Transfer-Encoding: binary');

        $csvFile = \fopen('php://output', 'w');
        \fputcsv($csvFile, \array_keys(\reset($posts)), $separator);

        foreach ($posts as $post) {
            \fputcsv($csvFile, $post, $separator);
        }

        \fclose($csvFile);

        exit();
    }

    private function getPostsData(string $cpt, \WP_Site $currentBlog, array $blogs): array
    {
        $posts = \get_posts([
            'post_type'      => $cpt,
            'posts_per_page' => -1,
        ]);

        foreach ($posts as $post) {
            $author = \get_user_by('ID', $post->post_author);
            $postMeta = new PostMeta($post->ID);
            $postSharingMeta = $postMeta->getMeta();
            $postIsPrimary = $postMeta->isSharingTypePrimary();

            $postData = [
                'blog_id'      => $currentBlog->id,
                'blog_name'    => $currentBlog->blogname,
                'post_id'      => $post->ID,
                'post_title'   => $post->post_title,
                'post_type'    => $post->post_type,
                'post_author'  => $author->display_name,
                'post_created' => $post->post_date,
                'post_updated' => $post->post_modified,
                'post_url'     => \get_permalink($post),
                'is_sharing'   => $postMeta->getSharingType() ? 1 : 0,
                'is_master'    => $postIsPrimary ? 1 : 0,
            ];

            $postSharingMeta = $postMeta->isSharingTypeReplica()
                ? (new PostMeta($postSharingMeta->postId, $postSharingMeta->blogId))->getMeta()
                : $postSharingMeta;

            foreach ($blogs as $blog) {
                $postData["sharing_blog_{$blog->id}"] = $postSharingMeta->{$blog->id}->postId ?? '';
            }

            $postsData[] = $postData;
        }

        return $postsData ?? [];
    }
}
