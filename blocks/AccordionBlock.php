<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use Yii;

class AccordionBlock extends BaseBlock
{
    public const TYPE = 'accordion';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Accordion / FAQ');
    }

    public function normalizeSettings(): array
    {
        $items = [];
        foreach ((array) ($this->settings['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $heading = trim((string) ($item['heading'] ?? ''));
            $body = trim((string) ($item['body'] ?? ''));
            if ($heading === '' && $body === '') {
                continue;
            }
            $items[] = [
                'heading' => $heading,
                'body' => $body,
            ];
        }

        return [
            'title' => $this->string('title', Yii::t('ThiscoveryPageBuilderModule.base', 'Frequently asked questions')),
            'items' => $items,
        ];
    }
}
