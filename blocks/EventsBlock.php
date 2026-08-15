<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use Yii;

class EventsBlock extends BaseBlock
{
    public const TYPE = 'events';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Events');
    }

    public function normalizeSettings(): array
    {
        $items = [];
        foreach ((array) ($this->settings['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $items[] = [
                'title' => $title,
                'date' => trim((string) ($item['date'] ?? '')),
                'time' => trim((string) ($item['time'] ?? '')),
                'location' => trim((string) ($item['location'] ?? '')),
                'url' => trim((string) ($item['url'] ?? '')),
                'cta_label' => trim((string) ($item['cta_label'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Register'))),
            ];
        }

        return [
            'title' => $this->string('title', Yii::t('ThiscoveryPageBuilderModule.base', 'Upcoming events')),
            'items' => $items,
        ];
    }
}
