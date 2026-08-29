<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\services;

use yii\base\Event;

class RegisterBlocksEvent extends Event
{
    /** @var array<string, class-string> */
    public array $types = [];

    /** @var array<int, array{type:string,icon:string,group:string}> */
    public array $palette = [];
}
