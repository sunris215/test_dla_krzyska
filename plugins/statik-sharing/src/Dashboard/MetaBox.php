<?php

declare(strict_types=1);

namespace Statik\Sharing\Dashboard;

use Statik\Sharing\DI;
use Statik\Sharing\Helper\SwitcherTrait;
use const Statik\Sharing\PLUGIN_DIR;
use Statik\Sharing\Post\PostMeta;

/**
 * Class MetaBox.
 */
class MetaBox
{
    use SwitcherTrait;

    protected array $postTypes;

    /** @var \WP_Site[]|null */
    private ?array $blogs = null;

    /**
     * ShareManager constructor.
     */
    public function __construct()
    {
        $this->postTypes = (array) DI::Config()->get('settings.cpt.value', []);

        \add_action('admin_menu', [$this, 'addSharingMetaBox']);
        \add_action('wp_ajax_rebuild_meta_box', [$this, 'rebuildSharingMetaBoxAjax']);
    }

    /**
     * Display sharing meta box.
     */
    public function addSharingMetaBox(): void
    {
        foreach ($this->postTypes as $type) {
            \add_meta_box(
                'statik_sharing_meta_box',
                \__('Statik Sharing'),
                [$this, 'renderSharingMetaBoxHtml'],
                $type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Generate sharing meta box.
     */
    public function renderSharingMetaBoxHtml(\WP_Post $post): void
    {
        $postMetaManager = new PostMeta((int) $post->ID);

        \ob_start();

        if ($postMetaManager->isSharingTypeReplica()) {
            include PLUGIN_DIR . 'src/Partials/MetaBoxDisabled.php';
        } else {
            include PLUGIN_DIR . 'src/Partials/MetaBox.php';
        }

        echo \ob_get_clean();
    }

    /**
     * Handles rebuild HTML ajax request after the sharing process.
     */
    public function rebuildSharingMetaBoxAjax(): void
    {
        if (false === \wp_verify_nonce($_POST['nonce'] ?? null, 'statik_sharing_meta_box_nonce')) {
            \wp_send_json_error(\__('Invalid request nonce. Please reload page and try again!', 'statik'));
        }

        $postId = (int) \filter_input(\INPUT_POST, 'post_id', \FILTER_SANITIZE_NUMBER_INT);
        $post = \get_post($postId);

        if (false === $post instanceof \WP_Post) {
            \wp_send_json_error(\__('Sharing meta box cannot be displayed for provided post ID', 'statik'));
        }

        \ob_start();
        $this->renderSharingMetaBoxHtml($post);

        $metaBox = \ob_get_clean();

        \wp_send_json_success($metaBox);
    }

    /**
     * Get all blogs.
     */
    private function getBlogs(): array
    {
        if (null === $this->blogs) {
            $this->blogs = \get_sites([
                'archived'     => 0,
                'spam'         => 0,
                'deleted'      => 0,
            ]);
        }

        return $this->blogs;
    }
}
