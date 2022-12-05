<?php

declare(strict_types=1);

namespace Statik\Sharing\Share\Processor\Post;

use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Post\PostMeta;
use Statik\Sharing\Share\Data\AbstractData;
use Statik\Sharing\Share\Data\PostData;
use Statik\Sharing\Share\Exception\ShareProcessException;
use Statik\Sharing\Share\Processor\AbstractProcessor;

/**
 * Class AbstractPostProcessor.
 */
abstract class AbstractPostProcessor extends AbstractProcessor
{
    use SwitcherTrait;

    private const EXCLUDED_META = [
        PostMeta::META_KEY,
        PostMeta::META_TYPE,
        '_edit_lock',
        '_edit_last',
    ];

    protected AbstractData $sourceObject;

    protected ?AbstractData $destinationObject = null;

    /**
     * Get exclude meta keys.
     */
    public function getExcludeKeys(): array
    {
        /**
         * Fire exclude post meta keys filter.
         *
         * @param string[] list of excluded meta keys
         */
        return (array) \apply_filters('Statik\Sharing\excludedPostMetaKeys', self::EXCLUDED_META);
    }

    /**
     * {@inheritdoc}
     */
    protected function validate(): void
    {
        parent::validate();

        if (false === $this->sourceObject instanceof PostData) {
            throw new ShareProcessException(
                \__('Post processor requires the source object to be an instance of PostData', 'statik')
            );
        }

        if (true === $this->sourceObject->getMeta()->isSharingTypeReplica()) {
            throw new ShareProcessException(\__('Source post cannot be replica type', 'statik'));
        }

        if ('revision' === $this->sourceObject->getPost()->post_type) {
            throw new ShareProcessException(\__('Revision post type cannot be processed', 'statik'));
        }

        if ('trash' === $this->sourceObject->getPost()->post_status) {
            throw new ShareProcessException(\__('Trashed post cannot be processed', 'statik'));
        }

        if (null === $this->destinationObject) {
            return;
        }

        if (false === $this->destinationObject instanceof PostData) {
            throw new ShareProcessException(
                \__('Post processor requires the source object to be an instance of PostData', 'statik')
            );
        }

        if (true === $this->destinationObject->getMeta()->isSharingTypePrimary()) {
            throw new ShareProcessException(\__('Destination post cannot be primary type', 'statik'));
        }

        if ('revision' === $this->destinationObject->getPost()->post_type) {
            throw new ShareProcessException(\__('Revision post type cannot be processed', 'statik'));
        }

        if ('trash' === $this->destinationObject->getPost()->post_status) {
            throw new ShareProcessException(\__('Trashed post cannot be processed', 'statik'));
        }

        if ($this->sourceObject->getPost()->post_type !== $this->destinationObject->getPost()->post_type) {
            throw new ShareProcessException(\__('Source and destination posts types are not consistent', 'statik'));
        }

        $destinationPostMeta = $this->destinationObject->getMeta()->getMeta();

        if (false === isset($destinationPostMeta->blogId)) {
            if ($this->sourceObject->getPost()->post_name !== $this->destinationObject->getPost()->post_name) {
                throw new ShareProcessException(\__('Source and destination posts slugs are not consistent', 'statik'));
            }
        } elseif (
            $destinationPostMeta->blogId !== $this->sourceBlogId
            || $destinationPostMeta->postId !== $this->sourceObject->getPost()->ID
        ) {
            throw new ShareProcessException(\__('Destination post is already linked to different post'));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws ShareProcessException
     */
    protected function validateDuplicates(): void
    {
        $foundPosts = self::safeSwitchToBlog(
            $this->destinationBlogId,
            'get_posts',
            [
                'name'           => $this->sourceObject->getPost()->post_name,
                'post_type'      => $this->sourceObject->getPost()->post_type,
                'posts_per_page' => 1,
            ]
        );

        /**
         * Fire found duplicates action.
         *
         * @param \WP_Post[]            founded posts
         * @param AbstractPostProcessor processor instance
         */
        $foundPosts = (array) \apply_filters('Statik\Share\foundDuplicates', $foundPosts, $this);

        if (1 === \count($foundPosts) && null === $this->destinationObject) {
            throw new ShareProcessException(
                \json_encode([
                    'message'     => \__('Post cannot be shared because of slug conflict', 'statik'),
                    'conflict_id' => \reset($foundPosts)->ID,
                ]),
                419
            );
        }
    }

    /**
     * Insert post into destination blog.
     *
     * @throws ShareProcessException
     */
    protected function insertPostIntoDestinationBlog(): AbstractData
    {
        \wp_defer_term_counting(true);

        $post = $this->sourceObject->getPost()->to_array();

        if ($this->destinationObject && $this->destinationObject->getPost() instanceof \WP_Post) {
            $post['ID'] = $this->destinationObject->getPost()->ID;

            /**
             * Fire before update post filter.
             *
             * @param array                 post data
             * @param AbstractPostProcessor processor instance
             */
            $post = (array) \apply_filters('Statik\Sharing\beforeUpdatePost', $post, $this);

            $newPostId = \wp_update_post($post);
        } else {
            unset($post['ID']);

            /**
             * Fire before insert post filter.
             *
             * @param array                 post data
             * @param AbstractPostProcessor processor instance
             */
            $post = (array) \apply_filters('Statik\Sharing\beforeInsertPost', $post, $this);

            $newPostId = \wp_insert_post($post);
        }

        if (\is_wp_error($newPostId)) {
            throw new ShareProcessException(
                \sprintf(
                    \__('An error occurred when trying to insert post: %s', 'statik'),
                    $newPostId->get_error_message()
                )
            );
        }

        $this->destinationObject = new PostData($newPostId, $this->destinationBlogId);

        $this->insertPostMetaIntoDestinationBlog();
        $this->insertPostTaxonomiesIntoDestinationBlog();
        $this->insertTermsCountsIntoDestinationBlog();

        $this->destinationObject->initialize();

        /**
         * Fire before after insert post action.
         *
         * @param array                 post data
         * @param AbstractPostProcessor processor instance
         */
        \do_action('Statik\Sharing\afterInsertPost', $post, $this);

        return $this->destinationObject;
    }

    /**
     * Insert terms count in destination blog.
     */
    protected function insertTermsCountsIntoDestinationBlog(): void
    {
        /**
         * Fire before update terms count action.
         *
         * @param AbstractPostProcessor processor instance
         */
        \do_action('Statik\Share\beforeUpdateTermsCount', $this);

        foreach ($this->sourceObject->getTaxonomies() as $taxonomy) {
            $termIds = [];
            $post_terms = \wp_get_object_terms(
                $this->destinationObject->getPost()->ID,
                $taxonomy,
                ['orderby' => 'term_order']
            );

            if (empty($post_terms)) {
                continue;
            }

            foreach ($post_terms as $term) {
                $termIds[] = $term->term_id;
            }

            \wp_update_term_count_now($termIds, $taxonomy);
        }

        /**
         * Fire after update terms count action.
         *
         * @param AbstractPostProcessor processor instance
         */
        \do_action('Statik\Share\afterUpdateTermsCount', $this);
    }

    /**
     * Insert destination Post Meta.
     */
    protected function insertPostMetaIntoDestinationBlog(): void
    {
        /**
         * Fire before insert post meta action.
         *
         * @param AbstractPostProcessor processor instance
         */
        \do_action('Statik\Share\beforeInsertPostMeta', $this);

        $destinationPostId = $this->destinationObject->getPost()->ID;
        $destinationPostMetaKeys = \get_post_custom_keys($destinationPostId) ?: [];

        foreach (\array_diff($destinationPostMetaKeys, $this->sourceObject->getMetaKeys()) as $key) {
            \delete_post_meta($destinationPostId, $key);
        }

        foreach ($this->sourceObject->getMetaValues() as $key => $value) {
            if (\in_array($key, $this->getExcludeKeys())) {
                continue;
            }

            \update_post_meta($destinationPostId, $key, \wp_slash($value));
        }

        /**
         * Fire after insert post meta action.
         *
         * @param AbstractPostProcessor processor instance
         */
        \do_action('Statik\Share\afterInsertPostMeta', $this);
    }

    /**
     * Insert destination Post Taxonomies.
     */
    private function insertPostTaxonomiesIntoDestinationBlog(): void
    {
        /**
         * Fire before insert post taxonomies action.
         *
         * @param AbstractPostProcessor processor instance
         */
        \do_action('Statik\Share\beforeInsertPostTaxonomies', $this);

        $postTaxonomies = \get_object_taxonomies($this->destinationObject->getPost()->post_type);

        foreach ($postTaxonomies as $taxonomy) {
            \wp_set_object_terms($this->destinationObject->getPost()->ID, null, $taxonomy);
        }

        foreach ($this->sourceObject->getTerms() as $sourceTaxonomy => $sourceTerms) {
            $termsIds = [];

            /** @var \WP_Term $sourceTerm */
            foreach ($sourceTerms as $sourceTerm) {
                $destinationTermExists = \term_exists($sourceTerm->name, $sourceTaxonomy);

                if ($destinationTermExists) {
                    $destinationTerm = \get_term($destinationTermExists['term_id']);
                    $destinationTermId = $termsIds[] = $destinationTerm->term_id;

                    if (
                        $sourceTerm->slug !== $destinationTerm->slug
                        || $sourceTerm->description !== $destinationTerm->description
                    ) {
                        \wp_update_term(
                            $destinationTerm->term_id,
                            $sourceTaxonomy,
                            [
                                'description' => $sourceTerm->description,
                                'slug'        => $sourceTerm->slug,
                            ]
                        );
                    }
                } else {
                    $destinationTermId = $termsIds[] = \wp_insert_term(
                            $sourceTerm->name,
                            $sourceTaxonomy,
                            [
                                'description' => $sourceTerm->description,
                                'slug'        => $sourceTerm->slug,
                                'parent'      => '0',
                            ]
                        )['term_id'] ?? 0;
                }

                $this->insertParentForTaxonomyIntoDestinationBlog($sourceTerm, $sourceTaxonomy, $destinationTermId);
            }

            \wp_set_object_terms($this->destinationObject->getPost()->ID, $termsIds, $sourceTaxonomy);
        }

        /**
         * Fire after insert post taxonomies action.
         *
         * @param AbstractPostProcessor processor instance
         */
        \do_action('Statik\Share\afterInsertPostTaxonomies', $this);
    }

    /**
     * Insert taxonomies' parents in destination blog.
     */
    private function insertParentForTaxonomyIntoDestinationBlog(
        \WP_Term $sourceTerm,
        string $sourceTaxonomy,
        int $destinationTermId
    ): void {
        if (
            false === \is_object($sourceTerm->parent)
            || false === \is_a($sourceTerm->parent, \WP_Term::class)
        ) {
            return;
        }

        $destinationTermParentExists = \term_exists($sourceTerm->parent->name, $sourceTaxonomy);

        if ($destinationTermParentExists) {
            $destinationTermParent = \get_term($destinationTermParentExists['term_id']);
            $destinationTermParentId = $destinationTermParent->term_id ?? 0;
        } else {
            $destinationTermParentId = \wp_insert_term(
                    $sourceTerm->parent->name,
                    $sourceTaxonomy,
                    [
                        'description' => $sourceTerm->parent->description,
                        'slug'        => $sourceTerm->parent->slug,
                        'parent'      => '0',
                    ]
                )['term_id'] ?? 0;
        }

        \wp_update_term($destinationTermId, $sourceTaxonomy, ['parent' => $destinationTermParentId]);

        $this->insertParentForTaxonomyIntoDestinationBlog(
            $sourceTerm->parent,
            $sourceTaxonomy,
            $destinationTermParentId
        );
    }
}
