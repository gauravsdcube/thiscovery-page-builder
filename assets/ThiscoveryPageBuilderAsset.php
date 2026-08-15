<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\assets;

use yii\web\AssetBundle;

class ThiscoveryPageBuilderAsset extends AssetBundle
{
    public $sourcePath = '@thiscovery-page-builder/resources';

    public $css = [
        'css/thiscovery-page-builder.css',
    ];

    public $js = [
        'js/humhub.thiscoveryPageBuilder.js',
    ];

    public $depends = [
        'humhub\assets\CoreApiAsset',
        'humhub\modules\thiscoveryEditor\assets\EditorAsset',
    ];

    public $publishOptions = [
        'forceCopy' => true,
    ];
}
