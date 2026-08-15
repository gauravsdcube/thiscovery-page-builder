<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\helpers\Url as PageUrl;
use humhub\modules\space\models\Space;
use humhub\widgets\bootstrap\Button;

/** @var Space $space */
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Stream restricted') ?></strong>
    </div>
    <div class="panel-body">
        <p>
            <?= Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'Public engagement feedback lives on project pages. The Space stream is limited to administrators.'
            ) ?>
        </p>
        <?= Button::primary(Yii::t('ThiscoveryPageBuilderModule.base', 'Open page builder'))
            ->link($space->createUrl('/thiscovery-page-builder/page/index'))
            ->icon('th-large') ?>
        <?= Button::defaultType(Yii::t('ThiscoveryPageBuilderModule.base', 'Browse public pages'))
            ->link(PageUrl::toDirectory())
            ->sm() ?>
    </div>
</div>
