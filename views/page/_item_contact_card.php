<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;

/**
 * @var string $namePrefix
 * @var array $item
 * @var string $widgetId
 * @var \humhub\modules\thiscoveryPageBuilder\models\EngagementPage|null $page
 */

$page = $page ?? null;
$widgetId = $widgetId ?? ('ep-cc-' . substr(md5($namePrefix), 0, 12));
?>
<div class="ep-repeat-item" data-ep-repeat-item>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Photo') ?>
            <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
        </label>
        <?= $this->render('_upload_field', [
            'inputName' => $namePrefix . '[image_guid]',
            'guid' => $item['image_guid'] ?? '',
            'widgetId' => $widgetId,
            'imagesOnly' => true,
            'buttonLabel' => Yii::t('ThiscoveryPageBuilderModule.base', 'Upload photo'),
            'page' => $page,
        ]) ?>
        <div class="ep-hint text-muted">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Square photos work best. They are shown in a circle with a coloured ring.') ?>
        </div>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Photo alt text') ?>
            <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
        </label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[image_alt]"
               value="<?= Html::encode($item['image_alt'] ?? '') ?>"
               placeholder="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Describe the photo for screen readers') ?>">
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Name') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[name]"
               value="<?= Html::encode($item['name'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Role') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[role]"
               value="<?= Html::encode($item['role'] ?? '') ?>"
               placeholder="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'e.g. Research Professor') ?>">
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Organisation') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[organisation]"
               value="<?= Html::encode($item['organisation'] ?? '') ?>"
               placeholder="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'e.g. THIS Institute, University of Cambridge') ?>">
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Email') ?></label>
        <input type="email" class="form-control" name="<?= $namePrefix ?>[email]"
               value="<?= Html::encode($item['email'] ?? '') ?>">
    </div>
    <button type="button" class="btn btn-light btn-sm" data-ep-repeat-remove>
        <i class="fa fa-times"></i> <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Remove') ?>
    </button>
    <hr>
</div>
