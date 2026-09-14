<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\services;

use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use yii\helpers\Json;
use humhub\modules\thiscoveryPageBuilder\services\BlockRegistry;

/**
 * Export / import / in-memory hydrate of a page definition for versioning.
 */
class PageSnapshotService
{
    public const SCHEMA_VERSION = 1;

    /**
     * @return array<string, mixed>
     */
    public function export(EngagementPage $page): array
    {
        return [
            'schema' => self::SCHEMA_VERSION,
            'meta' => [
                'title' => $page->title,
                'summary' => $page->summary,
                'layout' => $page->getLayoutKey(),
                'page_width' => $page->getPageWidthKey(),
                'status' => (int) $page->status,
                'listed' => (int) $page->listed,
                'featured' => (int) $page->featured,
                'audience' => $page->hasAttribute('audience') ? $page->getAudienceKey() : null,
                'category' => $page->category,
                'closes_at' => $page->closes_at,
                'theme_id' => $page->hasAttribute('theme_id') ? $page->theme_id : null,
                'style_json' => $page->hasAttribute('style_json') ? $page->style_json : null,
                'custom_css' => $page->hasAttribute('custom_css') ? $page->custom_css : null,
            ],
            'sections' => $page->getSections(),
        ];
    }

    /**
     * Persist snapshot onto the working draft (restore). Availability status is kept.
     */
    public function import(EngagementPage $page, array $snapshot, bool $preserveStatus = true): bool
    {
        $this->applyMeta($page, $snapshot, $preserveStatus);
        $sections = $snapshot['sections'] ?? [];
        $page->sections = is_array($sections) ? $sections : [];
        $page->sections_json = Json::encode($page->sections);

        return $page->save();
    }

    /**
     * Apply snapshot onto an in-memory page for public/preview without writing the draft.
     */
    public function hydrateInMemory(EngagementPage $page, array $snapshot): void
    {
        $this->applyMeta($page, $snapshot, true);
        $sections = $snapshot['sections'] ?? [];
        $page->sections = is_array($sections) ? BlockRegistry::normalizeSections($sections) : [];
        $page->sections_json = Json::encode($page->sections);
    }

    protected function applyMeta(EngagementPage $page, array $snapshot, bool $preserveStatus): void
    {
        $meta = is_array($snapshot['meta'] ?? null) ? $snapshot['meta'] : [];
        $currentStatus = (int) $page->status;

        if (array_key_exists('title', $meta) && $meta['title'] !== null && $meta['title'] !== '') {
            $page->title = (string) $meta['title'];
        }
        if (array_key_exists('summary', $meta)) {
            $page->summary = $meta['summary'];
        }
        if (isset($meta['layout']) && $meta['layout'] !== '') {
            $page->layout = (string) $meta['layout'];
        }
        if (isset($meta['page_width']) && $meta['page_width'] !== '') {
            $page->page_width = (string) $meta['page_width'];
        }
        if (array_key_exists('listed', $meta)) {
            $page->listed = (int) $meta['listed'];
        }
        if (array_key_exists('featured', $meta)) {
            $page->featured = (int) $meta['featured'];
        }
        if ($page->hasAttribute('audience') && isset($meta['audience']) && $meta['audience'] !== null && $meta['audience'] !== '') {
            $page->audience = (string) $meta['audience'];
        }
        if (array_key_exists('category', $meta)) {
            $page->category = $meta['category'];
        }
        if (array_key_exists('closes_at', $meta)) {
            $page->closes_at = $meta['closes_at'];
        }
        if ($page->hasAttribute('theme_id') && array_key_exists('theme_id', $meta)) {
            $tid = $meta['theme_id'];
            $page->theme_id = ($tid === '' || $tid === null) ? null : (int) $tid;
        }
        if ($page->hasAttribute('style_json') && array_key_exists('style_json', $meta)) {
            $page->style_json = $meta['style_json'];
            $decoded = json_decode((string) $page->style_json, true);
            $page->style = is_array($decoded) ? $decoded : [];
        }
        if ($page->hasAttribute('custom_css') && array_key_exists('custom_css', $meta)) {
            $page->custom_css = $meta['custom_css'];
        }

        if (!$preserveStatus && isset($meta['status'])) {
            $page->status = (int) $meta['status'];
        } else {
            $page->status = $currentStatus;
        }
    }
}
