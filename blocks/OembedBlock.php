<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use Yii;

class OembedBlock extends BaseBlock
{
    public const TYPE = 'oembed';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Video embed');
    }

    public function normalizeSettings(): array
    {
        return [
            'url' => $this->string('url'),
            'title' => $this->string('title'),
        ];
    }
}
