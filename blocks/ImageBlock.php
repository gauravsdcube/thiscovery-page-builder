<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\blocks;

use Yii;

class ImageBlock extends BaseBlock
{
    public const TYPE = 'image';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('EngagementPagesModule.base', 'Image');
    }

    public function normalizeSettings(): array
    {
        return [
            'image_guid' => $this->string('image_guid'),
            'image_url' => $this->string('image_url'),
            'alt' => $this->string('alt'),
            'caption' => $this->string('caption'),
            'link_url' => $this->string('link_url'),
            'use_as_card_image' => !empty($this->settings['use_as_card_image']),
        ];
    }
}
