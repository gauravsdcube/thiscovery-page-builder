<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use humhub\modules\thiscoveryPageBuilder\helpers\CustomHtml;
use Yii;

class CustomHtmlBlock extends BaseBlock
{
    public const TYPE = 'custom_html';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Custom HTML');
    }

    public function normalizeSettings(): array
    {
        $html = $this->settings['html'] ?? '';
        if (!is_string($html)) {
            $html = '';
        }

        return [
            'title' => $this->string('title'),
            'html' => CustomHtml::normalize($html),
        ];
    }
}
