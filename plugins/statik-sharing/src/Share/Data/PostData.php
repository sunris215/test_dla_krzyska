<?php

declare(strict_types=1);

namespace Statik\Sharing\Share\Data;

use Statik\Sharing\Post\PostMeta;
use Statik\Sharing\Share\Exception\ShareProcessException;

/**
 * Class PostData.
 */
class PostData extends AbstractData
{
    protected array $metaKeys;

    protected array $metaValues;

    protected array $taxonomies;

    protected array $terms;

    protected PostMeta $meta;

    protected ?\WP_Post $thumbnail;

    /**
     * Get the Post.
     */
    public function getPost(): ?\WP_Post
    {
        return $this->object;
    }

    /**
     * Get Post meta keys.
     */
    public function getMetaKeys(): array
    {
        return $this->metaKeys;
    }

    /**
     * Get Post meta values.
     */
    public function getMetaValues(): array
    {
        return $this->metaValues;
    }

    /**
     * Get Post taxonomies.
     */
    public function getTaxonomies(): array
    {
        return $this->taxonomies;
    }

    /**
     * Get Post terms.
     */
    public function getTerms(): array
    {
        return $this->terms;
    }

    /**
     * Get post meta.
     */
    public function getMeta(): PostMeta
    {
        return $this->meta;
    }

    /**
     * Get thumbnail Id.
     */
    public function getThumbnail(): ?\WP_Post
    {
        return $this->thumbnail;
    }

    /**
     * Convert object to array instance.
     */
    public function toArray(): array
    {
        return \get_object_vars($this);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ShareProcessException
     */
    public function initialize(): void
    {
        $this->object = \get_post($this->id) ?: null;

        if (false === \is_object($this->object)) {
            throw new ShareProcessException(
                \sprintf(\__('The data object for ID %d on blog %d does not exist'), $this->id, $this->blogId)
            );
        }

        $this->metaKeys = (array) \get_post_custom_keys($this->id);
        $this->metaValues = $this->prepareMetaValues();
        $this->taxonomies = \get_object_taxonomies($this->object->post_type);
        $this->terms = $this->prepareTerms();
        $this->meta = new PostMeta($this->id, $this->blogId);
        $thumbnail = \get_post_thumbnail_id($this->id);
        $this->thumbnail = $thumbnail ? \get_post($thumbnail) : null;
    }

    /**
     * Prepare meta values for post.
     */
    private function prepareMetaValues(): array
    {
        foreach ($this->metaKeys as $metaKey) {
            $values[$metaKey] = \get_post_meta($this->object->ID, $metaKey, true);
        }

        return $values ?? [];
    }

    /**
     * Prepare terms for post.
     */
    private function prepareTerms(): array
    {
        foreach ($this->taxonomies as $taxonomy) {
            $postTerms = \wp_get_object_terms($this->object->ID, $taxonomy, ['orderby' => 'term_order']);

            for ($i = 0, $termsCount = \count($postTerms); $i < $termsCount; ++$i) {
                $terms[$taxonomy][] = $this->prepareParentForTaxonomy($postTerms[$i], $taxonomy);
            }
        }

        return $terms ?? [];
    }

    /**
     * Prepare taxonomy Parent. If there is more levels of parents, then get all of them.
     */
    private function prepareParentForTaxonomy(\WP_Term $term, string $taxonomy): \WP_Term
    {
        if (0 === $term->parent) {
            return $term;
        }

        $parentTerm = \get_term($term->parent, $taxonomy);

        if (\is_wp_error($parentTerm)) {
            return $term;
        }

        $term->parent = $this->prepareParentForTaxonomy(\get_term($term->parent, $taxonomy), $taxonomy);

        return $term;
    }
}
