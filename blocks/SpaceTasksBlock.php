<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use Yii;

class SpaceTasksBlock extends SpaceWidgetBlock
{
    public const TYPE = 'space_tasks';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Space tasks');
    }
}
