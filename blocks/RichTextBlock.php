<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\blocks;

use Yii;

class RichTextBlock extends BaseBlock
{
    public const TYPE = 'rich_text';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('EngagementPagesModule.base', 'Rich text');
    }

    public function normalizeSettings(): array
    {
        return [
            'title' => $this->string('title'),
            'body' => $this->string('body'),
        ];
    }
}
