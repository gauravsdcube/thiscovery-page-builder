<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\blocks;

use Yii;

class DownloadsBlock extends BaseBlock
{
    public const TYPE = 'downloads';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('EngagementPagesModule.base', 'Downloads');
    }

    public function normalizeSettings(): array
    {
        $items = [];
        $raw = $this->settings['items'] ?? [];

        // Parallel arrays from form post (item_label / item_url / item_file_guid / item_alt)
        if (isset($this->settings['item_label']) || isset($this->settings['item_file_guid'])) {
            $labels = (array) ($this->settings['item_label'] ?? []);
            $urls = (array) ($this->settings['item_url'] ?? []);
            $guids = (array) ($this->settings['item_file_guid'] ?? []);
            $alts = (array) ($this->settings['item_alt'] ?? []);
            $count = max(count($labels), count($urls), count($guids), count($alts));
            $raw = [];
            for ($i = 0; $i < $count; $i++) {
                $raw[] = [
                    'label' => (string) ($labels[$i] ?? ''),
                    'url' => (string) ($urls[$i] ?? ''),
                    'file_guid' => (string) ($guids[$i] ?? ''),
                    'alt' => (string) ($alts[$i] ?? ''),
                ];
            }
        }

        if (is_array($raw)) {
            foreach ($raw as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $label = isset($item['label']) ? trim((string) $item['label']) : '';
                $url = isset($item['url']) ? trim((string) $item['url']) : '';
                $fileGuid = isset($item['file_guid']) ? trim((string) $item['file_guid']) : '';
                $alt = isset($item['alt']) ? trim((string) $item['alt']) : '';
                if ($label === '' && $url === '' && $fileGuid === '') {
                    continue;
                }
                $items[] = [
                    'label' => $label,
                    'url' => $url,
                    'file_guid' => $fileGuid,
                    'alt' => $alt,
                ];
            }
        }

        return [
            'title' => $this->string(
                'title',
                Yii::t('EngagementPagesModule.base', 'Documents')
            ),
            'items' => $items,
        ];
    }
}
