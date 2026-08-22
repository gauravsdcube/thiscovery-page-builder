<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use Yii;

abstract class SpaceWidgetBlock extends BaseBlock
{
    public function normalizeSettings(): array
    {
        return [
            'title' => $this->string('title'),
        ];
    }
}
