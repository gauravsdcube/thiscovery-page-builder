<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\helpers;

use Yii;

/**
 * Soft dependency on Thiscovery Mapping for the map embed block.
 */
class MappingAvailability
{
    public const MAP_EMBED_TYPE = 'map_embed';

    public static function isEnabled(): bool
    {
        if (!Yii::$app->hasModule('thiscovery-mapping')) {
            return false;
        }
        try {
            $module = Yii::$app->getModule('thiscovery-mapping');
        } catch (\Throwable $e) {
            return false;
        }
        return $module && $module->getIsEnabled();
    }
}
