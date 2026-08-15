<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use Yii;

class CalloutBlock extends BaseBlock
{
    public const TYPE = 'callout';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Callout');
    }

    public function normalizeSettings(): array
    {
        $tone = (string) ($this->settings['tone'] ?? 'info');
        if (!in_array($tone, ['info', 'success', 'warning', 'neutral'], true)) {
            $tone = 'info';
        }

        return [
            'title' => $this->string('title'),
            'body' => $this->string('body'),
            'tone' => $tone,
        ];
    }
}
