<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\assets;

use yii\web\AssetBundle;

class EngagementPagesAsset extends AssetBundle
{
    public $sourcePath = '@engagement-pages/resources';

    public $css = [
        'css/engagement-pages.css',
    ];

    public $js = [
        'js/humhub.engagementPages.js',
    ];

    public $depends = [
        'humhub\assets\CoreApiAsset',
    ];

    public $publishOptions = [
        'forceCopy' => true,
    ];
}
