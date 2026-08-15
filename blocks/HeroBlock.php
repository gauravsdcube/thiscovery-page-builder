<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use Yii;

class HeroBlock extends BaseBlock
{
    public const TYPE = 'hero';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Hero');
    }

    public function normalizeSettings(): array
    {
        return [
            'headline' => $this->string('headline'),
            'subheadline' => $this->string('subheadline'),
            'image_guid' => $this->string('image_guid'),
            'image_alt' => $this->string('image_alt'),
            // Legacy external URL support (read-only fallback for existing pages)
            'image_url' => $this->string('image_url'),
            'cta_label' => $this->string('cta_label'),
            'cta_url' => $this->string('cta_url'),
        ];
    }
}
