<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;

/** @var string $namePrefix */
/** @var array $item */
?>
<div class="ep-repeat-item" data-ep-repeat-item>
    <div class="row g-2">
        <div class="col-md-6 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Name') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[name]"
                   value="<?= Html::encode($item['name'] ?? '') ?>">
        </div>
        <div class="col-md-6 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Role') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[role]"
                   value="<?= Html::encode($item['role'] ?? '') ?>">
        </div>
    </div>
    <div class="row g-2">
        <div class="col-md-6 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Email') ?></label>
            <input type="email" class="form-control" name="<?= $namePrefix ?>[email]"
                   value="<?= Html::encode($item['email'] ?? '') ?>">
        </div>
        <div class="col-md-6 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Phone') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[phone]"
                   value="<?= Html::encode($item['phone'] ?? '') ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Short bio') ?></label>
        <textarea class="form-control" rows="2" name="<?= $namePrefix ?>[bio]"><?= Html::encode($item['bio'] ?? '') ?></textarea>
    </div>
    <button type="button" class="btn btn-light btn-sm" data-ep-repeat-remove>
        <i class="fa fa-times"></i> <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Remove') ?>
    </button>
    <hr>
</div>
